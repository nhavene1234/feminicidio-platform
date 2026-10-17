<?php
// agents/change_password.php  (qualquer utilizador autenticado)
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$isAdminUser = ($_SESSION['user_type'] === 'admin');
$backUrl = $isAdminUser ? '../admin/dashboard.php' : 'dashboard.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Preencha todos os campos.';
    } elseif (strlen($new) < 8) {
        $error = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($new !== $confirm) {
        $error = 'A nova senha e a confirmação não coincidem.';
    } elseif ($new === $current) {
        $error = 'A nova senha deve ser diferente da atual.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current, $user['password'])) {
                $error = 'A senha atual está incorreta.';
            } else {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
                session_regenerate_id(true);
                $success = 'Senha alterada com sucesso!';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao alterar a senha. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - DenúnciaFemi</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container { max-width: 420px; margin: 3rem auto; padding: 2rem; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="<?php echo $backUrl; ?>">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi
                    </a>
                </div>
                <div class="nav-menu">
                    <span style="color: white; margin-right: 1rem;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="<?php echo $backUrl; ?>" class="nav-link">Voltar</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <h1 class="form-title" style="text-align: center;">
                <i class="fas fa-lock"></i><br>
                Alterar Senha
            </h1>

            <?php if ($success): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password" class="form-label">Senha atual</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Nova senha (mín. 8 caracteres)</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar nova senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Guardar nova senha
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>