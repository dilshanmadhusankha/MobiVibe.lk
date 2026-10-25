<?php
session_start();
require_once 'auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "Please enter your email and password."]);
    exit;
}

try {
    $stmt = $conn->prepare(
        "SELECT user_id, first_name, last_name, email, phone, password_hash, status
         FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    /* Only the email + password used at registration will work. */
    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
        exit;
    }

    /* Logged in -> status becomes "Active" in the users table. */
    $upd = $conn->prepare("UPDATE users SET status = 'Active' WHERE user_id = ?");
    $upd->bind_param("i", $user['user_id']);
    $upd->execute();
    $upd->close();
    $user['status'] = 'Active';

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

    echo json_encode([
        "success" => true,
        "message" => "Login successful! Welcome back, " . $user['first_name'] . ".",
        "user" => mv_user_payload($conn, $user)
    ]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["success" => false, "message" => "Login failed. Please try again."]);
}
?>
