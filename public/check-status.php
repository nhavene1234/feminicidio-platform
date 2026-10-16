<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$complaint_code = $_GET['code'] ?? '';
$complaint = null;
$updates = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $complaint_code) {
    $search_code = $complaint_code ?: $_POST['complaint_code'];
    
    try {
        // Buscar denúncia
        $stmt = $pdo->prepare("SELECT c.*, u.name as agent_name, u.post as agent_post 
                              FROM complaints c 
                              LEFT JOIN users u ON c.assigned_agent_id = u.id 
                              WHERE c.complaint_code = ?");
        $stmt->execute([$search_code]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($complaint) {
            // Buscar atualizações
            $updates_stmt = $pdo->prepare("SELECT cu.*, u.name as user_name, u.user_type 
                                          FROM complaint_updates cu 
                                          JOIN users u ON cu.user_id = u.id 
                                          WHERE cu.complaint_id = ? 
                                          ORDER BY cu.created_at DESC");
            $updates_stmt->execute([$complaint['id']]);
            $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        $error = "Erro ao buscar denúncia: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Status - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h1 class="form-title">Verificar Status da Denúncia</h1>
            
            <div class="form-options" style="text-align: center; margin-bottom: 2rem;">
                <a href="check-status.php" class="btn btn-primary">Ver Status da Denúncia</a>
                <a href="make-complaint.php" class="btn btn-secondary">Fazer Nova Denúncia</a>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="complaint_code" class="form-label">Código da Denúncia *</label>
                    <input type="text" id="complaint_code" name="complaint_code" class="form-control" 
                           required value="<?php echo htmlspecialchars($complaint_code); ?>" 
                           placeholder="Digite o código recebido (ex: ABC12345)">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-search"></i> Verificar Status
                    </button>
                </div>
            </form>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($complaint): ?>
                <div class="complaint-details" style="margin-top: 2rem;">
                    <div class="detail-header" style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <div>
                                <h3>Denúncia #<?php echo $complaint['complaint_code']; ?></h3>
                                <p>Status: <?php echo getStatusBadge($complaint['status']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <small>Data de registro: <?php echo date('d/m/Y', strtotime($complaint['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div class="detail-card">
                            <h4>Informações da Vítima</h4>
                            <p><strong>Nome:</strong> <?php echo $complaint['is_anonymous'] ? 'Anônimo' : htmlspecialchars($complaint['complainant_name']); ?></p>
                            <p><strong>Contacto:</strong> <?php echo htmlspecialchars($complaint['complainant_contact']); ?></p>
                            <p><strong>Tipo:</strong> <?php echo getComplaintTypeText($complaint['complaint_type']); ?></p>
                            <?php if ($complaint['aggressor_relation']): ?>
                            <p><strong>Relação:</strong> <?php echo htmlspecialchars($complaint['aggressor_relation']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="detail-card">
                            <h4>Localização</h4>
                            <p><strong>Data do incidente:</strong> <?php echo date('d/m/Y', strtotime($complaint['incident_date'])); ?></p>
                            <p><strong>Província:</strong> <?php echo htmlspecialchars($complaint['province']); ?></p>
                            <p><strong>Distrito:</strong> <?php echo htmlspecialchars($complaint['district']); ?></p>
                            <p><strong>Bairro:</strong> <?php echo htmlspecialchars($complaint['neighborhood']); ?></p>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h4>Detalhes do Ocorrido</h4>
                        <div style="background: white; padding: 1rem; border-radius: 5px; border-left: 4px solid var(--primary);">
                            <?php echo nl2br(htmlspecialchars($complaint['incident_details'])); ?>
                        </div>
                    </div>

                    <?php if ($complaint['assigned_agent_id']): ?>
                    <div class="detail-card">
                        <h4>Agente Responsável</h4>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($complaint['agent_name']); ?></p>
                        <p><strong>Posto:</strong> <?php echo htmlspecialchars($complaint['agent_post']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($updates): ?>
                    <div class="updates-timeline">
                        <h4>Histórico de Atualizações</h4>
                        <?php foreach($updates as $update): ?>
                            <div class="timeline-item">
                                <div class="timeline-header">
                                    <strong><?php echo htmlspecialchars($update['user_name']); ?></strong>
                                    <small><?php echo date('d/m/Y H:i', strtotime($update['created_at'])); ?></small>
                                </div>
                                <p><?php echo htmlspecialchars($update['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-warning" style="text-align: center; margin-top: 2rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Nenhuma denúncia encontrada com este código.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <style>
        .detail-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .timeline-item {
            padding: 1rem;
            border-left: 3px solid var(--primary);
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</body>
</html>