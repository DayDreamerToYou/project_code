<?php
/**
 * ============================================
 * 邮件发送API接口
 * 说明：发送带PDF附件的邮件
 * ============================================
 */

// 禁用错误输出到响应（改为记录到日志）
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-email-error.log');

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置（会加载 .env 文件）
require_once '../config/db.php';

/**
 * 获取环境变量值
 */
function getEnvVar($key, $default = '') {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $value;
}

// 引入 PHPMailer
require_once dirname(__DIR__) . '/lib/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/lib/PHPMailer/src/SMTP.php';
require_once dirname(__DIR__) . '/lib/PHPMailer/src/Exception.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 启动Session
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 自定义错误处理函数
function sendJsonResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证登录状态
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, '未登录或登录已过期');
}

try {
    // 获取 POST 数据
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        throw new Exception('没有接收到请求数据');
    }

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 解析失败: ' . json_last_error_msg());
    }

    if (!$input) {
        throw new Exception('无效的请求数据');
    }

    $to = $input['to'] ?? '';
    $subject = $input['subject'] ?? '采购记录';
    $body = $input['body'] ?? '请查收附件中的采购记录PDF文件。';
    $pdfData = $input['pdfData'] ?? ''; // Base64 编码的 PDF 数据

    // 验证必填字段
    if (empty($to) || empty($pdfData)) {
        throw new Exception('收件人地址和PDF数据不能为空');
    }

    // 验证邮箱格式
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('无效的邮箱地址');
    }

    // 创建邮件实例
    $mail = new PHPMailer(true);

    // 服务器设置 - Gmail 配置
    $mail->isSMTP();                                            // 使用 SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Gmail SMTP 服务器
    $mail->SMTPAuth   = true;                                  // 启用 SMTP 认证
    $mail->Username   = getEnvVar('GMAIL_USERNAME', 'your_gmail@gmail.com');   // Gmail 地址（从环境变量读取）
    $mail->Password   = getEnvVar('GMAIL_PASSWORD', '');        // Gmail 应用专用密码（从环境变量读取）
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        // 使用 STARTTLS
    $mail->Port       = 587;                                   // TCP 端口 587（STARTTLS）
    $mail->CharSet    = 'UTF-8';                               // 设置字符编码

    // 超时设置（秒）
    $mail->Timeout   = 30;                                      // SMTP 连接超时
    $mail->SMTPDebug  = 0;                                      // 关闭调试输出

    // 收件人
    $mail->setFrom(getEnvVar('GMAIL_USERNAME', 'noreply@example.com'), 'Fishery Data Management System');  // 发件人
    $mail->addAddress($to);

    // 附件 - 将 Base64 数据解码并添加为附件
    $pdfContent = base64_decode($pdfData);
    if ($pdfContent === false) {
        throw new Exception('PDF数据解码失败');
    }

    $timestamp = date('YmdHis');
    $mail->addStringAttachment($pdfContent, 'Purchase_Invoice_' . $timestamp . '.pdf', 'base64', 'application/pdf');

    // 内容
    $mail->isHTML(false);                                        // 设置为纯文本格式
    $mail->Subject = $subject;
    $mail->Body    = $body;

    // 发送邮件
    $mail->send();

    sendJsonResponse(true, '邮件发送成功');

} catch (Exception $e) {
    $errorMessage = $e->getMessage();

    // 记录详细错误到日志
    error_log("邮件发送错误: " . $errorMessage);
    error_log("错误堆栈: " . $e->getTraceAsString());

    sendJsonResponse(false, '邮件发送失败: ' . $errorMessage);
} catch (Error $e) {
    $errorMessage = $e->getMessage();

    // 记录详细错误到日志
    error_log("PHP 错误: " . $errorMessage);
    error_log("错误堆栈: " . $e->getTraceAsString());

    sendJsonResponse(false, '系统错误: ' . $errorMessage);
}
?>
