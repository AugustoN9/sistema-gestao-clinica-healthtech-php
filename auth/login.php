<?php
session_start();

// =================================================================
// NOVO: TRATAMENTO DE ERROS E DADOS VIA SESSÃO
// =================================================================
$mensagem_erro = $_SESSION['login_erro'] ?? '';
$email_digitado = $_SESSION['email_digitado'] ?? '';

// Limpa as variáveis de sessão para que a mensagem não persista após o refresh
unset($_SESSION['login_erro']);
unset($_SESSION['email_digitado']);

// Código de redirecionamento se já estiver logado (Mantenha o seu código original aqui, se tiver)
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['usuario_tipo'] == 'admin') header('Location: ../admin_painel.php');
    if ($_SESSION['usuario_tipo'] == 'gerente') header('Location: ../pacientes/pacientes.php');
    if ($_SESSION['usuario_tipo'] == 'paciente') header('Location: ../area_paciente.php');
    if ($_SESSION['usuario_tipo'] == 'medico') header('Location: ../area_medico.php');
    if ($_SESSION['usuario_tipo'] == 'enfermagem') header('Location: ../area_enfermagem.php');
    exit();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Clínica HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; background-image: url("../assets/images/fundoClinica.jpg"); bacground-repeat: no-repeat; background-size: cover; }
        .login-card { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            
            <div class="text-center mb-4">
                <a class="navbar-brand text-primary" href="login.php" style="font-size: 2rem; text-decoration: none;">
                    <i class="fas fa-heartbeat"></i>
                    <strong>HealthTech</strong>
                </a>
            </div>
            
            <?php if ($mensagem_erro): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($mensagem_erro) ?>
                    <?php 
                    // Se o erro for de email não encontrado, adiciona a sugestão de cadastro
                    if (strpos($mensagem_erro, 'Email não encontrado') !== false): ?>
                        <br><a href="registro.php" class="alert-link">Crie uma conta agora!</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form action="processa_login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           value="<?= htmlspecialchars($email_digitado) ?>" autofocus>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
            <hr>
            <div class="text-center">
                <p class="mb-0">Não tem uma conta? <a href="registro.php">Crie uma aqui</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>