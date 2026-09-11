<?php
// Inclui os arquivos essenciais
require_once '../auth.php';
proteger_pagina(['admin']); 
require_once '../conexao.php';
require_once '../header.php';

// Variáveis de Controle
$erro = null;
$sucesso = null;
$laboratorio_editando = null; 

// =========================================================
// FUNÇÃO DE SERVIÇO: GEOCLASSIFICAÇÃO (NOVA)
// =========================================================

/**
 * Consulta a API Nominatim para obter Lat/Lng e atualiza o laboratório no banco.
 * @param mysqli $conexao
 * @param int $laboratorio_id ID do laboratório recém-inserido
 * @param string $endereco_formatado Endereço completo para busca
 * @return bool True em caso de sucesso, False em caso de falha na API ou UPDATE.
 */
function geocodificar_e_atualizar($conexao, $laboratorio_id, $endereco_formatado) {
    // Codifica a URL e define o endpoint do Nominatim
    $query = urlencode($endereco_formatado);
    $url_nominatim = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";
    
    // Configurações da requisição (CRÍTICO: User-Agent para Nominatim)
    $context = stream_context_create([
        'http' => [
            // Use um User-Agent válido para evitar bloqueios da API
            'header' => "User-Agent: ClinicaHealthTechApp/1.0 (administrador@suaempresa.com.br)\r\n" 
        ]
    ]);
    
    // Tenta fazer a requisição HTTP
    $resultado_raw = @file_get_contents($url_nominatim, false, $context);
    
    if ($resultado_raw === FALSE) {
        return false; // Falha na conexão de rede
    }
    
    $dados_geocodificados = json_decode($resultado_raw, true);

    if (!empty($dados_geocodificados) && isset($dados_geocodificados[0]['lat'])) {
        
        $latitude = $dados_geocodificados[0]['lat'];
        $longitude = $dados_geocodificados[0]['lon'];

        // 2. Atualizar o Banco de Dados com Lat/Lng
        $sql_update = "UPDATE laboratorios_parceiros SET latitude = ?, longitude = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conexao, $sql_update);
        
        mysqli_stmt_bind_param($stmt_update, "ddi", $latitude, $longitude, $laboratorio_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            return true;
        }
    }
    return false; // Falha na geocodificação ou no update
}

// =========================================================
// LÓGICA DE PROCESSAMENTO (CRUD Actions)
// =========================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    $laboratorio_id = $_POST['laboratorio_id'] ?? null;
    
    // CAMPOS DE ENDEREÇO E CONTATO
    $nome_empresa = trim($_POST['nome_empresa'] ?? '');
    $cep = trim($_POST['cep'] ?? ''); 
    $telefone = trim($_POST['telefone'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $uf = trim($_POST['uf'] ?? '');
    $numero = trim($_POST['numero'] ?? ''); 
    $complemento = trim($_POST['complemento'] ?? ''); 

    // Reconstrução do Endereço Completo para o campo legacy e geocodificação
    $endereco_completo = "{$logradouro}, {$numero}";
    if (!empty($complemento)) {
        $endereco_completo .= " ({$complemento})";
    }
    $endereco_completo .= " - {$bairro}, {$cidade} - {$uf}";

    if (empty($nome_empresa) || empty($cep) || empty($telefone) || empty($logradouro) || empty($cidade) || empty($numero)) {
        $erro = "Por favor, preencha o Nome, CEP, Telefone e todos os detalhes do Endereço, incluindo o Número.";
    } else {
        try {
            if ($acao == 'adicionar') {
                mysqli_begin_transaction($conexao); // Inicia transação para INSERT e UPDATE Lat/Lng
                
                // 1. INSERT na Tabela (com latitude/longitude como NULL)
                $sql = "INSERT INTO laboratorios_parceiros 
                        (nome_empresa, endereco_completo, cep, telefone, logradouro, bairro, cidade, uf, numero, complemento) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param($stmt, "ssssssssss", 
                                        $nome_empresa, $endereco_completo, $cep, $telefone, 
                                        $logradouro, $bairro, $cidade, $uf, 
                                        $numero, $complemento);
                mysqli_stmt_execute($stmt);
                
                $novo_id = mysqli_insert_id($conexao);
                
                // 2. TENTATIVA DE GEOCLASSIFICAÇÃO IMEDIATA
                $endereco_para_geocodificacao = "{$logradouro} {$numero}, {$cidade}, {$uf}, Brasil";
                $geocodificado = geocodificar_e_atualizar($conexao, $novo_id, $endereco_para_geocodificacao);
                
                mysqli_commit($conexao); // Confirma ambas as operações

                if ($geocodificado) {
                    $sucesso = "Laboratório '$nome_empresa' adicionado e geocodificado com sucesso! O mapa já está disponível.";
                } else {
                    $sucesso = "Laboratório '$nome_empresa' adicionado com sucesso. (⚠️ Falha na geocodificação automática. Coordenadas pendentes.)";
                }

            } elseif ($acao == 'editar' && $laboratorio_id) {
                // Ao editar o endereço, as coordenadas são resetadas para NULL para forçar nova geocodificação
                $sql = "UPDATE laboratorios_parceiros 
                        SET nome_empresa = ?, endereco_completo = ?, cep = ?, telefone = ?, logradouro = ?, bairro = ?, cidade = ?, uf = ?, numero = ?, complemento = ?, 
                            latitude = NULL, longitude = NULL 
                        WHERE id = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param($stmt, "ssssssssssi", 
                                        $nome_empresa, $endereco_completo, $cep, $telefone, 
                                        $logradouro, $bairro, $cidade, $uf, 
                                        $numero, $complemento, 
                                        $laboratorio_id);
                mysqli_stmt_execute($stmt);
                $sucesso = "Laboratório '$nome_empresa' atualizado com sucesso! (Coordenadas pendentes de recálculo)";
            }

        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conexao);
            $erro = "Erro de SQL durante a transação: " . $e->getMessage();
        }
    }
}

// Lógica para Excluir (Mantida)
if (isset($_GET['excluir_id'])) {
    $excluir_id = (int)$_GET['excluir_id'];
    try {
        $sql = "DELETE FROM laboratorios_parceiros WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $excluir_id);
        mysqli_stmt_execute($stmt);
        $sucesso = "Laboratório excluído com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $erro = "Erro ao excluir: " . $e->getMessage(); 
    }
    $status_param = ($erro) ? 'erro' : 'sucesso';
    $msg_param = urlencode($erro ?? $sucesso);
    header("Location: gerenciar_laboratorios.php?status={$status_param}&msg={$msg_param}");
    exit();
}

// Lógica para carregar dados para Edição (ATUALIZADA com lat/lng)
if (isset($_GET['editar_id'])) {
    $editar_id = (int)$_GET['editar_id'];
    // Busca todos os campos, incluindo latitude e longitude para o mapa
    $sql = "SELECT * FROM laboratorios_parceiros WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $editar_id);
    mysqli_stmt_execute($stmt);
    $laboratorio_editando = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// =========================================================
// BUSCA E LISTAGEM
// =========================================================
// Adiciona latitude e longitude para o botão Mapa
$sql_listagem = "SELECT *, latitude, longitude FROM laboratorios_parceiros ORDER BY nome_empresa";
$resultado_listagem = mysqli_query($conexao, $sql_listagem);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>

<div class="d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-microscope me-2"></i> Gestão de Laboratórios Parceiros</h2>
</div>
<p class="text-muted">Cadastre laboratórios, endereços, CEPs e telefones.</p>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= ($_GET['status'] == 'sucesso') ? 'success' : 'danger' ?>"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <?= $laboratorio_editando ? 'Editar Laboratório: ' . htmlspecialchars($laboratorio_editando['nome_empresa']) : 'Adicionar Novo Laboratório' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="gerenciar_laboratorios.php">
            <input type="hidden" name="acao" value="<?= $laboratorio_editando ? 'editar' : 'adicionar' ?>">
            <?php if ($laboratorio_editando): ?>
                <input type="hidden" name="laboratorio_id" value="<?= $laboratorio_editando['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="nome_empresa" class="form-label">Nome da Empresa (Laboratório):</label>
                <input type="text" class="form-control" id="nome_empresa" name="nome_empresa" required 
                       value="<?= htmlspecialchars($laboratorio_editando['nome_empresa'] ?? '') ?>">
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="cep" class="form-label">CEP:</label>
                    <input type="text" class="form-control" id="cep" name="cep" required 
                           value="<?= htmlspecialchars($laboratorio_editando['cep'] ?? '') ?>" 
                           placeholder="Ex: 90000-000" 
                           onblur="buscarCep(this.value)">
                    <div class="form-text" id="status-cep">Digite o CEP para preencher o endereço.</div>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="logradouro" class="form-label">Logradouro / Rua:</label>
                    <input type="text" class="form-control" id="logradouro" name="logradouro" required 
                           value="<?= htmlspecialchars($laboratorio_editando['logradouro'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="numero" class="form-label">Número:</label>
                    <input type="text" class="form-control" id="numero" name="numero" required 
                           value="<?= htmlspecialchars($laboratorio_editando['numero'] ?? '') ?>">
                </div>
                <div class="col-md-9 mb-3">
                    <label for="complemento" class="form-label">Complemento (Bloco, Sala):</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" 
                           value="<?= htmlspecialchars($laboratorio_editando['complemento'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bairro" class="form-label">Bairro:</label>
                    <input type="text" class="form-control" id="bairro" name="bairro" 
                           value="<?= htmlspecialchars($laboratorio_editando['bairro'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cidade" class="form-label">Cidade:</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" required
                           value="<?= htmlspecialchars($laboratorio_editando['cidade'] ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="uf" class="form-label">UF:</label>
                    <input type="text" class="form-control" id="uf" name="uf" maxlength="2"
                           value="<?= htmlspecialchars($laboratorio_editando['uf'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone:</label>
                <input type="text" class="form-control" id="telefone" name="telefone" required 
                           value="<?= htmlspecialchars($laboratorio_editando['telefone'] ?? '') ?>" placeholder="Ex: (51) 98765-4321">
            </div>

            <?php 
            $tem_coordenadas = $laboratorio_editando && !empty($laboratorio_editando['latitude']) && !empty($laboratorio_editando['longitude']);
            ?>
            
            <?php if ($laboratorio_editando): ?>
                <h4 class="mt-4">Localização Geográfica</h4>
                <?php if ($tem_coordenadas): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <i class="fas fa-map-marker-alt me-1"></i> Mapa de Confirmação
                        </div>
                        <div class="card-body">
                            <div id="mapaLaboratorio" style="height: 400px; width: 100%;">
                                Carregando mapa...
                            </div>
                            <p class="text-muted mt-2">Coordenadas: Lat: **<?= htmlspecialchars($laboratorio_editando['latitude']) ?>**, Lng: **<?= htmlspecialchars($laboratorio_editando['longitude']) ?>**</p>
                            <p class="text-danger small">**AVISO:** Se alterar o endereço acima, as coordenadas serão apagadas e precisarão ser recalculadas.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-exclamation-triangle me-1"></i> Coordenadas Geográficas (Latitude/Longitude) estão ausentes.
                        <br>O mapa será exibido aqui após o processamento em `processar_geolocalizacao.php` (ou o próximo cadastro).
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> <?= $laboratorio_editando ? 'Salvar Alterações' : 'Adicionar Laboratório' ?>
                </button>
                <?php if ($laboratorio_editando): ?>
                    <a href="gerenciar_laboratorios.php" class="btn btn-warning">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3>Lista de Laboratórios Parceiros</h3>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>CEP</th>
            <th>Telefone</th>
            <th>Endereço Completo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($resultado_listagem) > 0): ?>
            <?php while ($lab = mysqli_fetch_assoc($resultado_listagem)): ?>
                <tr>
                    <td><?= $lab['id'] ?></td>
                    <td><?= htmlspecialchars($lab['nome_empresa']) ?></td>
                    <td><?= htmlspecialchars($lab['cep'] ?? 'N/D') ?></td>
                    <td><?= htmlspecialchars($lab['telefone'] ?? 'N/D') ?></td>
                    <td><?= htmlspecialchars($lab['endereco_completo'] ?? '') ?></td>
                    <td>
                        <?php 
                        $tem_coordenadas = !empty($lab['latitude']) && !empty($lab['longitude']);
                        if ($tem_coordenadas):
                        ?>
                            <button type="button" class="btn btn-sm btn-success me-2" 
                                    onclick="exibirMapa('<?= htmlspecialchars($lab['nome_empresa']) ?>', '<?= $lab['latitude'] ?>', '<?= $lab['longitude'] ?>')">
                                Mapa
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-light text-muted me-2" disabled>
                                Mapa (N/D)
                            </button>
                        <?php endif; ?>
                        <a href="?editar_id=<?= $lab['id'] ?>" class="btn btn-sm btn-info me-2">Editar</a>
                        <a href="?excluir_id=<?= $lab['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Tem certeza que deseja excluir este laboratório? Se houver vínculos com exames, a exclusão pode falhar.');">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">Nenhum laboratório cadastrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="modal fade" id="mapaViewModal" tabindex="-1" aria-labelledby="mapaViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="mapaViewModalLabel">Localização do Laboratório</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="labNomeNoMapa" class="mb-3 text-primary"></h4>
                <div id="mapaView" style="height: 450px; width: 100%;">
                    Carregando mapa...
                </div>
                <p class="text-muted mt-2 small">Coordenadas: Lat: <span id="latDisplay"></span>, Lng: <span id="lngDisplay"></span></p>
            </div>
        </div>
    </div>
</div>


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20n6aR4S+t6yX/4k/J+qRjM+b32yF+v/Kj3A/7t/wQ="
    crossorigin=""></script>

<script>
    function limparEndereco() {
        document.getElementById('logradouro').value = "";
        document.getElementById('bairro').value = "";
        document.getElementById('cidade').value = "";
        document.getElementById('uf').value = "";
    }

    function buscarCep(cep) {
        cep = cep.replace(/\D/g, ''); 
        const statusCep = document.getElementById('status-cep');

        if (cep != "" && cep.length === 8) {
            statusCep.textContent = "Buscando endereço...";
            
            const url = `https://viacep.com.br/ws/${cep}/json/`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!("erro" in data)) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('uf').value = data.uf;
                        statusCep.textContent = "Endereço preenchido com sucesso.";
                        document.getElementById('numero').focus(); 
                    } else {
                        limparEndereco();
                        statusCep.textContent = "CEP não encontrado. Preencha o endereço manualmente.";
                    }
                })
                .catch(err => {
                    limparEndereco();
                    statusCep.textContent = "Erro de conexão ao buscar CEP.";
                    console.error("Erro na consulta ViaCEP:", err);
                });
        } else if (cep.length !== 8) {
             statusCep.textContent = "CEP inválido.";
        } else {
             limparEndereco();
             statusCep.textContent = "Digite o CEP para preencher o endereço.";
        }
    }
</script>

<?php if ($tem_coordenadas): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Coordenadas do laboratório
        const lat = parseFloat("<?= $laboratorio_editando['latitude'] ?>");
        const lng = parseFloat("<?= $laboratorio_editando['longitude'] ?>");
        const nome = "<?= htmlspecialchars($laboratorio_editando['nome_empresa']) ?>";

        if (isNaN(lat) || isNaN(lng)) return;

        const map = L.map('mapaLaboratorio').setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup(`<b>${nome}</b><br>Localização geocodificada.`)
            .openPopup();
    });
</script>
<?php endif; ?>

<script>
    let mapaViewInstance = null;
    
    function exibirMapa(nome, lat, lng) {
        const modal = new bootstrap.Modal(document.getElementById('mapaViewModal'));
        const latFloat = parseFloat(lat);
        const lngFloat = parseFloat(lng);

        document.getElementById('labNomeNoMapa').textContent = nome;
        document.getElementById('latDisplay').textContent = latFloat.toFixed(6);
        document.getElementById('lngDisplay').textContent = lngFloat.toFixed(6);

        modal.show();

        document.getElementById('mapaViewModal').addEventListener('shown.bs.modal', function() {
            
            // Destruir instância anterior
            if (mapaViewInstance) {
                mapaViewInstance.remove();
                mapaViewInstance = null;
            }

            // Inicializa o mapa Leaflet
            mapaViewInstance = L.map('mapaView').setView([latFloat, lngFloat], 16); 

            // Adiciona a camada do OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapaViewInstance);

            // Adiciona um marcador
            L.marker([latFloat, lngFloat]).addTo(mapaViewInstance)
                .bindPopup(`<b>${nome}</b>`)
                .openPopup();
            
            // CRÍTICO para mapas em modais: força a atualização do tamanho
            mapaViewInstance.invalidateSize(); 
        }, {once: true}); // Garante que o evento só roda uma vez por exibição de modal
    }
</script>


<?php require_once '../footer.php'; ?>