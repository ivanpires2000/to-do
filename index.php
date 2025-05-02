<?php
// Conectar ao SQLite
$db = new SQLite3('database.db');

// Criar tabela se não existir
$db->exec('
    CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_name TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
');

// Adicionar tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $task = $_POST['task'];
    $stmt = $db->prepare('INSERT INTO tasks (task_name) VALUES (:task)');
    $stmt->bindValue(':task', $task, SQLITE3_TEXT);
    $stmt->execute();
}

// Excluir tarefa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->exec("DELETE FROM tasks WHERE id = $id");
}

// Buscar tarefas
$result = $db->query('SELECT * FROM tasks');
$tasks = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tasks[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lista de Tarefas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Lista de Tarefas 📝</h1>
    
    <form method="POST">
        <input type="text" name="task" placeholder="Nova tarefa..." required>
        <button type="submit">Adicionar</button>
    </form>

    <ul>
        <?php foreach ($tasks as $task): ?>
            <li>
                <?php echo htmlspecialchars($task['task_name']); ?>
                <a href="index.php?delete=<?php echo $task['id']; ?>">❌</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
