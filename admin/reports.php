<?php
require_once '../includes/auth.php';
if (!isAdmin()) {
    header('Location: ../agents/login.php');
    exit();
}

// Buscar estatísticas
try {
    // Total de denúncias
    $total_complaints = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    
    // Denúncias por status
    $status_stats = $pdo->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    
    // Denúncias por província
    $province_stats = $pdo->query("SELECT province, COUNT(*) as count FROM complaints GROUP BY province ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Denúncias por tipo
    $type_stats = $pdo->query("SELECT complaint_type, COUNT(*) as count FROM complaints GROUP BY complaint_type")->fetchAll(PDO::FETCH_ASSOC);
    
    // Denúncias por mês
    $monthly_stats = $pdo->query("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
        FROM complaints 
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY month DESC 
        LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
        
} catch(PDOException $e) {
    die("Erro ao carregar relatórios: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema contra Feminicídio</title>
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
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .export-buttons {
            display: flex;
            gap: 1rem;
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
                    <li><a href="reports.php" class="active">Relatórios</a></li>
                </ul>
            </div>

            <div class="dashboard-header">
                <h1>Relatórios e Estatísticas</h1>
                <p>Análise detalhada do sistema de denúncias</p>
            </div>

            <div class="export-buttons">
                <button onclick="exportToPDF()" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button onclick="exportToExcel()" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_complaints; ?></div>
                    <div>Total de Denúncias</div>
                </div>
                <?php foreach($status_stats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stat['count']; ?></div>
                    <div><?php echo ucfirst($stat['status']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="chart-row">
                <div class="chart-container">
                    <h3>Denúncias por Status</h3>
                    <canvas id="statusChart" width="400" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Denúncias por Tipo</h3>
                    <canvas id="typeChart" width="400" height="300"></canvas>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-container">
                    <h3>Denúncias por Província (Top 10)</h3>
                    <canvas id="provinceChart" width="400" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Evolução Mensal</h3>
                    <canvas id="monthlyChart" width="400" height="300"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3>Estatísticas Detalhadas por Província</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 1rem; text-align: left;">Província</th>
                                <th style="padding: 1rem; text-align: center;">Total</th>
                                <th style="padding: 1rem; text-align: center;">Pendentes</th>
                                <th style="padding: 1rem; text-align: center;">Em Andamento</th>
                                <th style="padding: 1rem; text-align: center;">Resolvidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $province_details = $pdo->query("
                                SELECT 
                                    province,
                                    COUNT(*) as total,
                                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                                    SUM(CASE WHEN status = 'andamento' THEN 1 ELSE 0 END) as andamento,
                                    SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolvidas
                                FROM complaints 
                                GROUP BY province 
                                ORDER BY total DESC
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach($province_details as $detail):
                            ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 1rem;"><?php echo $detail['province']; ?></td>
                                <td style="padding: 1rem; text-align: center; font-weight: bold;"><?php echo $detail['total']; ?></td>
                                <td style="padding: 1rem; text-align: center;"><?php echo $detail['pendentes']; ?></td>
                                <td style="padding: 1rem; text-align: center;"><?php echo $detail['andamento']; ?></td>
                                <td style="padding: 1rem; text-align: center;"><?php echo $detail['resolvidas']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

   

    <script>
        // Gráfico de Status
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst($s['status']) . "'"; }, $status_stats)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($status_stats, 'count')); ?>],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8', 
                        '#28a745'
                    ]
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

        // Gráfico de Tipo
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(function($t) { 
                    return "'" . ($t['complaint_type'] == 'intimo' ? 'Agressor Íntimo' : 'Agressor Não Íntimo') . "'"; 
                }, $type_stats)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($type_stats, 'count')); ?>],
                    backgroundColor: [
                        '#e91e63',
                        '#673ab7'
                    ]
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

        // Gráfico de Províncias (Top 10)
        const provinceCtx = document.getElementById('provinceChart').getContext('2d');
        const provinceChart = new Chart(provinceCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $top_provinces = array_slice($province_stats, 0, 10);
                    echo implode(',', array_map(function($p) { return "'" . $p['province'] . "'"; }, $top_provinces)); 
                ?>],
                datasets: [{
                    label: 'Número de Denúncias',
                    data: [<?php echo implode(',', array_column($top_provinces, 'count')); ?>],
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
                labels: [<?php echo implode(',', array_map(function($m) { 
                    return "'" . date('M/Y', strtotime($m['month'] . '-01')) . "'"; 
                }, array_reverse($monthly_stats))); ?>],
                datasets: [{
                    label: 'Denúncias',
                    data: [<?php echo implode(',', array_column(array_reverse($monthly_stats), 'count')); ?>],
                    borderColor: '#e91e63',
                    backgroundColor: 'rgba(233, 30, 99, 0.1)',
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

        function exportToPDF() {
            alert('Funcionalidade de exportação PDF será implementada!');
            // Aqui você pode integrar com uma biblioteca como jsPDF
        }

        function exportToExcel() {
            alert('Funcionalidade de exportação Excel será implementada!');
            // Aqui você pode integrar com uma biblioteca como SheetJS
        }
    </script>
</body>
</html>