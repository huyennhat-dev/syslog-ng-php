#!/usr/bin/php
<?php
file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] script STARTED\n", FILE_APPEND);

$mysqli = new mysqli('host.docker.internal', 'syslog_user', 'syslog_pass', 'syslog_db');
if ($mysqli->connect_errno) {
    file_put_contents("php://stderr", "Failed to connect to MySQL: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

// Đọc từng dòng từ stdin
while ($line = fgets(STDIN)) {
    file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] line: $line\n", FILE_APPEND);

    // Giả sử log dạng: 2025-05-16T11:22:33 host program 1234 Message here
    preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.*)$/', trim($line), $matches);
    if (count($matches) === 6) {
        file_put_contents("/tmp/pipe_debug.log", "[" . date('H:i:s') . "] malformed line: $line\n", FILE_APPEND);

        [$full, $datetime, $host, $program, $pid, $message] = $matches;
        $stmt = $mysqli->prepare("INSERT INTO logs (datetime, host, program, pid, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssss', $datetime, $host, $program, $pid, $message);
            $stmt->execute();
            $stmt->close();
        } else {
            file_put_contents("php://stderr", "SQL error: " . $mysqli->error . PHP_EOL);
        }
    } else {
        file_put_contents("php://stderr", "Malformed log: $line" . PHP_EOL);
    }
}
?>