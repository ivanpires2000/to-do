<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Excluir Tarefa Concluída
if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $db->exec("DELETE FROM tasks WHERE id = $task_id AND user_id = $user_id");
    header("Location: completed_tasks.php");
    exit();
}

// Buscar tarefas concluídas
$result = $db->query("
    SELECT * FROM tasks
    WHERE user_id = $user_id AND status = 2
    ORDER BY due_date DESC
");
$completedTasksList = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $completedTasksList[] = $row;
}

require_once 'includes/header.php';
?>

<div class="card shadow">
    <div class="card-body">
        <h3>Tarefas Concluídas</h3>
        <a href="dashboard.php" class="btn btn-primary mb-3">← Voltar para Dashboard</a>
        <div class="list-group">
            <?php if (count($completedTasksList) === 0): ?>
                <p>Nenhuma tarefa concluída.</p>
            <?php else: ?>
                <?php foreach ($completedTasksList as $task): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h5 style="text-decoration: line-through;">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </h5>
                            <p style="text-decoration: line-through;"><?php echo htmlspecialchars($task['description']); ?></p>
                            <small class="text-muted">Prioridade: <?php echo $task['priority']; ?></small>
                            <br>
                            <small>Responsáveis: <?php echo htmlspecialchars($task['responsaveis_execucao'] ?? ''); ?></small>
                            <br>
                            <small>Vistoriado por: <?php echo htmlspecialchars($task['vistoriado_por'] ?? ''); ?></small>
                            <br>
                            <small>Status: Concluída</small>
                        </div>
                        <div>
                            <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-info btn-sm me-2" title="Ver detalhes">🔍</a>
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-warning btn-sm me-2" title="Editar tarefa">✏️</a>
                            <a href="?delete=<?php echo $task['id']; ?>" class="btn btn-danger btn-sm">❌</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
