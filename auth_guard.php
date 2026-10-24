<?php
/*
 * Shared authentication helpers.
 * A user is treated as logged in only when a PHP session exists AND the
 * users.status column is "Active" (set at login, cleared at logout).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connection.php';

/* Returns the address text saved for the user (or empty string). */
function mv_get_address(mysqli $conn, int $userId): string {
    $stmt = $conn->prepare(
        "SELECT address_line FROM addresses WHERE user_id = ?
         ORDER BY is_default DESC, address_id DESC LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['address_line'] ?? '';
}

/* Builds the user data sent to the browser. */
function mv_user_payload(mysqli $conn, array $row): array {
    return [
        "user_id"    => (int) $row['user_id'],
        "first_name" => $row['first_name'],
        "last_name"  => $row['last_name'],
        "name"       => trim($row['first_name'] . ' ' . $row['last_name']),
        "email"      => $row['email'],
        "phone"      => $row['phone'] ?? '',
        "status"     => $row['status'],
        "address"    => mv_get_address($conn, (int) $row['user_id'])
    ];
}

/* Returns the logged in user's DB row, or null when not logged in. */
function mv_get_logged_in_user(mysqli $conn): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $id = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT user_id, first_name, last_name, email, phone, status
         FROM users WHERE user_id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['status'] !== 'Active') {
        $_SESSION = [];
        return null;
    }
    return $row;
}

/* Protected page guard: redirects guests to the account page. */
function mv_require_login_page(string $redirect = 'account.html?login=required'): array {
    $conn = $GLOBALS['conn'];
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $user = mv_get_logged_in_user($conn);
    if (!$user) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}
?>
