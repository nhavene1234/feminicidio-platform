<?php
require_once '../includes/auth.php';
if (!isAdmin()) {
    header('Location: ../agents/login.php');
    exit();
}

// Configuração de paginação
$agents_per_page = 6;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $agents_per_page;

// Buscar agentes com paginação
try {
    // Total de agentes para paginação
    $total_agents_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_agents = $total_agents_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calcular total de páginas
    $total_pages = ceil($total_agents / $agents_per_page);
    
    // Buscar agentes da página atual
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $agents_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erro ao carregar agentes: " . $e->getMessage());
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'create_agent') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = $_POST['user_type'];
        $post = $_POST['post'];
        
        try {
            // Verificar se email já existe
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $error = "Já existe um agente com este email!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, post) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $password, $user_type, $post]);
                $success = "Agente criado com sucesso!";
                
                // Redirecionar para evitar re-submissão
                header("Location: manage-agents.php?page=1&success=1");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erro ao criar agente: " . $e->getMessage();
        }
    } elseif ($action === 'toggle_agent') {
        $agent_id = $_POST['agent_id'];
        $is_active = $_POST['is_active'];
        
        try {
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$is_active, $agent_id]);
            $success = "Status do agente atualizado!";
            
            // Redirecionar para a mesma página
            header("Location: manage-agents.php?page=" . $current_page . "&success=1");
            exit();
        } catch(PDOException $e) {
            $error = "Erro ao atualizar agente: " . $e->getMessage();
        }
    } elseif ($action === 'delete_agent') {
        $agent_id = $_POST['agent_id'];
        
        try {
            // Não permitir deletar o próprio usuário
            if ($agent_id == $_SESSION['user_id']) {
                $error = "Não pode eliminar a sua própria conta!";
            } else {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$agent_id]);
                $success = "Agente eliminado com sucesso!";
                
                // Redirecionar para a mesma página
                header("Location: manage-agents.php?page=" . $current_page . "&success=1");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erro ao eliminar agente: " . $e->getMessage();
        }
    } elseif ($action === 'edit_agent') {
        $agent_id = $_POST['agent_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $user_type = $_POST['user_type'];
        $post = $_POST['post'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, user_type = ?, post = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_type, $post, $agent_id]);
            $success = "Agente atualizado com sucesso!";
            
            // Redirecionar para a mesma página
            header("Location: manage-agents.php?page=" . $current_page . "&success=1");
            exit();
        } catch(PDOException $e) {
            $error = "Erro ao atualizar agente: " . $e->getMessage();
        }
    }
}

// Verificar se há mensagem de sucesso na URL
if (isset($_GET['success'])) {
    $success = "Operação realizada com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Agentes - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .agent-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .agent-card:hover {
            transform: translateY(-5px);
        }
        
        .agent-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .agent-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .agent-body {
            padding: 1.5rem;
        }
        
        .agent-info {
            margin-bottom: 1rem;
        }
        
        .agent-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .agent-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: none;
            cursor: pointer;
        }
        
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .type-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }
        
        .type-admin { background: #e91e63; }
        .type-police { background: #2196F3; }
        .type-hospital { background: #4CAF50; }
        .type-inas { background: #FF9800; }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .pagination-info {
            color: var(--gray);
            font-weight: 500;
        }
        
        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pagination-btn:hover:not(.disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-numbers {
            display: flex;
            gap: 0.5rem;
        }
        
        .page-number {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            background: white;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .page-number:hover {
            background: #f8f9fa;
        }
        
        .page-number.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
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
                    <li><a href="manage-agents.php" class="active">Gerir Agentes</a></li>
                    <li><a href="reports.php">Relatórios</a></li>
                    <li><a href="settings.php">Configurações</a></li>
                </ul>
            </div>

            <div class="header-actions">
                <div>
                    <h1>Gerir Agentes</h1>
                    <p class="pagination-info">
                        Mostrando <?php echo count($agents); ?> de <?php echo $total_agents; ?> agentes
                        <?php if ($total_pages > 1): ?>
                         - Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <button onclick="openModal('create-agent-modal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Agente
                </button>
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

            <!-- Grid de Agentes -->
            <div class="agents-grid">
                <?php foreach($agents as $agent): ?>
                <div class="agent-card">
                    <div class="agent-header">
                        <div class="agent-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 style="margin: 0; font-size: 1.2rem;"><?php echo $agent['name']; ?></h3>
                        <p style="margin: 0; opacity: 0.9;"><?php echo $agent['email']; ?></p>
                    </div>
                    
                    <div class="agent-body">
                        <div class="agent-info">
                            <div class="agent-info-item">
                                <span><strong>Tipo:</strong></span>
                                <span class="type-badge type-<?php echo $agent['user_type']; ?>">
                                    <?php 
                                    $types = [
                                        'admin' => 'Administrador',
                                        'police' => 'Polícia', 
                                        'hospital' => 'Hospital',
                                        'inas' => 'INAS'
                                    ];
                                    echo $types[$agent['user_type']];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="agent-info-item">
                                <span><strong>Posto:</strong></span>
                                <span><?php echo $agent['post']; ?></span>
                            </div>
                            
                            <div class="agent-info-item">
                                <span><strong>Status:</strong></span>
                                <span class="status-badge <?php echo $agent['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $agent['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </div>
                            
                            <div class="agent-info-item">
                                <span><strong>Registado em:</strong></span>
                                <span><?php echo date('d/m/Y', strtotime($agent['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="agent-actions">
                            <button onclick="openEditModal(<?php echo $agent['id']; ?>, '<?php echo addslashes($agent['name']); ?>', '<?php echo $agent['email']; ?>', '<?php echo $agent['user_type']; ?>', '<?php echo addslashes($agent['post']); ?>')" 
                                    class="btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_agent">
                                <input type="hidden" name="agent_id" value="<?php echo $agent['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $agent['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn-sm <?php echo $agent['is_active'] ? 'btn-secondary' : 'btn-success'; ?>">
                                    <i class="fas fa-power-off"></i> <?php echo $agent['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                </button>
                            </form>
                            
                            <?php if ($agent['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja eliminar este agente?');">
                                <input type="hidden" name="action" value="delete_agent">
                                <input type="hidden" name="agent_id" value="<?php echo $agent['id']; ?>">
                                <button type="submit" class="btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($agents)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <i class="fas fa-users" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>Nenhum Agente Encontrado</h3>
                <p>Comece adicionando o primeiro agente ao sistema.</p>
                <button onclick="openModal('create-agent-modal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Adicionar Primeiro Agente
                </button>
            </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Botão Anterior -->
                <a href="manage-agents.php?page=<?php echo $current_page - 1; ?>" 
                   class="pagination-btn <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>

                <!-- Números das Páginas -->
                <div class="page-numbers">
                    <?php
                    // Mostrar até 5 números de página
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="manage-agents.php?page=<?php echo $i; ?>" 
                           class="page-number <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <!-- Botão Próximo -->
                <a href="manage-agents.php?page=<?php echo $current_page + 1; ?>" 
                   class="pagination-btn <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                    Próximo <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Criar Agente -->
    <div id="create-agent-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-user-plus"></i> Criar Novo Agente</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_agent">
                
                <div class="form-group">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Agente *</label>
                    <select name="user_type" class="form-select" required>
                        <option value="">Selecione o tipo</option>
                        <option value="police">Polícia</option>
                        <option value="hospital">Hospital</option>
                        <option value="inas">INAS</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Posto/Localização *</label>
                    <input type="text" name="post" class="form-control" required placeholder="Ex: Esquadra Central Maputo">
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Agente
                    </button>
                    <button type="button" onclick="closeModal('create-agent-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Agente -->
    <div id="edit-agent-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-user-edit"></i> Editar Agente</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_agent">
                <input type="hidden" name="agent_id" id="edit_agent_id">
                
                <div class="form-group">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Agente *</label>
                    <select name="user_type" id="edit_user_type" class="form-select" required>
                        <option value="police">Polícia</option>
                        <option value="hospital">Hospital</option>
                        <option value="inas">INAS</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Posto/Localização *</label>
                    <input type="text" name="post" id="edit_post" class="form-control" required>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar Agente
                    </button>
                    <button type="button" onclick="closeModal('edit-agent-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openEditModal(id, name, email, userType, post) {
            document.getElementById('edit_agent_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_user_type').value = userType;
            document.getElementById('edit_post').value = post;
            openModal('edit-agent-modal');
        }
        
        // Fechar modais clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.getElementsByClassName('modal');
                for (let modal of modals) {
                    modal.style.display = 'none';
                }
            }
        });
        
        // Desabilitar clique em botões desabilitados
        document.addEventListener('DOMContentLoaded', function() {
            const disabledButtons = document.querySelectorAll('.pagination-btn.disabled');
            disabledButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                });
            });
        });
    </script>
</body>
</html>