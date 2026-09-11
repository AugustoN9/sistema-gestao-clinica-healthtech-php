<?php
/*
 * htdocs/index.php
 * Este script serve como a "porta de entrada" do site.
 * Sua única função é redirecionar o usuário para a página principal,
 * que é a lista de pacientes.
 */

header("Location: /auth/login.php");
exit();

?>