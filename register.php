<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(["success" => $success, "message" => $message], $extra));
    exit;
}

/*
 * Accepts Sri Lankan style numbers such as 0771234567, 077 123 4567,
 * +94 77 123 4567 or 94771234567. Returns the 9 digits after the leading 0,
 * or null when the number is not valid.
 */
function normalize_phone($raw) {
    $p = preg_replace('/[\s\-\(\)\.]/', '', $raw);
    if (strpos($p, '+94') === 0) {
        $p = '0' . substr($p, 3);
    } elseif (strpos($p, '0094') === 0) {
        $p = '0' . substr($p, 4);
    } elseif (strpos($p, '94') === 0 && strlen($p) === 11) {
        $p = '0' . substr($p, 2);
    }
    if (!preg_match('/^0[1-9][0-9]{8}$/', $p)) {
        return null;
    }
    return substr($p, 1);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Invalid request.");
}

$first_name = trim($_POST['firstName'] ?? '');
$last_name = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_raw = trim($_POST['phone'] ?? '');
$pass = $_POST['password'] ?? '';
$confirm_pass = $_POST['confirmPassword'] ?? '';

if ($first_name === '' || $last_name === '' || $email === '' || $phone_raw === '' || $pass === '' || $confirm_pass === '') {
    respond(false, "Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Please enter a valid email address.");
}

$phone_digits = normalize_phone($phone_raw);
if ($phone_digits === null) {
    respond(false, "Please enter a valid phone number (e.g. +94 77 123 4567).");
}
$phone = '+94' . $phone_digits;   // stored in one standard format

if (strlen($pass) < 6) {
    respond(false, "Password must contain at least 6 characters.");
}

if ($pass !== $confirm_pass) {
    respond(false, "Passwords do not match.");
}

try {
    /* Block duplicate registrations: check email AND phone number. */
    $phone_a = '+94' . $phone_digits;
    $phone_b = '0' . $phone_digits;
    $phone_c = '94' . $phone_digits;

    $check = $conn->prepare(
        "SELECT email, phone FROM users WHERE email = ? OR phone IN (?, ?, ?)"
    );
    $check->bind_param("ssss", $email, $phone_a, $phone_b, $phone_c);
    $check->execute();
    $result = $check->get_result();

    $email_taken = false;
    $phone_taken = false;
    while ($row = $result->fetch_assoc()) {
        if (strcasecmp($row['email'], $email) === 0) {
            $email_taken = true;
        }
        if (in_array($row['phone'], [$phone_a, $phone_b, $phone_c], true)) {
            $phone_taken = true;
        }
    }
    $check->close();

    if ($email_taken && $phone_taken) {
        respond(false, "This email and phone number are already registered. Please login.");
    }
    if ($email_taken) {
        respond(false, "This email is already registered. Please login.");
    }
    if ($phone_taken) {
        respond(false, "This phone number is already registered. Please login.");
    }

    $password_hash = password_hash($pass, PASSWORD_DEFAULT);
    $role = 'customer';
    $status = 'Not Active';   // becomes "Active" only after the user logs in

    $stmt = $conn->prepare(
        "INSERT INTO users (first_name, last_name, email, password_hash, phone, role, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssssss", $first_name, $last_name, $email, $password_hash, $phone, $role, $status);
    $stmt->execute();
    $stmt->close();

    respond(true, "Registration successful! Please login with your email and password.");
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {
        respond(false, "This email is already registered. Please login.");
    }
    respond(false, "Registration failed. Please try again.");
}
?>
