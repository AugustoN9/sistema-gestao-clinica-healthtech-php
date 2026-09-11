<?php
// Inicia a sessão para podermos aceder às variáveis de sessão em todas as páginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================== CÓDIGO DE TEMA DINÂMICO ==================
// Garante o caminho correto para o helper, independentemente de onde o header.php seja incluído
require_once __DIR__ . '/helpers/tema_helper.php';

$usuario_tipo = $_SESSION['usuario_tipo'] ?? 'default';
$tema = getTemaClasses($usuario_tipo);
// ================== FIM DO CÓDIGO DE TEMA ==================


// Lógica para definir o link da "home" dinamicamente com base no tipo de utilizador
$link_home = '/auth/login.php'; 
if (isset($_SESSION['usuario_tipo'])) {
    switch ($_SESSION['usuario_tipo']) {
        case 'admin':
        case 'gerente':
            // Para admin/gerente, a home é a lista de pacientes
            $link_home = '/pacientes/pacientes.php';
            break;
        case 'paciente':
            $link_home = '/area_paciente.php';
            break;
        case 'medico':
            $link_home = '/area_medico.php';
            break;
        case 'enfermagem':
             $link_home = '/area_enfermagem.php';
             break;
    }
}
?>
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google-site-verification" content="sgTzCGaFc_GkbR8jbovRzZztSKuQ_anAC06eEOjbPmY" />
    <title>Clínica HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
	<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <style>
        /* Cores do Body (Fundo da Tela) */
        .admin-bg { background-color: #d8d8d8 !important; } /* Cinza 25% */
        .gerente-bg { background-color: #f0f0f0 !important; } /* Cinza 10% */

        /* Cor do Header (navbar-custom usa a cor dinâmica do helper PHP) */
        .navbar-custom { 
            background-color: <?= $tema['header_color'] ?> !important;
        }
    </style>
    </head>
  
  <body class="d-flex flex-column min-vh-100 <?= $tema['body_class'] ?>">

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm navbar-custom">
      <div class="container">
        <a class="navbar-brand" href="<?= $link_home ?>">
            <i class="fas fa-heartbeat"></i>
            <strong>HealthTech</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                
                <?php // MENU PRINCIPAL PARA ADMIN/GERENTE ?>
                <?php if ($_SESSION['usuario_tipo'] == 'admin' || $_SESSION['usuario_tipo'] == 'gerente'): ?>
                    
                    <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle" href="#" id="navbarCadastros" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-database me-1"></i> CADASTROS
                      </a>
                      <ul class="dropdown-menu" aria-labelledby="navbarCadastros">
                        <li><a class="dropdown-item" href="/pacientes/pacientes.php"><i class="fas fa-user-injured me-2"></i> Pacientes</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/medicos/medicos.php"><i class="fas fa-user-md me-2"></i> Médicos</a></li>
                        <li><a class="dropdown-item" href="/enfermagem/enfermagem.php"><i class="fas fa-user-nurse me-2"></i> Enfermagem</a></li>
                        
                        <?php if ($_SESSION['usuario_tipo'] == 'admin'): ?>
                             <li><hr class="dropdown-divider"></li>
                             <li><a class="dropdown-item" href="/admin/gerenciar_equipe_adm.php"><i class="fas fa-users-cog me-2"></i> Equipe Administrativa</a></li>
                        <?php endif; ?>
                      </ul>
                    </li>

                    <li class="nav-item">
                      <a class="nav-link" href="/consultas/consultas.php">
                          <i class="fas fa-file-medical me-1"></i> OPERAÇÕES
                      </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/painel_disponibilidade_medica.php">
                            <i class="fas fa-calendar-alt me-1"></i> AGENDA
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php // ANÁLISES & GESTÃO (Antigo GERENCIAMENTO) - APENAS ADMIN ?>
                <?php if ($_SESSION['usuario_tipo'] == 'admin'): ?>
                    <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle" href="#" id="navbarAnalises" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-line me-1"></i> ANÁLISES & GESTÃO
                      </a>
                      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarAnalises">
                        <li><a class="dropdown-item" href="/admin_painel.php"><i class="fas fa-cogs me-2"></i> Painel de Controlo</a></li>
                        <hr class="dropdown-divider">
                        <li><a class="dropdown-item" href="/admin/gerenciar_exames.php"><i class="fas fa-flask me-2"></i> Gestão de Exames</a></li>
                        <li><a class="dropdown-item" href="/admin/gerenciar_laboratorios.php"><i class="fas fa-microscope me-2"></i> Gestão de Laboratórios</a></li>
                        <li><a class="dropdown-item" href="/admin/vincular_exames_laboratorio.php"><i class="fas fa-link me-2"></i> Vincular Exames</a></li>
                        <hr class="dropdown-divider">
                        <li><a class="dropdown-item" href="/dashboard_consultas.php"><i class="fas fa-stethoscope me-2"></i> Dashboard Consultas</a></li>
                        <li><a class="dropdown-item" href="/infovacinacao.php"><i class="fas fa-syringe me-2"></i> Dashboard Vacinação</a></li>
                      </ul>
                    </li>
                <?php endif; ?>
                
                
                <?php // MENU DO USUÁRIO (PERFIL/LOGOUT) ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Perfil') ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                    <li><a class="dropdown-item" href="<?= $link_home ?>">Meu Painel</a></li>
                    
                    <?php // Links de Perfil para Admin/Gerente ?>
                    <?php if ($_SESSION['usuario_tipo'] == 'admin' || $_SESSION['usuario_tipo'] == 'gerente'): ?>
                         <li><a class="dropdown-item" href="/admin/editar_admin.php?id=<?= $_SESSION['id_referencia'] ?>">Perfil Administrativo</a></li>
                         <li><a class="dropdown-item" href="/auth/editar_perfil_usuario.php">Editar Conta (Email/Senha)</a></li>
                    <?php endif; ?>
                    
                    <?php // Links de Perfil para Médico ?>
                    <?php if ($_SESSION['usuario_tipo'] == 'medico'): ?>
                        <li><a class="dropdown-item" href="/medicos/editar_medico.php?id=<?= $_SESSION['id_referencia'] ?>">Perfil Profissional</a></li>
                        <li><a class="dropdown-item" href="/auth/editar_perfil_usuario.php">Editar Conta (Email/Senha)</a></li>
                    <?php endif; ?>

                    <?php // Links de Perfil para Paciente ?>
                    <?php if ($_SESSION['usuario_tipo'] == 'paciente'): ?>
                        <li><a class="dropdown-item" href="/pacientes/editar_paciente.php?id=<?= $_SESSION['id_referencia'] ?>">Perfil Pessoal</a></li>
                        <li><a class="dropdown-item" href="/auth/editar_perfil_usuario.php">Editar Conta (Email/Senha)</a></li>
                    <?php endif; ?>
                    
                    <?php // Links de Perfil para Enfermagem ?>
                    <?php if ($_SESSION['usuario_tipo'] == 'enfermagem'): ?>
                        <li><a class="dropdown-item" href="/enfermagem/editar_perfil_enfermagem.php">Perfil Profissional</a></li>
                        <li><a class="dropdown-item" href="/auth/editar_perfil_usuario.php">Editar Conta (Email/Senha)</a></li>
                    <?php endif; ?>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/auth/logout.php">Sair</a></li>
                  </ul>
                </li>
            <?php endif; ?>
            </ul>
        </div>
      </div>
    </nav>
    <main class="container mt-4">
