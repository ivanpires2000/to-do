<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$task_id = intval($_GET['id']);

$stmt = $db->prepare('SELECT * FROM tasks WHERE id = :id AND user_id = :user_id');
$stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$task = $result->fetchArray(SQLITE3_ASSOC);

if (!$task) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/header.php';
?>

<div class="card mx-auto" style="max-width: 600px;">
    <div class="card-body">
        <h2 class="card-title"><?php echo htmlspecialchars($task['title']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
<p><strong>Prioridade:</strong> <?php echo $task['priority']; ?></p>
<p><strong>Status:</strong> 
    <?php 
        if ($task['status'] == 0) echo 'Pendente';
        elseif ($task['status'] == 1) echo 'Em Execução';
        else echo 'Concluída';
    ?>
</p>
<p><strong>Responsáveis pela execução:</strong> <?php echo htmlspecialchars($task['responsaveis_execucao'] ?? ''); ?></p>
<p><strong>Vistoriado por:</strong> <?php echo htmlspecialchars($task['vistoriado_por'] ?? ''); ?></p>
<p><strong>Data de Início:</strong> <?php echo $task['start_time'] ?? 'Não iniciado'; ?></p>
<p><strong>Data de Término:</strong> <?php echo $task['end_time'] ?? 'Não concluído'; ?></p>
<p><strong>Notas de Conclusão:</strong></p>
<p><?php echo nl2br(htmlspecialchars($task['completion_notes'] ?? '')); ?></p>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
