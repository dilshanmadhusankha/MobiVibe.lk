<?php
require_once 'auth_guard.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $user = mv_get_logged_in_user($conn);
    if ($user) {
        echo json_encode(["loggedIn" => true, "user" => mv_user_payload($conn, $user)]);
    } else {
        echo json_encode(["loggedIn" => false]);
    }
} catch (mysqli_sql_exception $e) {
    echo json_encode(["loggedIn" => false]);
}
?>
