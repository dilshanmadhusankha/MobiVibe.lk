<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
function password_response(bool $success, string $message): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') password_response(false, 'Invalid request.');
$user = mv_get_logged_in_user($conn);
if (!$user) password_response(false, 'Please login to change your password.');
$current = $_POST['currentPassword'] ?? '';
$new = $_POST['newPassword'] ?? '';
$confirm = $_POST['confirmNewPassword'] ?? '';
if ($current === '' || $new === '' || $confirm === '') password_response(false, 'Please fill in all password fields.');
if (strlen($new) < 6) password_response(false, 'New password must contain at least 6 characters.');
if ($new !== $confirm) password_response(false, 'New passwords do not match.');
if ($current === $new) password_response(false, 'Choose a new password different from your current password.');
try {
    $userId = (int)$user['user_id'];
    $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !password_verify($current, $row['password_hash'])) password_response(false, 'Your current password is incorrect.');
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
    $stmt->close();
    password_response(true, 'Password changed successfully. Please use your new password next time you login.');
} catch (mysqli_sql_exception $e) {
    password_response(false, 'Could not change password. Please try again.');
}
?>
