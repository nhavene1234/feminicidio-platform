<?php
require_once '../includes/auth.php';
checkAuth();
$sql = "SELECT n.*, c.title as complaint_title 
FROM notifications n 
LEFT JOIN complaints c ON n.complaint_id = c.id 
WHERE n.user_id =? 
ORDER BY n.created_at DESC";
// Buscar notificações do usuário
try {
    $notifications_stmt = $pdo->prepare("
        SELECT n.*, c.complaint_code 
        FROM notifications n 
        LEFT JOIN complaints c ON n.complaint_id = c.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
    ");
    $notifications_stmt->execute([$_SESSION['user_id']]);
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao carregar notificações: " . $e->getMessage());
}
    // Marcar como lida se especificado
    if (isset($_GET['mark_read']) && $_GET['mark_read']) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
            ->execute([$_GET['mark_read'], $_SESSION['user_id']]);
        header('Location: notifications.php');
        exit();
    }
    
    // Marcar todas como lidas
    if (isset($_GET['mark_all_read'])) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
            ->execute([$_SESSION['user_id']]);
        header('Location: notifications.php');
        exit();
    }
    

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notifications-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
            position: relative;
        }
        
        .notification-item.unread {
            background: #f0f8ff;
            border-left-color: var(--info);
        }
        
        .notification-item.important {
            border-left-color: var(--danger);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
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
                    <a href="notifications.php" class="nav-link">
                        Notificações 
                        <?php 
                        $unread_count = array_reduce($notifications, function($carry, $item) {
                            return $carry + (!$item['is_read'] ? 1 : 0);
                        }, 0);
                        if ($unread_count > 0): ?>
                        <span style="background: var(--danger); color: white; border-radius: 50%; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            <?php echo $unread_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="notifications-header">
                <div>
                    <h1>Notificações</h1>
                    <p>Mantenha-se atualizado sobre suas atividades</p>
                </div>
                <div>
                    <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Marcar Todas como Lidas
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($notifications): ?>
                <div class="notifications-list">
                    <?php foreach($notifications as $notification): ?>
                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> <?php echo $notification['priority'] == 'high' ? 'important' : ''; ?>">
                            <div class="notification-header">
                                <div>
                                    <h4 style="margin: 0;">
                                        <?php if ($notification['complaint_code']): ?>
                                        Denúncia #<?php echo $notification['complaint_code']; ?>
                                        <?php else: ?>
                                        Sistema
                                        <?php endif; ?>
                                    </h4>
                                    <small style="color: var(--gray);">
                                        <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Marcar como Lida
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($notification['complaint_code']): ?>
                                    <a href="view-complaint.php?code=<?php echo $notification['complaint_code']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p style="margin: 0;"><?php echo htmlspecialchars($notification['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>Nenhuma Notificação</h3>
                    <p>Você não tem notificações no momento.</p>
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
</body>
</html>