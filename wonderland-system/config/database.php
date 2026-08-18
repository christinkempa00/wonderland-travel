<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Database Configuration
 * ================================================================
 * File: config/database.php
 * Description: Konfigurasi koneksi database menggunakan PDO
 * ================================================================
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Database Configuration
 * Load from installed config or use defaults
 */
$envFile = BASE_PATH . '/config/database.env.php';

if (file_exists($envFile)) {
    require_once $envFile;
} else {
    // Tidak ada config/database.env.php — jangan diam-diam connect ke database
    // dengan kredensial default. Jalankan wizard instalasi (/install) atau buat
    // config/database.env.php manual (lihat README.md) dulu sebelum lanjut.
    exit('Konfigurasi database belum ada. Buat config/database.env.php terlebih dahulu (lihat README.md) atau jalankan wizard instalasi di /install.');
}
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

/**
 * PDO Options
 */
define('DB_OPTIONS', serialize([
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::ATTR_PERSISTENT         => false
]));

/**
 * Database Class
 * Singleton pattern untuk koneksi database
 */
class Database {
    
    private static $instance = null;
    private $pdo;
    private $queryCount = 0;
    private $queryLog = [];
    private $debugMode = false;
    
    /**
     * Constructor - Private untuk singleton
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get Database Instance
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO Connection
     * @return PDO
     */
    public static function getConnection(): PDO {
        return self::getInstance()->pdo;
    }
    
    /**
     * Connect to Database
     */
    private function connect(): void {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, unserialize(DB_OPTIONS));
            
        } catch (PDOException $e) {
            // Log error
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Show user-friendly error in production
            if (defined('APP_DEBUG') && APP_DEBUG === true) {
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            } else {
                throw new Exception("Terjadi kesalahan koneksi database. Silakan hubungi administrator.");
            }
        }
    }
    
    /**
     * Enable/Disable Debug Mode
     * @param bool $enabled
     */
    public function setDebugMode(bool $enabled): void {
        $this->debugMode = $enabled;
    }
    
    /**
     * Execute Query with Parameters
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->queryCount++;
            
            if ($this->debugMode) {
                $this->queryLog[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ];
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Fetch Single Row
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch All Rows
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch Single Column Value
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert Data
     * @param string $table
     * @param array $data
     * @return int Last Insert ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        
        $this->query($sql, array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Update Data
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int Affected Rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = array_map(fn($col) => "`$col` = ?", array_keys($data));
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE `$table` SET $setClause WHERE $where";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete Data
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int Affected Rows
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin Transaction
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit Transaction
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback Transaction
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
    
    /**
     * Check if in Transaction
     */
    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Get Last Insert ID
     * @return string
     */
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get Query Count
     * @return int
     */
    public function getQueryCount(): int {
        return $this->queryCount;
    }
    
    /**
     * Get Query Log
     * @return array
     */
    public function getQueryLog(): array {
        return $this->queryLog;
    }
    
    /**
     * Quote String
     * @param string $string
     * @return string
     */
    public function quote(string $string): string {
        return $this->pdo->quote($string);
    }
    
    /**
     * Check Table Exists
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetchColumn($sql, [$table]);
        return !empty($result);
    }
    
    /**
     * Get Table Columns
     * @param string $table
     * @return array
     */
    public function getTableColumns(string $table): array {
        $sql = "DESCRIBE `$table`";
        return $this->fetchAll($sql);
    }
    
    /**
     * Truncate Table
     * @param string $table
     */
    public function truncate(string $table): void {
        $this->pdo->exec("TRUNCATE TABLE `$table`");
    }
    
    /**
     * Execute Raw SQL (for migrations, etc)
     * @param string $sql
     * @return int
     */
    public function exec(string $sql): int {
        return $this->pdo->exec($sql);
    }
    
    /**
     * Import SQL File
     * @param string $filepath
     * @return bool
     */
    public function importSqlFile(string $filepath): bool {
        if (!file_exists($filepath)) {
            throw new Exception("SQL file not found: $filepath");
        }
        
        $sql = file_get_contents($filepath);
        
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by delimiter
        $queries = preg_split('/;\s*$/m', $sql);
        
        $this->beginTransaction();
        
        try {
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->pdo->exec($query);
                }
            }
            
            $this->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->rollback();
            throw $e;
        }
    }
}

/**
 * Helper function untuk akses cepat ke database
 * @return Database
 */
function db(): Database {
    return Database::getInstance();
}

/**
 * Helper function untuk akses PDO langsung
 * @return PDO
 */
function pdo(): PDO {
    return Database::getConnection();
}
