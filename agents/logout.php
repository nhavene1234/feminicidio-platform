<?php
// agents/logout.php
require_once '../includes/config.php';

// Destruir a sessão
session_destroy();

// Redirecionar para o login
header('Location: login.php');
exit();
?>