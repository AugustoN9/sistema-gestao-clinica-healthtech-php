<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Conta - Clínica HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Crie a sua Conta de Paciente</h3>

                        <?php if(isset($_GET['erro'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_GET['erro']) ?></div>
                        <?php endif; ?>

                        <form action="processa_registro.php" method="POST">
                            <h5>Dados Pessoais</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="nome" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone">
                                </div>
                            </div>

                            <hr>

                            <h5>Dados de Acesso</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="email" class="form-label">Email (será o seu login)</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="senha" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Criar Conta</button>
                                <a href="login.php" class="btn btn-secondary">Já tenho uma conta</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>