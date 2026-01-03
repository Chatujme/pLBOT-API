<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

/**
 * SQLite database service for stats and auth persistence
 */
final class DatabaseService
{
    private PDO $pdo;
    private const DB_FILE = __DIR__ . '/../../data/plbot.db';

    public function __construct()
    {
        $this->initDatabase();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function initDatabase(): void
    {
        $dbDir = dirname(self::DB_FILE);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . self::DB_FILE);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->createTables();
    }

    private function createTables(): void
    {
        // Stats table for endpoint statistics
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS stats_endpoints (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path TEXT NOT NULL,
                method TEXT NOT NULL,
                requests INTEGER DEFAULT 0,
                success INTEGER DEFAULT 0,
                errors INTEGER DEFAULT 0,
                total_time REAL DEFAULT 0,
                avg_time REAL DEFAULT 0,
                min_time REAL DEFAULT NULL,
                max_time REAL DEFAULT NULL,
                last_request_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(path, method)
            )
        ");

        // Stats hourly aggregation
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS stats_hourly (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                hour TEXT NOT NULL UNIQUE,
                requests INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Request log for detailed tracking
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS stats_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path TEXT NOT NULL,
                method TEXT NOT NULL,
                status_code INTEGER,
                response_time REAL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create index for faster queries
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_requests_created
            ON stats_requests(created_at)
        ");

        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_requests_path
            ON stats_requests(path)
        ");

        // Users table for authentication
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'admin',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Auth tokens table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create index for token lookup
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_tokens_token
            ON auth_tokens(token)
        ");

        // Insert default admin user if not exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password_hash, role)
                VALUES (?, ?, ?)
            ");
            $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), 'admin']);
        }
    }
}
