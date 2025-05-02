<?php
// Script para adicionar a coluna completion_notes na tabela tasks

$db = new SQLite3('database.db');

// Verificar se a coluna já existe
$columns = $db->query("PRAGMA table_info(tasks)");
$has_column = false;
while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'completion_notes') {
        $has_column = true;
        break;
    }
}

if (!$has_column) {
    // Criar tabela temporária com a nova coluna
    $db->exec('
        CREATE TABLE tasks_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            priority INTEGER DEFAULT 1,
            status INTEGER DEFAULT 0,
            due_date DATETIME,
            completion_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Copiar dados da tabela antiga para a nova
    $db->exec('
        INSERT INTO tasks_temp (id, user_id, title, description, priority, status, due_date, created_at)
        SELECT id, user_id, title, description, priority, status, due_date, created_at FROM tasks
    ');

    // Deletar tabela antiga
    $db->exec('DROP TABLE tasks');

    // Renomear tabela temporária para tasks
    $db->exec('ALTER TABLE tasks_temp RENAME TO tasks');

    echo "Coluna completion_notes adicionada com sucesso.\n";
} else {
    echo "Coluna completion_notes já existe.\n";
}
?>
