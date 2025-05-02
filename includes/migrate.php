<?php
// Script para migrar a tabela tasks para a estrutura correta

$db = new SQLite3('database.db');

// Criar tabela temporária com a estrutura correta
$db->exec('
    CREATE TABLE IF NOT EXISTS tasks_temp (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        priority INTEGER DEFAULT 1,
        status INTEGER DEFAULT 0,
        due_date DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Copiar dados da tabela antiga para a nova (adaptando colunas)
$rows = $db->query('SELECT id, task_name, created_at FROM tasks');
while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
    $stmt = $db->prepare('INSERT INTO tasks_temp (id, user_id, title, created_at) VALUES (:id, :user_id, :title, :created_at)');
    $stmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', 1, SQLITE3_INTEGER); // Usuário padrão 1, ajustar conforme necessário
    $stmt->bindValue(':title', $row['task_name'], SQLITE3_TEXT);
    $stmt->bindValue(':created_at', $row['created_at'], SQLITE3_TEXT);
    $stmt->execute();
}

// Deletar tabela antiga
$db->exec('DROP TABLE tasks');

// Renomear tabela temporária para tasks
$db->exec('ALTER TABLE tasks_temp RENAME TO tasks');

echo "Migração concluída com sucesso.\n";
?>
