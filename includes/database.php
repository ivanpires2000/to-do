<?php
class Database {
    private $db;
// includes/database.php (nova linha)
private function __construct() {
    // Usa a variável de ambiente do Heroku ou o arquivo local
    $databasePath = $_ENV['DATABASE_URL'] ?? 'database.db';
    $this->db = new SQLite3($databasePath);
    $this->createTables();
}

    private function createTables() {
        // Tabela de usuários
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                reset_token TEXT,
                reset_expires DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Tabela de tarefas
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                priority INTEGER DEFAULT 1,
                status INTEGER DEFAULT 0,
                due_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )
        ');

        // Tabela de anexos
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                path TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(task_id) REFERENCES tasks(id)
            )
        ');
    }

    public function getConnection() {
        return $this->db;
    }
}
?>