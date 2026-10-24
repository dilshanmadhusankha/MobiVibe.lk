<?php
session_start();
$userId = $_SESSION['user_id'] ?? null;

/* End the session first so the user is always logged out. */
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

/* Logged out -> status goes back to "Not Active". */
if ($userId) {
    try {
        require_once 'db_connection.php';
        $stmt = $conn->prepare("UPDATE users SET status = 'Not Active' WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        /* Session is already destroyed; ignore DB errors. */
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(["success" => true, "message" => "Logged out successfully."]);
?>
