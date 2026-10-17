<?php
// agents/reset_password.php
require_once '../includes/config.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$resetRow = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $pdo->prepare("SELECT id, user_id FROM password_resets
                           WHERE token_hash = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([hash('sha256', $token)]);
    $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($resetRow && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_DEFAULT), $resetRow['user_id']]);
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ?")
                ->execute([$resetRow['user_id']]);
            $pdo->commit();

            header('Location: login.php?reset=1');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erro ao atualizar a senha. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - DenúnciaFemi</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container { max-width: 400px; margin: 4rem auto; padding: 2rem; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="<?php echo BASE_PATH; ?>/public/index.php"><i class="fas fa-shield-heart"></i> DenúnciaFemi</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <h1 class="form-title" style="text-align:center;">
                <i class="fas fa-lock"></i><br>Nova Senha
            </h1>

            <?php if (!$resetRow): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">Este link é inválido ou já expirou.</div>
                <div style="text-align:center;">
                    <a href="forgot_password.php">Pedir um novo link</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="password" class="form-label">Nova senha (mín. 8 caracteres)</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar nova senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-save"></i> Guardar nova senha
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>