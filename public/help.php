<?php require_once '../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda e Suporte - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .help-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .faq-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .faq-question {
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            display: none;
            padding-top: 1rem;
            color: var(--gray);
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .contact-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .contact-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
    </style>
</head>
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
                    <a href="index.php" class="nav-link">Página Inicial</a>
                    <a href="make-complaint.php" class="nav-link">Fazer Denúncia</a>
                    <a href="check-status.php" class="nav-link">Verificar Status</a>
                    <a href="../agents/login.php" class="nav-link">Área de Agentes</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="help-section">
                <h1>Ajuda e Suporte</h1>
                <p>Encontre respostas para suas perguntas e saiba como obter ajuda</p>
            </div>

            <div class="help-section">
                <h2>Perguntas Frequentes (FAQ)</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Como faço uma denúncia?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Para fazer uma denúncia:</p>
                        <ol>
                            <li>Clique em "Fazer Denúncia" no menu principal</li>
                            <li>Preencha o formulário com os detalhes do ocorrido</li>
                            <li>Escolha entre denúncia identificada ou anônima</li>
                            <li>Após enviar, você receberá um código de acompanhamento</li>
                        </ol>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Posso fazer denúncia anônima?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Sim! O sistema permite denúncias anônimas. Basta marcar a opção "Fazer denúncia anônima" no formulário. Sua identidade será preservada.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Como acompanho minha denúncia?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Use o código recebido após fazer a denúncia para:</p>
                        <ul>
                            <li>Acessar "Verificar Status" no menu</li>
                            <li>Digitar seu código</li>
                            <li>Visualizar o andamento do caso</li>
                            <li>Ver as ações tomadas pelos agentes</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Quanto tempo leva para uma denúncia ser atendida?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>O tempo de resposta varia conforme a gravidade do caso e a disponibilidade dos agentes. Casos de emergência são priorizados.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        O que fazer em caso de emergência?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Em situações de perigo imediato:</p>
                        <ul>
                            <li><strong>Ligue para a Polícia: 119</strong></li>
                            <li><strong>Ligue para a Ambulância: 117</strong></li>
                            <li>Procure um local seguro</li>
                            <li>Busque ajuda de vizinhos ou familiares</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="help-section">
                <h2>Contactos de Emergência</h2>
                
                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h3>Polícia</h3>
                        <p><strong>119</strong></p>
                        <p>Emergências policiais</p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-ambulance"></i>
                        </div>
                        <h3>Ambulância</h3>
                        <p><strong>117</strong></p>
                        <p>Emergências médicas</p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Bombeiros</h3>
                        <p><strong>198</strong></p>
                        <p>Incêndios e resgates</p>
                    </div>
                </div>
            </div>

            <div class="help-section">
                <h2>Suporte Técnico</h2>
                <p>Problemas com o sistema? Entre em contacto com nossa equipe técnica:</p>
                
                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email</h3>
                        <p>suporte@denunciafemi.gov.mz</p>
                        <p>Resposta em 24 horas</p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Telefone</h3>
                        <p>+258 84 123 4567</p>
                        <p>Segunda a Sexta, 8h-17h</p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Chat Online</h3>
                        <p>Disponível no horário comercial</p>
                        <button class="btn btn-primary" style="margin-top: 1rem;">
                            Iniciar Chat
                        </button>
                    </div>
                </div>
            </div>

            <div class="help-section">
                <h2>Recursos Adicionais</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                        <h4><i class="fas fa-download"></i> Manuais e Guias</h4>
                        <ul>
                            <li><a href="#">Guia da Vítima (PDF)</a></li>
                            <li><a href="#">Manual do Agente (PDF)</a></li>
                            <li><a href="#">Procedimentos de Emergência</a></li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                        <h4><i class="fas fa-balance-scale"></i> Apoio Legal</h4>
                        <ul>
                            <li><a href="#">Direitos da Vítima</a></li>
                            <li><a href="#">Lei contra Violência Doméstica</a></li>
                            <li><a href="#">Recursos Judiciais</a></li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                        <h4><i class="fas fa-heart"></i> Apoio Psicológico</h4>
                        <ul>
                            <li><a href="#">Linha de Apoio Emocional</a></li>
                            <li><a href="#">Grupos de Apoio</a></li>
                            <li><a href="#">Consultas Online</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
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
                        <li><a href="make-complaint.php">Fazer Denúncia</a></li>
                        <li><a href="check-status.php">Verificar Status</a></li>
                        <li><a href="help.php">Ajuda e Suporte</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Emergência</h3>
                    <p>Polícia: 119</p>
                    <p>Ambulância: 117</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.style.display === 'block') {
                answer.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            } else {
                answer.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            }
        }
        
        // Abrir primeira FAQ por padrão
        document.addEventListener('DOMContentLoaded', function() {
            const firstQuestion = document.querySelector('.faq-question');
            if (firstQuestion) {
                toggleFAQ(firstQuestion);
            }
        });
    </script>
</body>
</html>