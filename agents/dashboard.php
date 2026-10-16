<?php
// agents/dashboard.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Buscar denúncias para o dashboard
try {
    // Estatísticas gerais
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints");
    $total_complaints = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $pending_stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'pendente'");
    $pending_complaints = $pending_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $progress_stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'andamento'");
    $progress_complaints = $progress_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $resolved_stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'resolvido'");
    $resolved_complaints = $resolved_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Denúncias recentes
    $recent_stmt = $pdo->prepare("
        SELECT c.*, u.name as agent_name 
        FROM complaints c 
        LEFT JOIN users u ON c.assigned_agent_id = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $recent_stmt->execute();
    $recent_complaints = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .complaints-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .user-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .badge-admin { background: #e91e63; color: white; }
        .badge-police { background: #2196F3; color: white; }
        .badge-hospital { background: #4CAF50; color: white; }
        .badge-inas { background: #FF9800; color: white; }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
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
                        <i class="fas fa-user"></i> 
                        <?php echo $_SESSION['user_name']; ?>
                        <span class="user-badge badge-<?php echo $_SESSION['user_type']; ?>">
                            <?php 
                            $user_types = [
                                'admin' => 'Administrador',
                                'police' => 'Polícia', 
                                'hospital' => 'Hospital',
                                'inas' => 'INAS'
                            ];
                            echo $user_types[$_SESSION['user_type']];
                            ?>
                        </span>
                    </span>
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="nav-link"><i class="fas fa-cog"></i> Admin</a>
                    <?php endif; ?>
                    <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Perfil</a>
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <!-- Seção de Boas-vindas -->
            <div class="welcome-section">
                <h1>Bem-vindo(a), <?php echo $_SESSION['user_name']; ?>! 👋</h1>
                <p><?php echo $_SESSION['user_post']; ?> | <?php echo date('d/m/Y - H:i'); ?></p>
            </div>

            <div class="dashboard-header">
                <h2><i class="fas fa-chart-bar"></i> Visão Geral do Sistema</h2>
                <p>Estatísticas e atividades recentes</p>
            </div>

            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_complaints; ?></div>
                    <div>Total de Denúncias</div>
                    <small style="color: var(--gray);">Desde o início</small>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_complaints; ?></div>
                    <div>Pendentes</div>
                    <small style="color: var(--warning);">Aguardando ação</small>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $progress_complaints; ?></div>
                    <div>Em Andamento</div>
                    <small style="color: var(--info);">Sendo tratadas</small>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $resolved_complaints; ?></div>
                    <div>Resolvidas</div>
                    <small style="color: var(--success);">Concluídas</small>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="dashboard-header">
                <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                    <a href="view-complaint.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Ver Todas Denúncias
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-user-cog"></i> Meu Perfil
                    </a>
                    <a href="notifications.php" class="btn btn-info">
                        <i class="fas fa-bell"></i> Notificações
                    </a>
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="../admin/manage-agents.php" class="btn btn-warning">
                        <i class="fas fa-users-cog"></i> Gerir Agentes
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Denúncias Recentes -->
            <div class="complaints-table">
                <div style="padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;"><i class="fas fa-clock"></i> Denúncias Recentes</h3>
                    <a href="view-complaint.php" class="btn btn-sm btn-primary">Ver Todas</a>
                </div>
                
                <?php if ($recent_complaints): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Província</th>
                            <th>Status</th>
                            <th>Agente</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_complaints as $complaint): ?>
                        <tr>
                            <td style="font-weight: bold;"><?php echo $complaint['complaint_code']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($complaint['created_at'])); ?></td>
                            <td>
                                <?php 
                                $type_badge = $complaint['complaint_type'] == 'intimo' ? 
                                    '<span style="background: #e91e63; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">Íntimo</span>' :
                                    '<span style="background: #673ab7; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">Não Íntimo</span>';
                                echo $type_badge;
                                ?>
                            </td>
                            <td><?php echo $complaint['province']; ?></td>
                            <td>
                                <?php 
                                $status_badges = [
                                    'pendente' => '<span style="background: #ffc107; color: black; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">Pendente</span>',
                                    'andamento' => '<span style="background: #17a2b8; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">Em Andamento</span>',
                                    'resolvido' => '<span style="background: #28a745; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">Resolvido</span>'
                                ];
                                echo $status_badges[$complaint['status']];
                                ?>
                            </td>
                            <td><?php echo $complaint['agent_name'] ?: 'Não atribuído'; ?></td>
                            <td class="action-buttons">
                                <a href="view-complaint.php?code=<?php echo $complaint['complaint_code']; ?>" class="btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: var(--gray);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h4>Nenhuma denúncia encontrada</h4>
                    <p>Não há denúncias registradas no sistema ainda.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi - Sistema de Denúncia contra Feminicídio</p>
            </div>
        </div>
    </footer>

    <script>
        // Atualizar a hora em tempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('pt-PT', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('pt-PT');
            
            // Atualizar o elemento de tempo se existir
            const timeElement = document.querySelector('.welcome-section p');
            if (timeElement) {
                timeElement.textContent = '<?php echo $_SESSION["user_post"]; ?> | ' + dateString + ' - ' + timeString;
            }
        }
        
        // Atualizar a cada segundo
        setInterval(updateTime, 1000);
        updateTime(); // Chamar imediatamente
    </script>
</body>
</html>