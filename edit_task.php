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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = intval($_POST['priority']);
    $due_date = $_POST['due_date'];
    $responsaveis_execucao = trim($_POST['responsaveis_execucao'] ?? '');
    $vistoriado_por = trim($_POST['vistoriado_por'] ?? '');

    $stmt = $db->prepare('
        UPDATE tasks SET 
            title = :title,
            description = :description,
            priority = :priority,
            due_date = :due_date,
            responsaveis_execucao = :responsaveis_execucao,
            vistoriado_por = :vistoriado_por
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
    $stmt->bindValue(':due_date', $due_date, SQLITE3_TEXT);
    $stmt->bindValue(':responsaveis_execucao', $responsaveis_execucao, SQLITE3_TEXT);
    $stmt->bindValue(':vistoriado_por', $vistoriado_por, SQLITE3_TEXT);
    $stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();

    header('Location: dashboard.php');
    exit();
}

// Fetch task data
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
        <h2 class="card-title">Editar Tarefa</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Título</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($task['title']); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Prioridade</label>
                <select id="priority" name="priority" class="form-select">
                    <option value="1" <?php if ($task['priority'] == 1) echo 'selected'; ?>>Baixa</option>
                    <option value="2" <?php if ($task['priority'] == 2) echo 'selected'; ?>>Média</option>
                    <option value="3" <?php if ($task['priority'] == 3) echo 'selected'; ?>>Alta</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="due_date" class="form-label">Data de Vencimento</label>
                <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($task['due_date']); ?>">
            </div>
            <div class="mb-3">
                <label for="responsaveis_execucao" class="form-label">Responsáveis pela execução</label>
                <input type="text" id="responsaveis_execucao" name="responsaveis_execucao" class="form-control" value="<?php echo htmlspecialchars($task['responsaveis_execucao'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="vistoriado_por" class="form-label">Vistoriado por</label>
                <input type="text" id="vistoriado_por" name="vistoriado_por" class="form-control" value="<?php echo htmlspecialchars($task['vistoriado_por'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="dashboard.php" class="btn btn-secondary ms-2">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
