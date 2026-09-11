<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Conta - Clínica HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container my-5"><div class="row justify-content-center"><div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                
                <div class="text-center mb-4">
                    <a class="navbar-brand text-primary" href="login.php" style="font-size: 2rem; text-decoration: none;">
                        <i class="fas fa-heartbeat"></i>
                        <strong>HealthTech</strong>
                    </a>
                </div>
                <h3 class="card-title text-center mb-4">Crie a sua Conta</h3>
                
                <?php if(isset($_GET['erro'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($_GET['erro']) ?>
                    </div>
                <?php endif; ?>

                <form action="processa_registro.php" method="POST">
                    <div class="mb-3">
                        <label for="tipo_usuario" class="form-label">Eu sou</label>
                        <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                            <option value="">Selecione...</option>
                            <option value="paciente">Paciente</option>
                            <option value="medico">Médico</option>
                            <option value="enfermagem">Enfermagem</option>
                            <option value="gerente">Gerente</option>
                        </select>
                    </div>

                    <div id="campos_paciente" style="display: none;">
                        <label for="pac_cpf_input" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="pac_cpf_input" name="cpf">
                        <div id="cpf_status_msg" class="form-text mb-3"></div>
                        <label for="pac_data_nascimento_input" class="form-label">Data de Nascimento</label>
                        <input type="text" class="form-control" onfocus="(this.type='date')" onblur="(this.type='text')" id="pac_data_nascimento_input" name="data_nascimento">
                    </div>
                    <div id="campos_medico" style="display: none;">
                        <label for="crm_input" class="form-label">CRM</label>
                        <input type="text" class="form-control" id="crm_input" name="crm">
                        <div id="crm_status_msg" class="form-text mb-3"></div>
                        <label for="especialidade_input" class="form-label">Especialidade</label>
                        <input type="text" class="form-control" id="especialidade_input" name="especialidade">
                    </div>
                    <div id="campos_enfermagem" style="display: none;">
                        <label for="coren_input" class="form-label">COREN</label>
                        <input type="text" class="form-control" id="coren_input" name="coren">
                        <div id="coren_status_msg" class="form-text mb-3"></div>
                        <label for="categoria_input" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria_input" name="categoria">
                            <option value="">Selecione...</option>
                            <option value="tecnico">Técnico(a) de Enfermagem</option>
                            <option value="enfermeiro">Enfermeiro(a)</option>
                        </select>
                    </div>
                    <div id="campos_gerente" style="display: none;">
                        <label for="ger_cpf_input" class="form-label">CPF (Gerente)</label>
                        <input type="text" class="form-control mb-3" id="ger_cpf_input" name="gerente_cpf">
                        <label for="ger_data_nascimento_input" class="form-label">Data de Nascimento (Gerente)</label>
                        <input type="text" class="form-control mb-3" onfocus="(this.type='date')" onblur="(this.type='text')" id="ger_data_nascimento_input" name="gerente_data_nascimento">
                    </div>

                    <div id="campos_gerais_login" style="display: none;">
                        <hr>
                        <div class="mb-3">
                            <label for="nome_completo_input" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome_completo_input" name="nome_completo" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_input" class="form-label">Email (será o seu login)</label>
                            <input type="email" class="form-control" id="email_input" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha_input" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha_input" name="senha" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Criar Conta</button>
                        </div>
                        
                        </div>
                </form>
				<div class="d-grid gap-2 mt-4">
                	<a href="login.php" class="btn btn-secondary">Já tenho uma conta</a>
                </div>
            </div>
        </div>
    </div></div></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Referências aos Elementos ---
            const tipoUsuarioSelect = document.getElementById('tipo_usuario');
            const camposPaciente = document.getElementById('campos_paciente');
            const cpfInput = document.getElementById('pac_cpf_input');
            const cpfStatusMsg = document.getElementById('cpf_status_msg');
            const dataNascimentoInput = document.getElementById('pac_data_nascimento_input');
            const camposMedico = document.getElementById('campos_medico');
            const crmInput = document.getElementById('crm_input');
            const crmStatusMsg = document.getElementById('crm_status_msg');
            const especialidadeInput = document.getElementById('especialidade_input');
            const camposEnfermagem = document.getElementById('campos_enfermagem');
            const corenInput = document.getElementById('coren_input');
            const corenStatusMsg = document.getElementById('coren_status_msg');
            const categoriaInput = document.getElementById('categoria_input');
            const camposGerente = document.getElementById('campos_gerente');
            const camposGeraisLogin = document.getElementById('campos_gerais_login');
            const nomeInput = document.getElementById('nome_completo_input');
            const emailInput = document.getElementById('email_input');
            const senhaInput = document.getElementById('senha_input');

            // --- Função de Reset Principal (MAIS SEGURA) ---
            function resetForm() {
                if (camposPaciente) camposPaciente.style.display = 'none';
                if (camposMedico) camposMedico.style.display = 'none';
                if (camposEnfermagem) camposEnfermagem.style.display = 'none';
                if (camposGerente) camposGerente.style.display = 'none';
                if (camposGeraisLogin) camposGeraisLogin.style.display = 'none';
                
                document.querySelectorAll('#campos_paciente input, #campos_medico input, #campos_enfermagem input, #campos_gerente input, #campos_gerais_login input, #campos_enfermagem select').forEach(input => input.required = false);
                
                if (cpfInput) cpfInput.value = '';
                if (cpfStatusMsg) cpfStatusMsg.innerHTML = '';
                if (dataNascimentoInput) { dataNascimentoInput.value = ''; dataNascimentoInput.readOnly = false; }
                if (crmInput) crmInput.value = '';
                if (crmStatusMsg) crmStatusMsg.innerHTML = '';
                if (especialidadeInput) { especialidadeInput.value = ''; especialidadeInput.readOnly = false; }
                if (corenInput) corenInput.value = '';
                if (corenStatusMsg) corenStatusMsg.innerHTML = '';
                if (categoriaInput) { categoriaInput.value = ''; categoriaInput.disabled = false; }
                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                if (emailInput) emailInput.value = '';
                if (senhaInput) senhaInput.value = '';
                const gerCpf = document.getElementById('ger_cpf_input');
                const gerData = document.getElementById('ger_data_nascimento_input');
                if (gerCpf) gerCpf.value = '';
                if (gerData) gerData.value = '';
            }

            // --- Evento Principal: Seleção de Tipo de Usuário ---
            tipoUsuarioSelect.addEventListener('change', function () {
                resetForm(); 
                const tipo = this.value;

                if (tipo === 'paciente') {
                    if (camposPaciente) camposPaciente.style.display = 'block';
                    if (camposGeraisLogin) camposGeraisLogin.style.display = 'block';
                    if (cpfInput) cpfInput.required = true;
                    if (dataNascimentoInput) dataNascimentoInput.required = true;
                    if (nomeInput) nomeInput.required = true;
                    if (emailInput) emailInput.required = true;
                    if (senhaInput) senhaInput.required = true;
                } else if (tipo === 'medico') {
                    if (camposMedico) camposMedico.style.display = 'block';
                    if (camposGeraisLogin) camposGeraisLogin.style.display = 'block';
                    if (crmInput) crmInput.required = true;
                    if (especialidadeInput) especialidadeInput.required = true;
                    if (nomeInput) nomeInput.required = true;
                    if (emailInput) emailInput.required = true;
                    if (senhaInput) senhaInput.required = true;
                } else if (tipo === 'enfermagem') {
                    if (camposEnfermagem) camposEnfermagem.style.display = 'block';
                    if (camposGeraisLogin) camposGeraisLogin.style.display = 'block';
                    if (corenInput) corenInput.required = true;
                    if (categoriaInput) categoriaInput.required = true;
                    if (nomeInput) nomeInput.required = true;
                    if (emailInput) emailInput.required = true;
                    if (senhaInput) senhaInput.required = true;
                } else if (tipo === 'gerente') {
                    if (camposGerente) camposGerente.style.display = 'block';
                    if (camposGeraisLogin) camposGeraisLogin.style.display = 'block';
                    const gerCpf = document.getElementById('ger_cpf_input');
                    const gerData = document.getElementById('ger_data_nascimento_input');
                    if (gerCpf) gerCpf.required = true;
                    if (gerData) gerData.required = true;
                    if (nomeInput) nomeInput.required = true;
                    if (emailInput) emailInput.required = true;
                    if (senhaInput) senhaInput.required = true;
                }
            });

            // --- Evento: Verificar CPF ---
            if (cpfInput) { 
                cpfInput.addEventListener('blur', function() {
                    const cpf = this.value.trim();
                    if (cpf === '') { if (cpfStatusMsg) cpfStatusMsg.innerHTML = ''; return; }
                    if (cpfStatusMsg) cpfStatusMsg.innerHTML = '<span class="text-info">Verificando CPF...</span>';
                    fetch('buscar_paciente_cpf.php?cpf=' + encodeURIComponent(cpf))
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'encontrado') {
                                if (nomeInput) { nomeInput.value = data.nome; nomeInput.readOnly = true; }
                                if (dataNascimentoInput) { dataNascimentoInput.value = data.data_nascimento; dataNascimentoInput.readOnly = true; }
                                if (cpfStatusMsg) cpfStatusMsg.innerHTML = '<span class="text-success">Cadastro encontrado! Preencha seu email e senha.</span>';
                            } else if (data.status === 'nao_encontrado') {
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (dataNascimentoInput) { dataNascimentoInput.value = ''; dataNascimentoInput.readOnly = false; }
                                if (cpfStatusMsg) cpfStatusMsg.innerHTML = '<span class="text-muted">Novo paciente. Preencha todos os campos.</span>';
                            } else if (data.status === 'erro') {
                                if (cpfStatusMsg) cpfStatusMsg.innerHTML = '<span class="text-danger">' + data.mensagem + '</span>';
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (dataNascimentoInput) { dataNascimentoInput.value = ''; dataNascimentoInput.readOnly = false; }
                            }
                        }).catch(error => { console.error('Erro no fetch:', error); if (cpfStatusMsg) cpfStatusMsg.innerHTML = '<span class="text-danger">Erro ao conectar com o servidor.</span>'; });
                });
            }

            // --- Evento: Verificar CRM ---
            if (crmInput) { 
                crmInput.addEventListener('blur', function() {
                    const crm = this.value.trim();
                    if (crm === '') { if (crmStatusMsg) crmStatusMsg.innerHTML = ''; return; }
                    if (crmStatusMsg) crmStatusMsg.innerHTML = '<span class="text-info">Verificando CRM...</span>';
                    fetch('buscar_medico_crm.php?crm=' + encodeURIComponent(crm))
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'encontrado') {
                                if (nomeInput) { nomeInput.value = data.nome; nomeInput.readOnly = true; }
                                if (especialidadeInput) { especialidadeInput.value = data.especialidade; especialidadeInput.readOnly = true; }
                                if (crmStatusMsg) crmStatusMsg.innerHTML = '<span class="text-success">Cadastro encontrado! Preencha seu email e senha.</span>';
                            } else if (data.status === 'nao_encontrado') {
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (especialidadeInput) { especialidadeInput.value = ''; especialidadeInput.readOnly = false; }
                                if (crmStatusMsg) crmStatusMsg.innerHTML = '<span class="text-muted">Novo médico. Preencha todos os campos.</span>';
                            } else if (data.status === 'erro') {
                                if (crmStatusMsg) crmStatusMsg.innerHTML = '<span class="text-danger">' + data.mensagem + '</span>';
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (especialidadeInput) { especialidadeInput.value = ''; especialidadeInput.readOnly = false; }
                            }
                        }).catch(error => { console.error('Erro no fetch:', error); if (crmStatusMsg) crmStatusMsg.innerHTML = '<span class="text-danger">Erro ao conectar com o servidor.</span>'; });
                });
            }
            
            // --- Evento: Verificar COREN ---
            if (corenInput) { 
                corenInput.addEventListener('blur', function() {
                    const coren = this.value.trim();
                    if (coren === '') { if (corenStatusMsg) corenStatusMsg.innerHTML = ''; return; }
                    if (corenStatusMsg) corenStatusMsg.innerHTML = '<span class="text-info">Verificando COREN...</span>';
                    fetch('buscar_enfermagem_coren.php?coren=' + encodeURIComponent(coren))
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'encontrado') {
                                if (nomeInput) { nomeInput.value = data.nome; nomeInput.readOnly = true; }
                                if (categoriaInput) { categoriaInput.value = data.categoria; categoriaInput.disabled = true; }
                                if (corenStatusMsg) corenStatusMsg.innerHTML = '<span class="text-success">Cadastro encontrado! Preencha seu email e senha.</span>';
                            } else if (data.status === 'nao_encontrado') {
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (categoriaInput) { categoriaInput.value = ''; categoriaInput.disabled = false; }
                                if (corenStatusMsg) corenStatusMsg.innerHTML = '<span class="text-muted">Novo profissional. Preencha todos os campos.</span>';
                            } else if (data.status === 'erro') {
                                if (corenStatusMsg) corenStatusMsg.innerHTML = '<span class="text-danger">' + data.mensagem + '</span>';
                                if (nomeInput) { nomeInput.value = ''; nomeInput.readOnly = false; }
                                if (categoriaInput) { categoriaInput.value = ''; categoriaInput.disabled = false; }
                            }
                        }).catch(error => { console.error('Erro no fetch:', error); if (corenStatusMsg) corenStatusMsg.innerHTML = '<span class="text-danger">Erro ao conectar com o servidor.</span>'; });
                });
            }
        });
    </script>
</body>
</html>