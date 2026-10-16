<?php
require_once '../includes/auth.php';
checkAuth();

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Usuário não encontrado!");
    }
} catch(PDOException $e) {
    die("Erro ao carregar perfil: " . $e->getMessage());
}

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $post = $_POST['post'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    try {
        // Verificar senha atual se for alterar senha
        if ($new_password && !password_verify($current_password, $user['password'])) {
            $error = "Senha atual incorreta!";
        } else {
            // Atualizar dados básicos
            if ($new_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, post = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $post, $password_hash, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, post = ? WHERE id = ?");
                $stmt->execute([$name, $email, $post, $_SESSION['user_id']]);
            }
            
            // Atualizar sessão
            $_SESSION['user_name'] = $name;
            $_SESSION['user_post'] = $post;
            
            $success = "Perfil atualizado com sucesso!";
            
            // Recarregar dados do usuário
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        $error = "Erro ao atualizar perfil: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .tab-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .tab-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            background: white;
            border-bottom: 2px solid var(--primary);
        }
        
        .tab-content {
            padding: 2rem;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="dashboard.php">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi
                    </a>
                </div>
                <div class="nav-menu">
                    <span style="color: white; margin-right: 1rem;">
                        <?php echo $_SESSION['user_name']; ?>
                        <?php echo getUserTypeBadge($_SESSION['user_type']); ?>
                    </span>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="profile.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h1><?php echo $user['name']; ?></h1>
                    <p><?php echo getUserTypeBadge($user['user_type']); ?> • <?php echo $user['post']; ?></p>
                    <p>Membro desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="profile-stats">
                <?php
                // Buscar estatísticas do usuário
                $user_stats = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_cases,
                        SUM(CASE WHEN status = 'andamento' THEN 1 ELSE 0 END) as active_cases,
                        SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolved_cases
                    FROM complaints 
                    WHERE assigned_agent_id = ?
                ");
                $user_stats->execute([$_SESSION['user_id']]);
                $stats = $user_stats->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_cases'] ?: '0'; ?></div>
                    <div>Total de Casos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_cases'] ?: '0'; ?></div>
                    <div>Casos Ativos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['resolved_cases'] ?: '0'; ?></div>
                    <div>Casos Resolvidos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total = $stats['total_cases'];
                        $resolved = $stats['resolved_cases'];
                        echo $total > 0 ? round(($resolved / $total) * 100) : '0';
                        ?>%
                    </div>
                    <div>Taxa de Resolução</div>
                </div>
            </div>

            <div class="tab-container">
                <div class="tab-header">
                    <button class="tab-button active" onclick="openTab('edit-profile')">
                        <i class="fas fa-user-edit"></i> Editar Perfil
                    </button>
                    <button class="tab-button" onclick="openTab('change-password')">
                        <i class="fas fa-lock"></i> Alterar Senha
                    </button>
                    <button class="tab-button" onclick="openTab('activity')">
                        <i class="fas fa-chart-line"></i> Minha Atividade
                    </button>
                </div>

                <div class="tab-content">
                    <!-- Aba Editar Perfil -->
                    <div id="edit-profile" class="tab-pane active">
                        <h3>Informações Pessoais</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Posto/Localização</label>
                                <input type="text" name="post" class="form-control" value="<?php echo htmlspecialchars($user['post']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Tipo de Usuário</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['user_type']); ?>" disabled>
                                <small class="form-text">O tipo de usuário não pode ser alterado.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Atualizar Perfil
                            </button>
                        </form>
                    </div>

                    <!-- Aba Alterar Senha -->
                    <div id="change-password" class="tab-pane">
                        <h3>Alterar Senha</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="new_password" class="form-control">
                                <small class="form-text">Deixe em branco se não quiser alterar a senha.</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Alterar Senha
                            </button>
                        </form>
                    </div>

                    <!-- Aba Minha Atividade -->
                    <div id="activity" class="tab-pane">
                        <h3>Minha Atividade Recente</h3>
                        <?php
                        $recent_activity = $pdo->prepare("
                            SELECT cu.*, c.complaint_code 
                            FROM complaint_updates cu 
                            JOIN complaints c ON cu.complaint_id = c.id 
                            WHERE cu.user_id = ? 
                            ORDER BY cu.created_at DESC 
                            LIMIT 10
                        ");
                        $recent_activity->execute([$_SESSION['user_id']]);
                        $activities = $recent_activity->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($activities):
                        ?>
                            <div class="activity-timeline">
                                <?php foreach($activities as $activity): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-header">
                                            <strong>Denúncia #<?php echo $activity['complaint_code']; ?></strong>
                                            <small><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                        <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>Nenhuma atividade registrada ainda.</p>
                        <?php endif; ?>
                    </div>
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
        function openTab(tabName) {
            // Esconder todas as abas
            const tabPanes = document.getElementsByClassName('tab-pane');
            for (let pane of tabPanes) {
                pane.classList.remove('active');
            }
            
            // Remover active de todos os botões
            const tabButtons = document.getElementsByClassName('tab-button');
            for (let button of tabButtons) {
                button.classList.remove('active');
            }
            
            // Mostrar aba selecionada
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        // Validação de senha
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const newPassword = document.querySelector('input[name="new_password"]').value;
                    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
                    
                    if (newPassword && newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('As senhas não coincidem!');
                    }
                });
            }
        });
    </script>
</body>
</html>