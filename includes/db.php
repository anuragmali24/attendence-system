<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'blw_attendance');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00;background:#fff0f0;border:1px solid #fcc;border-radius:8px;margin:2rem">
                <strong>Database Connection Failed:</strong> ' . $conn->connect_error . '<br><br>
                <small>Please run <code>setup.php</code> first to create the database and tables.</small>
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
?>
