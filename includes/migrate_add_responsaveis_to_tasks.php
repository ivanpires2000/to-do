<?php
// Migration script to add responsaveis_execucao and vistoriado_por columns to tasks table if they do not exist

$db = new SQLite3('database.db');

// Check existing columns
$columns = [];
$result = $db->query("PRAGMA table_info(tasks)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('responsaveis_execucao', $columns)) {
    $db->exec("ALTER TABLE tasks ADD COLUMN responsaveis_execucao TEXT");
    echo "Coluna 'responsaveis_execucao' adicionada à tabela tasks.\n";
} else {
    echo "Coluna 'responsaveis_execucao' já existe na tabela tasks.\n";
}

if (!in_array('vistoriado_por', $columns)) {
    $db->exec("ALTER TABLE tasks ADD COLUMN vistoriado_por TEXT");
    echo "Coluna 'vistoriado_por' adicionada à tabela tasks.\n";
} else {
    echo "Coluna 'vistoriado_por' já existe na tabela tasks.\n";
}
?>
