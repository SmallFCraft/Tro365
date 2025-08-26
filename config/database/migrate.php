<?php
/**
 * Database Migration Runner
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/connection.php';

try {
    echo "=== Database Migration Runner ===\n\n";
    
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    // Tạo bảng migrations nếu chưa có
    $createMigrationsTable = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $pdo->exec($createMigrationsTable);
    echo "✓ Bảng migrations đã sẵn sàng\n";
    
    // Lấy danh sách migrations đã chạy
    try {
        $executedMigrations = $pdo->query("SELECT migration_name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Nếu bảng migrations chưa có cột migration_name, tạo lại bảng
        $pdo->exec("DROP TABLE IF EXISTS migrations");
        $pdo->exec($createMigrationsTable);
        $executedMigrations = [];
        echo "✓ Đã tạo lại bảng migrations\n";
    }
    
    // Quét thư mục migrations
    $migrationsDir = __DIR__ . '/migrations/';
    $migrationFiles = glob($migrationsDir . '*.sql');
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "Không tìm thấy file migration nào\n";
        exit(0);
    }
    
    echo "Tìm thấy " . count($migrationFiles) . " file migration\n\n";
    
    $executedCount = 0;
    $skippedCount = 0;
    
    foreach ($migrationFiles as $file) {
        $migrationName = basename($file, '.sql');
        
        if (in_array($migrationName, $executedMigrations)) {
            echo "⏭️  Bỏ qua: {$migrationName} (đã chạy)\n";
            $skippedCount++;
            continue;
        }
        
        echo "🔄 Đang chạy: {$migrationName}\n";
        
        try {
            $sql = file_get_contents($file);
            
            // Tách các câu lệnh SQL
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
                }
            );
            
            // Thực thi từng câu lệnh
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Bỏ qua lỗi nếu là câu lệnh SELECT (thông báo)
                        if (strpos($statement, 'SELECT') === 0) {
                            continue;
                        }
                        throw $e;
                    }
                }
            }
            
            // Ghi nhận migration đã chạy
            $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)")
                ->execute([$migrationName]);
            
            echo "✅ Hoàn thành: {$migrationName}\n";
            $executedCount++;
            
        } catch (PDOException $e) {
            echo "❌ Lỗi: {$migrationName} - " . $e->getMessage() . "\n";
            break;
        }
    }
    
    echo "\n=== Kết quả ===\n";
    echo "✅ Đã chạy: {$executedCount} migration\n";
    echo "⏭️  Bỏ qua: {$skippedCount} migration\n";
    echo "📊 Tổng cộng: " . count($migrationFiles) . " migration\n";
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
