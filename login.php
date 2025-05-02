<?php
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Usuário ou senha inválidos!";
    }
}

require_once 'includes/header.php';
?>
<div class="card mx-auto" style="max-width: 400px;">
    <div class="card-body">
        <h2 class="card-title text-center">Login</h2>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Registro realizado! Faça login.</div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Usuário" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="mt-3 text-center">
            <a href="forgot_password.php">Esqueceu a senha?</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
