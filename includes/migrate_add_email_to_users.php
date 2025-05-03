<?php
// Migration script to add email column to users table if it does not exist

$db = new SQLite3('database.db');

// Check if email column exists
$columns = [];
$result = $db->query("PRAGMA table_info(users)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('email', $columns)) {
    // Add email column without UNIQUE constraint due to SQLite limitation
    $db->exec("ALTER TABLE users ADD COLUMN email TEXT NOT NULL DEFAULT ''");
    echo "Coluna 'email' adicionada à tabela users sem UNIQUE.\n";
} else {
    echo "Coluna 'email' já existe na tabela users.\n";
}
?>
