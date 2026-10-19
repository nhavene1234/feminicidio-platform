<?php
// public/index.php
require_once dirname(__DIR__) . '/Includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Denúncia contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
 <style>
.hero-image{
    background-image: url(../assets/images/44000573.jpg) ;
    background-size: cover;
    background-position:center;
    width: 100%; 
    height: 350px; 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white;
}
 </style>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="index.php">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi
                    </a>
                </div>
                <div class="nav-menu">
                    <!-- LINKS CORRETOS: -->
                    <a href="make-complaint.php" class="nav-link">Fazer Denúncia</a>
                    <a href="check-status.php" class="nav-link">Verificar Status</a>
                    <a href="../agents/login.php" class="nav-link">Área de Agentes</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Combate ao Feminicídio em Moçambique</h1>
                <p>Denuncie casos de violência contra a mulher de forma rápida e segura. Sua voz pode salvar vidas.</p>
                <div class="hero-buttons">
                    <!-- BOTÕES CORRETOS: -->
                    <a href="make-complaint.php" class="btn btn-primary">Fazer Denúncia</a>
                    <a href="check-status.php" class="btn btn-secondary">Acompanhar Denúncia</a>
                </div>
            </div>
            <div class="hero-image">
                <div style="" class="hero-imaged">
                    <i class="fas fa-shield-heart" style="font-size: 8rem;"></i>
                </div>
            </div>
        </section>

        <!-- ... resto do código ... -->
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>DenúnciaFemi</h3>
                    <p>Sistema de denúncia e resposta rápida contra feminicídio em Moçambique.</p>
                </div>
                <div class="footer-section">
                    <h3>Links Rápidos</h3>
                    <ul>
                        <!-- LINKS CORRETOS NO FOOTER: -->
                        <li><a href="make-complaint.php">Fazer Denúncia</a></li>
                        <li><a href="check-status.php">Verificar Status</a></li>
                        <li><a href="../agents/login.php">Área de Agentes</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contato</h3>
                    <p>Email: nelsonnhavenee@gmail.com</p>
                    <p>Telefone: +258 850604771</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>