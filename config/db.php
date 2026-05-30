<?php
/**
 * ============================================
 * 数据库配置文件
 * 说明：使用环境变量存储敏感信息
 * ============================================
 */

// 防止直接访问
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// 加载环境变量文件到本地数组
$envData = [];
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // 跳过注释行
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // 解析 KEY=VALUE 格式
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $envData[$name] = $value;
        }
    }
}

/**
 * 数据库配置类
 */
class Database {
    /**
     * 数据库连接参数（从环境变量读取）
     */
    private static $host;
    private static $db_name;
    private static $username;
    private static $password;
    private static $charset = 'utf8mb4';      // 字符集

    /**
     * 获取配置值
     */
    private static function getConfig($key, $default = '') {
        global $envData;
        return $envData[$key] ?? $default;
    }

    /**
     * 初始化配置（延迟加载）
     */
    private static function initConfig() {
        self::$host = self::getConfig('DB_HOST', 'localhost');
        self::$db_name = self::getConfig('DB_NAME', 'seafood');
        self::$username = self::getConfig('DB_USER', 'root');
        self::$password = self::getConfig('DB_PASS', '');
    }

    /**
     * 获取数据库连接（单例模式）
     * @return PDO 数据库连接对象
     */
    public static function getConnection() {
        static $conn = null;

        // 如果连接已存在，直接返回
        if ($conn !== null) {
            return $conn;
        }

        // 初始化配置
        self::initConfig();

        try {
            // 创建PDO连接
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // 启用异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,             // 默认返回关联数组
                PDO::ATTR_EMULATE_PREPARES   => false                         // 禁用模拟预处理
            ];

            $conn = new PDO($dsn, self::$username, self::$password, $options);
            return $conn;

        } catch(PDOException $e) {
            // 记录错误日志
            error_log("数据库连接失败: " . $e->getMessage());

            // 返回错误响应
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '数据库连接失败',
                'error' => '系统错误，请联系管理员'
            ]);
            exit;
        }
    }

    /**
     * 测试数据库连接
     * @return bool 连接是否成功
     */
    public static function testConnection() {
        try {
            $conn = self::getConnection();
            return $conn !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
