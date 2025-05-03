<?php
// Migration script to add visita_solicitada_por column to tasks table if it does not exist

$db = new SQLite3('database.db');

// Check existing columns
$columns = [];
$result = $db->query("PRAGMA table_info(tasks)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('visita_solicitada_por', $columns)) {
    $db->exec("ALTER TABLE tasks ADD COLUMN visita_solicitada_por TEXT");
    echo "Coluna 'visita_solicitada_por' adicionada à tabela tasks.\n";
} else {
    echo "Coluna 'visita_solicitada_por' já existe na tabela tasks.\n";
}
?>
