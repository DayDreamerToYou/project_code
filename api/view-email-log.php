<?php
/**
 * 邮件发送日志查看器
 */

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');

$logFile = '/tmp/php-email-error.log';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件发送日志</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1F4E78;
            border-bottom: 2px solid #1F4E78;
            padding-bottom: 10px;
        }
        .log-content {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
        }
        .no-log {
            color: #999;
            font-style: italic;
        }
        .error-line {
            color: #d32f2f;
            font-weight: bold;
        }
        .actions {
            margin: 20px 0;
        }
        button {
            background-color: #5FB878;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background-color: #4a9c63;
        }
        button.danger {
            background-color: #f44336;
        }
        button.danger:hover {
            background-color: #d32f2f;
        }
        .info {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 邮件发送日志查看器</h1>

        <div class="info">
            <strong>日志文件位置：</strong> <?php echo htmlspecialchars($logFile); ?><br>
            <strong>文件大小：</strong> <?php echo file_exists($logFile) ? filesize($logFile) . ' 字节' : '文件不存在'; ?>
        </div>

        <div class="actions">
            <button onclick="location.reload()">🔄 刷新日志</button>
            <button class="danger" onclick="clearLog()">🗑️ 清空日志</button>
            <button onclick="location.href='../public/'">← 返回主页面</button>
        </div>

        <h2>日志内容</h2>
        <?php
        if (file_exists($logFile) && filesize($logFile) > 0) {
            $content = file_get_contents($logFile);

            // 高亮错误信息
            $content = preg_replace('/\[.*?\].*?Error.*?$/m', '<span class="error-line">$0</span>', $content);
            $content = preg_replace('/\[.*?\].*?Exception.*?$/m', '<span class="error-line">$0</span>', $content);
            $content = preg_replace('/\[.*?\].*?Failed.*?$/m', '<span class="error-line">$0</span>', $content);

            echo '<div class="log-content">' . htmlspecialchars($content) . '</div>';
        } else {
            echo '<div class="log-content no-log">暂无日志记录</div>';
        }
        ?>

        <div class="info">
            <strong>常见错误解释：</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li><code>Could not authenticate</code> - SMTP 认证失败，通常是因为授权码不正确</li>
                <li><code>Connection refused</code> - 无法连接到 SMTP 服务器</li>
                <li><code>Timeout</code> - 连接超时，可能是网络问题</li>
            </ul>
        </div>
    </div>

    <script>
    function clearLog() {
        if (confirm('确定要清空日志吗？')) {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=clear')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('日志已清空');
                        location.reload();
                    } else {
                        alert('清空失败: ' + data.message);
                    }
                });
        }
    }

    // 处理清空日志请求
    <?php
    if (isset($_GET['action']) && $_GET['action'] === 'clear') {
        if (file_exists($logFile)) {
            if (unlink($logFile)) {
                echo json_encode(['success' => true, 'message' => '日志已清空']);
            } else {
                echo json_encode(['success' => false, 'message' => '无法删除日志文件']);
            }
        } else {
            echo json_encode(['success' => true, 'message' => '日志文件不存在']);
        }
        exit;
    }
    ?>
    </script>
</body>
</html>
