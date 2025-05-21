#!/usr/bin/php
<?php
file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] script STARTED\n", FILE_APPEND);

$mysqli = new mysqli('localhost', 'syslog_user', 'syslog_pass', 'syslog_db');
if ($mysqli->connect_errno) {
    file_put_contents("php://stderr", "❌ MySQL connection failed: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

// Cache các bảng đã tạo để tránh CREATE TABLE lặp đi lặp lại
$createdTables = [];

while ($line = fgets(STDIN)) {
    $line = trim($line);
    file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] line: $line\n", FILE_APPEND);

    // Mẫu log: 2025-05-21T14:32:00 myhost myapp 4321 This is the message
    if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(.*)$/', $line, $matches)) {
        [$full, $datetime, $host, $program, $pid, $message] = $matches;
        $datetime = date('Y-m-d H:i:s', strtotime($datetime));

        $tableName = 'logs_' . $mysqli->real_escape_string($pid);

        // Kiểm tra nếu bảng chưa tạo → tạo
        if (!in_array($tableName, $createdTables)) {
            $createQuery = "
                CREATE TABLE IF NOT EXISTS `$tableName` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    datetime DATETIME,
                    host VARCHAR(255),
                    program VARCHAR(255),
                    message TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            if (!$mysqli->query($createQuery)) {
                file_put_contents("php://stderr", "❌ Create table error: " . $mysqli->error . PHP_EOL);
                continue;
            }
            $createdTables[] = $tableName;
            file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] ✅ Created table $tableName\n", FILE_APPEND);
        }

        // Chèn log vào bảng tương ứng
        $stmt = $mysqli->prepare("INSERT INTO `$tableName` (datetime, host, program, message) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $datetime, $host, $program, $message);
            $stmt->execute();
            $stmt->close();
        } else {
            file_put_contents("php://stderr", "❌ SQL insert error: " . $mysqli->error . PHP_EOL);
        }
    } else {
        file_put_contents("php://stderr", "⚠️ Malformed log: $line\n", FILE_APPEND);
    }
}
