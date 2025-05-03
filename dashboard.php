<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Adicionar Tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = intval($_POST['priority']);
    $due_date = $_POST['due_date'];
    $responsaveis_execucao = trim($_POST['responsaveis_execucao'] ?? '');
    $vistoriado_por = trim($_POST['vistoriado_por'] ?? '');
    $visita_solicitada_por = trim($_POST['visita_solicitada_por'] ?? '');

    $stmt = $db->prepare('
        INSERT INTO tasks (user_id, title, description, priority, due_date, responsaveis_execucao, vistoriado_por, visita_solicitada_por, created_at)
        VALUES (:user_id, :title, :description, :priority, :due_date, :responsaveis_execucao, :vistoriado_por, :visita_solicitada_por, :created_at)
    ');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
    $stmt->bindValue(':due_date', $due_date, SQLITE3_TEXT);
    $stmt->bindValue(':responsaveis_execucao', $responsaveis_execucao, SQLITE3_TEXT);
    $stmt->bindValue(':vistoriado_por', $vistoriado_por, SQLITE3_TEXT);
    $stmt->bindValue(':visita_solicitada_por', $visita_solicitada_por, SQLITE3_TEXT);
    $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->execute();
}

// Excluir Tarefa
if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $db->exec("DELETE FROM tasks WHERE id = $task_id");
}

// Alternar status da tarefa
if (isset($_GET['toggle_status'])) {
    $task_id = intval($_GET['toggle_status']);
    // Buscar status atual
    $result = $db->query("SELECT status FROM tasks WHERE id = $task_id");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        // Ciclo de status: 0 (pendente) -> 1 (em execução) -> 2 (concluída) -> 0
        $new_status = ($row['status'] + 1) % 3;

        // Registrar start_time e end_time conforme status, usando o mesmo horário para início e término
        $current_time = date('Y-m-d H:i:s');
        if ($new_status == 1 && $row['status'] == 0) {
            // Início da tarefa
            $stmt = $db->prepare("UPDATE tasks SET status = :status, start_time = :start_time WHERE id = :id");
            $stmt->bindValue(':start_time', $current_time, SQLITE3_TEXT);
        } elseif ($new_status == 2) {
            // Tarefa concluída
            $stmt = $db->prepare("UPDATE tasks SET status = :status, end_time = :end_time WHERE id = :id");
            $stmt->bindValue(':end_time', $current_time, SQLITE3_TEXT);
        } else {
            $stmt = $db->prepare("UPDATE tasks SET status = :status WHERE id = :id");
        }

        $stmt->bindValue(':status', $new_status, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    header("Location: dashboard.php");
    exit();
}

// Atualizar notas de conclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completion_notes']) && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $completion_notes = trim($_POST['completion_notes']);
    $stmt = $db->prepare("UPDATE tasks SET completion_notes = :notes WHERE id = :id");
    $stmt->bindValue(':notes', $completion_notes, SQLITE3_TEXT);
    $stmt->bindValue(':id', $task_id, SQLITE3_INTEGER);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}

// Buscar Tarefas pendentes e em execução (status 0 e 1)
$result = $db->query("
    SELECT * FROM tasks 
    WHERE status IN (0, 1)
    ORDER BY priority DESC, due_date ASC
");
$tasks = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tasks[] = $row;
}

// Buscar Tarefas concluídas (status 2) para contabilizar no dashboard
$result_completed = $db->query("
    SELECT COUNT(*) as total_completed FROM tasks 
    WHERE status = 2
");
$row_completed = $result_completed->fetchArray(SQLITE3_ASSOC);
$totalCompletedTasks = $row_completed ? intval($row_completed['total_completed']) : 0;

// Estatísticas para Gráficos
// Corrigir cálculo de tarefas pendentes para refletir corretamente no gráfico
$result_pending = $db->query("
    SELECT COUNT(*) as total_pending FROM tasks 
    WHERE status IN (0, 1)
");
$row_pending = $result_pending->fetchArray(SQLITE3_ASSOC);
$totalPendingTasks = $row_pending ? intval($row_pending['total_pending']) : 0;

$totalTasks = $totalPendingTasks + $totalCompletedTasks;
$completedTasks = $totalCompletedTasks;
$priorityCounts = [0, 0, 0]; // Baixa, Média, Alta
foreach ($tasks as $task) {
    $priorityIndex = $task['priority'] - 1;
    if (isset($priorityCounts[$priorityIndex])) {
        $priorityCounts[$priorityIndex]++;
    }
}

require_once 'includes/header.php';
?>

<div class="card shadow">
    <div class="card-body">
        <!-- Formulário de Tarefa -->
        <form method="POST" class="mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Título" required>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="1">Baixa</option>
                        <option value="2">Média</option>
                        <option value="3">Alta</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="text" name="responsaveis_execucao" class="form-control" placeholder="Responsáveis pela execução">
                </div>
            <div class="col-md-2">
                <input type="text" name="vistoriado_por" class="form-control" placeholder="Vistoriado por">
            </div>
            <div class="col-md-2">
                <input type="text" name="visita_solicitada_por" class="form-control" placeholder="Visita Solicitada Por">
            </div>
        </div>
        <textarea name="description" class="form-control mt-3" placeholder="Descrição"></textarea>
        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-success btn-lg px-4">+ Adicionar Tarefa</button>
        </div>
    </form>
        <a href="completed_tasks.php" class="completed-tasks-link d-block mt-3">Ver todas as tarefas realizadas</a>

        <!-- Lista de Tarefas -->
        <div class="list-group">
            <?php foreach ($tasks as $task): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                <h5<?php if ($task['status'] == 2) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($task['title']); ?></h5>
                <p<?php if ($task['status'] == 2) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($task['description']); ?></p>
                <small class="text-muted">Prioridade: <?php echo $task['priority']; ?></small>
                <br>
                <small>Responsáveis: <?php echo htmlspecialchars($task['responsaveis_execucao'] ?? ''); ?></small>
                <br>
                <small>Vistoriado por: <?php echo htmlspecialchars($task['vistoriado_por'] ?? ''); ?></small>
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

        <!-- Seção de Tarefas Concluídas -->
        <!-- Removido do dashboard.php conforme solicitado -->
        <!-- A listagem e manipulação das tarefas concluídas deve ser feita somente em completed_tasks.php -->

        <!-- Gráficos -->
        <div class="row mt-3">
            <div class="col-md-6">
                <canvas id="completionChart" style="max-height: 200px;"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="priorityChart" style="max-height: 200px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
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

    // Gráfico de Prioridades (barras menores)
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
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>