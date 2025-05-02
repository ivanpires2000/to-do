<?php
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $db->prepare('
            UPDATE users 
            SET reset_token = :token, reset_expires = :expires 
            WHERE id = :id
        ');
        $stmt->bindValue(':token', $token, SQLITE3_TEXT);
        $stmt->bindValue(':expires', $expires, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();

        // Simulação de envio de e-mail
        $resetLink = "http://localhost:8000/reset_password.php?token=$token";
        echo "<div class='alert alert-info'>Link de redefinição: <a href='$resetLink'>$resetLink</a></div>";
    } else {
        $error = "E-mail não encontrado!";
    }
}

require_once 'includes/header.php';
?>
<div class="card mx-auto" style="max-width: 400px;">
    <div class="card-body">
        <h2 class="card-title text-center">Recuperar Senha</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="E-mail" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar Link</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>