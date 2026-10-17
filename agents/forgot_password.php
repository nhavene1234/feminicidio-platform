<?php
// agents/forgot_password.php
require_once '../includes/config.php';

$message = '';
$error = '';
$devLink = ''; // só preenchido em localhost

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Introduza um email válido.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Invalidar pedidos anteriores
                $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")
                    ->execute([$user['id']]);

                // Token aleatório: o link leva o token, a BD guarda apenas o hash
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at)
                               VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
                    ->execute([$user['id'], $tokenHash]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . BASE_PATH . '/agents/reset_password.php?token=' . $token;

                $subject = 'Redefinição de senha - DenúnciaFemi';
                $body = "Olá, {$user['name']}.\n\n"
                      . "Recebemos um pedido para redefinir a sua senha.\n"
                      . "Clique no link abaixo (válido por 1 hora):\n\n$link\n\n"
                      . "Se não fez este pedido, ignore esta mensagem.";
                $headers = "From: no-reply@denunciafemi.gov.mz\r\nContent-Type: text/plain; charset=UTF-8";
                @mail($email, $subject, $body, $headers);

                // No XAMPP o mail() normalmente não envia nada, por isso, SÓ em localhost,
                // mostramos o link no ecrã para poderes testar.
                if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
                    $devLink = $link;
                }
            }

            // Mesma resposta exista ou não o email (evita descobrir emails registados)
            $message = 'Se o email estiver registado, enviámos as instruções para redefinir a senha.';
        } catch (PDOException $e) {
            $error = 'Erro ao processar o pedido. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - DenúnciaFemi</title>
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
                <div class="nav-menu">
                    <a href="<?php echo BASE_PATH; ?>/public/index.php" class="nav-link">Página Inicial</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <h1 class="form-title" style="text-align:center;">
                <i class="fas fa-key"></i><br>Recuperar Senha
            </h1>

            <?php if ($message): ?><div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($devLink): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; font-size: 0.85rem; word-break: break-all;">
                    <strong>Modo local (teste):</strong> o email não é enviado no XAMPP. Use este link:<br>
                    <a href="<?php echo htmlspecialchars($devLink); ?>"><?php echo htmlspecialchars($devLink); ?></a>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email da sua conta</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Enviar link de recuperação
                    </button>
                </div>
            </form>

            <div style="text-align:center; margin-top:1rem;">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
            </div>
        </div>
    </main>
</body>
</html>