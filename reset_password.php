<?php
require_once 'includes/database.php';

$token = $_GET['token'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('
        SELECT id FROM users 
        WHERE reset_token = :token 
        AND reset_expires > datetime("now")
    ');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        $stmt = $db->prepare('
            UPDATE users 
            SET password = :password, 
                reset_token = NULL, 
                reset_expires = NULL 
            WHERE id = :id
        ');
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        header('Location: login.php?success=1');
        exit();
    } else {
        $error = "Token inválido ou expirado!";
    }
}

require_once 'includes/header.php';
?>
<div class="card mx-auto" style="max-width: 400px;">
    <div class="card-body">
        <h2 class="card-title text-center">Nova Senha</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Nova Senha" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Redefinir</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>