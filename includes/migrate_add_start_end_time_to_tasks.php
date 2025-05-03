<?php
// Migration script to add start_time and end_time columns to tasks table if they do not exist

$db = new SQLite3('database.db');

// Check existing columns
$columns = [];
$result = $db->query("PRAGMA table_info(tasks)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('start_time', $columns)) {
    $db->exec("ALTER TABLE tasks ADD COLUMN start_time DATETIME");
    echo "Coluna 'start_time' adicionada à tabela tasks.\n";
} else {
    echo "Coluna 'start_time' já existe na tabela tasks.\n";
}

if (!in_array('end_time', $columns)) {
    $db->exec("ALTER TABLE tasks ADD COLUMN end_time DATETIME");
    echo "Coluna 'end_time' adicionada à tabela tasks.\n";
} else {
    echo "Coluna 'end_time' já existe na tabela tasks.\n";
}
?>
