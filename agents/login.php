<?php
// agents/login.php
require_once '../includes/config.php';

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password,$user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_post'] = $user['post'];
            
            // Redirecionar baseado no tipo de usuário
            if ($user['user_type'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error = "Email ou senha incorretos!";
        }
    } catch(PDOException $e) {
        $error = "Erro ao fazer login: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Área dos Agentes</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .user-type-badges {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        
        .user-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-admin { background: #e91e63; color: white; }
        .badge-police { background: #2196F3; color: white; }
        .badge-hospital { background: #4CAF50; color: white; }
        .badge-inas { background: #FF9800; color: white; }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="<?php echo BASE_PATH; ?>/public/index.php">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi
                    </a>
                </div>
                <div class="nav-menu">
                    <a href="<?php echo BASE_PATH; ?>/public/index.php" class="nav-link">Página Inicial</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <h1 class="form-title" style="text-align: center;">
                <i class="fas fa-user-shield"></i><br>
                Área dos Agentes
            </h1>
            
            <div class="user-type-badges">
                <span class="user-badge badge-police">Polícia</span>
                <span class="user-badge badge-hospital">Hospital</span>
                <span class="user-badge badge-inas">INAS</span>
                <span class="user-badge badge-admin">Administrador</span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1rem; color: var(--gray);">
                <small>Área restrita para agentes autorizados</small>
            </div>

           
        </div>
    </main>

   
</body>
</html>