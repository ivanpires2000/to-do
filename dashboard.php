<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];

// Adicionar Tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = intval($_POST['priority']);
    $due_date = $_POST['due_date'];

    $stmt = $db->prepare('
        INSERT INTO tasks (user_id, title, description, priority, due_date)
        VALUES (:user_id, :title, :description, :priority, :due_date)
    ');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
    $stmt->bindValue(':due_date', $due_date, SQLITE3_TEXT);
    $stmt->execute();
}

// Excluir Tarefa
if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $db->exec("DELETE FROM tasks WHERE id = $task_id AND user_id = $user_id");
}

// Alternar status da tarefa
if (isset($_GET['toggle_status'])) {
    $task_id = intval($_GET['toggle_status']);
    // Buscar status atual
    $result = $db->query("SELECT status FROM tasks WHERE id = $task_id AND user_id = $user_id");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        // Ciclo de status: 0 (pendente) -> 1 (em execução) -> 2 (concluída) -> 0
        $new_status = ($row['status'] + 1) % 3;
        $stmt = $db->prepare("UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':status', $new_status, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    header("Location: dashboard.php");
    exit();
}

// Atualizar notas de conclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completion_notes']) && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $completion_notes = trim($_POST['completion_notes']);
    $stmt = $db->prepare("UPDATE tasks SET completion_notes = :notes WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':notes', $completion_notes, SQLITE3_TEXT);
    $stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}

// Buscar Tarefas
$result = $db->query("
    SELECT * FROM tasks 
    WHERE user_id = $user_id 
    ORDER BY priority DESC, due_date ASC
");
$tasks = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tasks[] = $row;
}

// Estatísticas para Gráficos
$totalTasks = count($tasks);
$completedTasks = array_sum(array_column($tasks, 'status'));
$priorityCounts = [0, 0, 0]; // Baixa, Média, Alta
foreach ($tasks as $task) {
    $priorityIndex = $task['priority'] - 1;
    $priorityCounts[$priorityIndex]++;
}

require_once 'includes/header.php';
?>

<div class="card shadow">
    <div class="card-body">
        <!-- Formulário de Tarefa -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Título" required>
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-select">
                        <option value="1">Baixa</option>
                        <option value="2">Média</option>
                        <option value="3">Alta</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">+ Tarefa</button>
                </div>
            </div>
            <textarea name="description" class="form-control mt-2" placeholder="Descrição"></textarea>
        </form>

        <!-- Lista de Tarefas -->
        <div class="list-group">
            <?php foreach ($tasks as $task): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                <h5<?php if ($task['status'] == 2) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($task['title']); ?></h5>
                <p<?php if ($task['status'] == 2) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($task['description']); ?></p>
                <small class="text-muted">Prioridade: <?php echo $task['priority']; ?></small>
                <br>
                <small>Status: 
                    <?php 
                        if ($task['status'] == 0) echo 'Pendente';
                        elseif ($task['status'] == 1) echo 'Em Execução';
                        else echo 'Concluída';
                    ?>
                </small>
                <?php if ($task['status'] > 0): ?>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <textarea name="completion_notes" class="form-control" placeholder="Descreva o que foi realizado..."><?php echo htmlspecialchars($task['completion_notes']); ?></textarea>
                    <button type="submit" class="btn btn-primary btn-sm mt-1">Salvar Notas</button>
                </form>
                <?php endif; ?>
            </div>
            <div>
                <a href="?toggle_status=<?php echo $task['id']; ?>" class="btn btn-sm 
                    <?php 
                        if ($task['status'] == 0) echo 'btn-secondary';
                        elseif ($task['status'] == 1) echo 'btn-warning';
                        else echo 'btn-success';
                    ?>" 
                    title="Alterar status">
                    <?php 
                        if ($task['status'] == 0) echo 'Pendente';
                        elseif ($task['status'] == 1) echo 'Em Execução';
                        else echo 'Concluída';
                    ?>
                </a>
                <a href="?delete=<?php echo $task['id']; ?>" class="btn btn-danger btn-sm">❌</a>
            </div>
        </div>
            <?php endforeach; ?>
        </div>

        <!-- Gráficos -->
        <div class="row mt-5">
            <div class="col-md-6">
                <canvas id="completionChart"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Configuração dos Gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Conclusão
    new Chart(document.getElementById('completionChart'), {
        type: 'pie',
        data: {
            labels: ['Concluídas', 'Pendentes'],
            datasets: [{
                data: [<?php echo $completedTasks; ?>, <?php echo $totalTasks - $completedTasks; ?>],
                backgroundColor: ['#4CAF50', '#FF6384']
            }]
        },
        options: { responsive: true }
    });

    // Gráfico de Prioridades
    new Chart(document.getElementById('priorityChart'), {
        type: 'bar',
        data: {
            labels: ['Baixa', 'Média', 'Alta'],
            datasets: [{
                label: 'Tarefas por Prioridade',
                data: <?php echo json_encode($priorityCounts); ?>,
                backgroundColor: ['#36A2EB', '#FFCE56', '#FF6384']
            }]
        },
        options: { 
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>