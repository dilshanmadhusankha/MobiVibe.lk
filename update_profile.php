<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function profile_response(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}
function profile_phone(string $raw): ?string {
    $value = preg_replace('/[\s\-().]/', '', $raw);
    if (strpos($value, '+94') === 0) $value = '0' . substr($value, 3);
    elseif (strpos($value, '0094') === 0) $value = '0' . substr($value, 4);
    elseif (strpos($value, '94') === 0 && strlen($value) === 11) $value = '0' . substr($value, 2);
    if (!preg_match('/^0[1-9][0-9]{8}$/', $value)) return null;
    return '+94' . substr($value, 1);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') profile_response(false, 'Invalid request.');
$user = mv_get_logged_in_user($conn);
if (!$user) profile_response(false, 'Please login to update your profile.');
$first = trim($_POST['firstName'] ?? '');
$last = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = profile_phone(trim($_POST['phone'] ?? ''));
if ($first === '' || $last === '' || $email === '' || !$phone) profile_response(false, 'Enter your first name, last name, valid email and valid phone number.');
if (mb_strlen($first) > 50 || mb_strlen($last) > 50) profile_response(false, 'Names must be 50 characters or fewer.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) profile_response(false, 'Please enter a valid email address.');
try {
    $userId = (int)$user['user_id'];
    $check = $conn->prepare('SELECT user_id FROM users WHERE (email = ? OR phone = ?) AND user_id <> ? LIMIT 1');
    $check->bind_param('ssi', $email, $phone, $userId);
    $check->execute();
    $duplicate = $check->get_result()->fetch_assoc();
    $check->close();
    if ($duplicate) profile_response(false, 'That email address or phone number is already used by another account.');

    $stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?');
    $stmt->bind_param('ssssi', $first, $last, $email, $phone, $userId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $first . ' ' . $last;

    $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, phone, status FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $updated = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    profile_response(true, 'Profile details updated successfully.', ['user' => mv_user_payload($conn, $updated)]);
} catch (mysqli_sql_exception $e) {
    if ((int)$e->getCode() === 1062) profile_response(false, 'That email address or phone number is already used.');
    profile_response(false, 'Could not update profile details. Please try again.');
}
?>
