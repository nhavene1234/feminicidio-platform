<?php
require_once '../includes/auth.php';
if (!isAdmin()) {
    header('Location: ../agents/login.php');
    exit();
}

// Buscar configurações
$settings = [
    'system_name' => 'DenúnciaFemi',
    'contact_email' => 'info@denunciafemi.gov.mz',
    'contact_phone' => '+258 84 123 4567',
    'emergency_numbers' => 'Polícia: 119, Ambulância: 117',
    'max_complaint_age' => '30',
    'auto_assign_cases' => '1'
];

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aqui você salvaria no banco de dados
    $success = "Configurações atualizadas com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="dashboard.php">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi - Admin
                    </a>
                </div>
                <div class="nav-menu">
                    <span style="color: white; margin-right: 1rem;">
                        <?php echo $_SESSION['user_name']; ?>
                        <?php echo getUserTypeBadge($_SESSION['user_type']); ?>
                    </span>
                    <a href="../agents/dashboard.php" class="nav-link">Ver Denúncias</a>
                    <a href="../agents/logout.php" class="nav-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="admin-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage-agents.php">Gerir Agentes</a></li>
                    <li><a href="reports.php">Relatórios</a></li>
                    <li><a href="settings.php" class="active">Configurações</a></li>
                </ul>
            </div>

            <div class="dashboard-header">
                <h1>Configurações do Sistema</h1>
                <p>Gerencie as configurações gerais do sistema</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <h3>Informações Gerais</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nome do Sistema</label>
                        <input type="text" name="system_name" class="form-control" value="<?php echo $settings['system_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email de Contacto</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo $settings['contact_email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefone de Contacto</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo $settings['contact_phone']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Números de Emergência</label>
                        <textarea name="emergency_numbers" class="form-control" rows="3" required><?php echo $settings['emergency_numbers']; ?></textarea>
                        <small class="form-text">Separe os números com vírgula</small>
                    </div>

                    <h3>Configurações de Denúncias</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Dias Máximos para Denúncias</label>
                        <input type="number" name="max_complaint_age" class="form-control" value="<?php echo $settings['max_complaint_age']; ?>" min="1" max="365">
                        <small class="form-text">Número máximo de dias que uma denúncia pode ter</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="auto_assign_cases" value="1" <?php echo $settings['auto_assign_cases'] ? 'checked' : ''; ?>>
                            Atribuição Automática de Casos
                        </label>
                        <small class="form-text">Atribuir automaticamente novos casos aos agentes disponíveis</small>
                    </div>

                    <h3>Configurações de Segurança</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Tempo de Sessão (minutos)</label>
                        <input type="number" name="session_timeout" class="form-control" value="60" min="15" max="1440">
                        <small class="form-text">Tempo de inatividade antes do logout automático</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="force_ssl" value="1" checked>
                            Forçar Conexão Segura (HTTPS)
                        </label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Restaurar Padrões
                        </button>
                    </div>
                </form>
            </div>

            <div class="form-container" style="margin-top: 2rem;">
                <h3>Ferramentas do Sistema</h3>
                
                <div class="action-buttons" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="backupDatabase()" class="btn btn-info">
                        <i class="fas fa-database"></i> Backup do Banco de Dados
                    </button>
                    
                    <button onclick="clearCache()" class="btn btn-warning">
                        <i class="fas fa-broom"></i> Limpar Cache
                    </button>
                    
                    <button onclick="systemDiagnostics()" class="btn btn-secondary">
                        <i class="fas fa-stethoscope"></i> Diagnóstico do Sistema
                    </button>
                </div>
                
                <div id="system-info" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                    <h4>Informações do Servidor</h4>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>MySQL Version:</strong> <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></p>
                    <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function backupDatabase() {
            if (confirm('Deseja criar um backup do banco de dados?')) {
                alert('Backup iniciado... Esta funcionalidade será implementada.');
                // Aqui você implementaria o backup
            }
        }
        
        function clearCache() {
            if (confirm('Deseja limpar o cache do sistema?')) {
                alert('Cache limpo com sucesso!');
            }
        }
        
        function systemDiagnostics() {
            alert('Executando diagnóstico do sistema...');
            // Aqui você implementaria o diagnóstico
        }
    </script>
</body>
</html>