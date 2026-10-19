<?php
// includes/config.php - VERSÃO POSTGRESQL (NEON / RENDER)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tenta ler as variáveis de ambiente do Render, senão usa os padrões locais
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'feminicidio_db'); 
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); 

try {
    // Configura o DSN para PostgreSQL e força o uso de SSL requerido pelo Neon
    $dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";sslmode=require";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Caso falhe localmente (se o seu Postgres local não exigir SSL), tenta sem SSL
    try {
        $dsn_local = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn_local, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e_local) {
        die("Erro na conexão com o banco de dados: " . $e_local->getMessage());
    }
}

// Definir base path de forma dinâmica para não quebrar links no Render
$base_path = (getenv('RENDER') === 'true') ? '' : '/feminicidio-platform';
define('BASE_PATH', $base_path);
?>
