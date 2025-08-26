<?php
/**
 * Database Connection Configuration
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

class DatabaseConnection
{
    private static $instance = null;
    private $connection;
    
    private function __construct()
    {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dbname = $_ENV['DB_NAME'] ?? 'Tro365';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $username, $password, $options);
            
            // Set timezone
            $timezone = $_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh';
            $this->connection->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function testConnection()
    {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getServerInfo()
    {
        try {
            return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (PDOException $e) {
            return 'Unknown';
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB()
{
    return DatabaseConnection::getInstance()->getConnection();
}

// Helper function to test database connection
function testDBConnection()
{
    return DatabaseConnection::getInstance()->testConnection();
}
