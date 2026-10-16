<?php
require_once '../includes/auth.php';
if (!isAdmin()) {
    header('Location: ../agents/login.php');
    exit();
}

// Buscar estatísticas REAIS da base de dados
try {
    // Total de agentes
    $total_agents = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1")->fetchColumn();
    
    // Agentes por tipo
    $agents_by_type = $pdo->query("SELECT user_type, COUNT(*) as count FROM users WHERE is_active = 1 GROUP BY user_type")->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de denúncias
    $total_complaints = $pdo->query("SELECT COUNT(*) as total FROM complaints")->fetchColumn();
    
    // Denúncias por status
    $complaints_by_status = $pdo->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    
    // Denúncias por província (top 5)
    $complaints_by_province = $pdo->query("SELECT province, COUNT(*) as count FROM complaints GROUP BY province ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Denúncias dos últimos 7 dias
    $recent_complaints = $pdo->query("SELECT COUNT(*) as count FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Denúncias por mês (últimos 6 meses)
    $monthly_stats = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%b %Y') as month_name,
            COUNT(*) as count 
        FROM complaints 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY month DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Agentes recentes (últimos 7 dias)
    $recent_agents = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
} catch(PDOException $e) {
    die("Erro ao carregar estatísticas: " . $e->getMessage());
}

// Calcular percentagens
$pending_percentage = $total_complaints > 0 ? round(($complaints_by_status[array_search('pendente', array_column($complaints_by_status, 'status'))]['count'] / $total_complaints) * 100) : 0;
$progress_percentage = $total_complaints > 0 ? round(($complaints_by_status[array_search('andamento', array_column($complaints_by_status, 'status'))]['count'] / $total_complaints) * 100) : 0;
$resolved_percentage = $total_complaints > 0 ? round(($complaints_by_status[array_search('resolvido', array_column($complaints_by_status, 'status'))]['count'] / $total_complaints) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .admin-nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
        }
        
        .admin-nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: var(--primary);
            color: white;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-trend {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .trend-up { background: #d4edda; color: #155724; }
        .trend-down { background: #f8d7da; color: #721c24; }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .progress-pending { background: #ffc107; }
        .progress-progress { background: #17a2b8; }
        .progress-resolved { background: #28a745; }
    </style>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="manage-agents.php">Gerir Agentes</a></li>
                    <li><a href="reports.php">Relatórios</a></li>
                    <li><a href="settings.php">Configurações</a></li>
                </ul>
            </div>

            <!-- Cartão de Boas-vindas -->
            <div class="welcome-card">
                <h1>Bem-vindo, <?php echo $_SESSION['user_name']; ?>! 👋</h1>
                <p>Painel de Administração - Visão Geral do Sistema</p>
                <div style="display: flex; gap: 2rem; margin-top: 1.5rem; flex-wrap: wrap;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem;"><?php echo $total_agents; ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Agentes Ativos</p>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 2rem;"><?php echo $total_complaints; ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Total Denúncias</p>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 2rem;"><?php echo $recent_complaints; ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Últimos 7 Dias</p>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Principais -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_agents; ?></div>
                    <h3>Total de Agentes</h3>
                    <p>Usuários ativos no sistema</p>
                    <?php if ($recent_agents > 0): ?>
                    <span class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +<?php echo $recent_agents; ?> novos
                    </span>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_complaints; ?></div>
                    <h3>Total de Denúncias</h3>
                    <p>Desde o início do sistema</p>
                    <?php if ($recent_complaints > 0): ?>
                    <span class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +<?php echo $recent_complaints; ?> esta semana
                    </span>
                    <?php endif; ?>
                </div>

                <?php 
                // Encontrar contagens por status
                $pending_count = 0;
                $progress_count = 0;
                $resolved_count = 0;
                
                foreach($complaints_by_status as $status) {
                    if ($status['status'] == 'pendente') $pending_count = $status['count'];
                    if ($status['status'] == 'andamento') $progress_count = $status['count'];
                    if ($status['status'] == 'resolvido') $resolved_count = $status['count'];
                }
                ?>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <h3>Denúncias Pendentes</h3>
                    <p>Aguardando ação</p>
                    <div class="progress-bar">
                        <div class="progress-fill progress-pending" style="width: <?php echo $pending_percentage; ?>%"></div>
                    </div>
                    <small><?php echo $pending_percentage; ?>% do total</small>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $resolved_count; ?></div>
                    <h3>Casos Resolvidos</h3>
                    <p>Concluídos com sucesso</p>
                    <div class="progress-bar">
                        <div class="progress-fill progress-resolved" style="width: <?php echo $resolved_percentage; ?>%"></div>
                    </div>
                    <small><?php echo $resolved_percentage; ?>% do total</small>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="chart-row">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição de Agentes</h3>
                    <canvas id="agentsChart" width="400" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> Status das Denúncias</h3>
                    <canvas id="statusChart" width="400" height="300"></canvas>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-container">
                    <h3><i class="fas fa-map-marker-alt"></i> Top 5 Províncias</h3>
                    <canvas id="provinceChart" width="400" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> Evolução Mensal</h3>
                    <canvas id="monthlyChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="chart-container">
                <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="manage-agents.php" class="btn btn-primary" style="text-align: center; padding: 1.5rem;">
                        <i class="fas fa-users-cog" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><br>
                        Gerir Agentes
                    </a>
                    <a href="reports.php" class="btn btn-info" style="text-align: center; padding: 1.5rem;">
                        <i class="fas fa-chart-bar" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><br>
                        Ver Relatórios
                    </a>
                    <a href="../agents/dashboard.php" class="btn btn-success" style="text-align: center; padding: 1.5rem;">
                        <i class="fas fa-eye" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><br>
                        Ver Denúncias
                    </a>
                    <a href="settings.php" class="btn btn-warning" style="text-align: center; padding: 1.5rem;">
                        <i class="fas fa-cog" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><br>
                        Configurações
                    </a>
                </div>
            </div>
        </div>
    </main>

   

    <script>
        // Gráfico de Distribuição de Agentes
        const agentsCtx = document.getElementById('agentsChart').getContext('2d');
        const agentsChart = new Chart(agentsCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    echo implode(',', array_map(function($a) { 
                        $types = ['admin' => 'Admin', 'police' => 'Polícia', 'hospital' => 'Hospital', 'inas' => 'INAS'];
                        return "'" . $types[$a['user_type']] . "'"; 
                    }, $agents_by_type)); 
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($agents_by_type, 'count')); ?>],
                    backgroundColor: ['#e91e63', '#2196F3', '#4CAF50', '#FF9800']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Status das Denúncias
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Pendentes', 'Em Andamento', 'Resolvidas'],
                datasets: [{
                    data: [<?php echo $pending_count . ', ' . $progress_count . ', ' . $resolved_count; ?>],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Províncias
        const provinceCtx = document.getElementById('provinceChart').getContext('2d');
        const provinceChart = new Chart(provinceCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($p) { return "'" . $p['province'] . "'"; }, $complaints_by_province)); ?>],
                datasets: [{
                    label: 'Número de Denúncias',
                    data: [<?php echo implode(',', array_column($complaints_by_province, 'count')); ?>],
                    backgroundColor: '#e91e63'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico Mensal
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['month_name'] . "'"; }, array_reverse($monthly_stats))); ?>],
                datasets: [{
                    label: 'Denúncias',
                    data: [<?php echo implode(',', array_column(array_reverse($monthly_stats), 'count')); ?>],
                    borderColor: '#673ab7',
                    backgroundColor: 'rgba(103, 58, 183, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>