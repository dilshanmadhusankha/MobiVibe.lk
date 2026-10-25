<?php
$host = "localhost";
$user = "root";
$pass = "1234";
$db = "mobivibe_db";

/* First connect to MySQL server without selecting a database. */
$serverConn = new mysqli($host, $user, $pass);

if ($serverConn->connect_error) {
    die("Database Connection Failed: " . $serverConn->connect_error);
}

$serverConn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$serverConn->close();

/* Now connect directly to MobiVibe database. */
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* Throw exceptions on SQL errors so the PHP files can return clean JSON errors. */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Make sure the users table exists even if database_setup.php has not been run yet. */
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        role ENUM('customer', 'admin') DEFAULT 'customer',
        status VARCHAR(20) DEFAULT 'Not Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
?>