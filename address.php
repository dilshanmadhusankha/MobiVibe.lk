<?php
require_once 'auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = mv_get_logged_in_user($conn);
    if (!$user) {
        echo json_encode(["success" => false, "message" => "Please login to save your address."]);
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["success" => false, "message" => "Invalid request."]);
        exit;
    }

    $address = trim($_POST['address'] ?? '');
    if ($address === '') {
        echo json_encode(["success" => false, "message" => "Please enter your delivery address."]);
        exit;
    }
    if (mb_strlen($address) > 255) {
        echo json_encode(["success" => false, "message" => "Address is too long (maximum 255 characters)."]);
        exit;
    }

    $userId = (int) $user['user_id'];
    $name = trim($user['first_name'] . ' ' . $user['last_name']);
    $phone = $user['phone'] ?? '';

    $find = $conn->prepare(
        "SELECT address_id FROM addresses WHERE user_id = ?
         ORDER BY is_default DESC, address_id DESC LIMIT 1"
    );
    $find->bind_param("i", $userId);
    $find->execute();
    $existing = $find->get_result()->fetch_assoc();
    $find->close();

    if ($existing) {
        $id = (int) $existing['address_id'];
        $stmt = $conn->prepare("UPDATE addresses SET address_line = ?, recipient_name = ?, phone = ? WHERE address_id = ?");
        $stmt->bind_param("sssi", $address, $name, $phone, $id);
    } else {
        $city = '';
        $stmt = $conn->prepare(
            "INSERT INTO addresses (user_id, recipient_name, phone, address_line, city, is_default)
             VALUES (?, ?, ?, ?, ?, TRUE)"
        );
        $stmt->bind_param("issss", $userId, $name, $phone, $address, $city);
    }
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true, "message" => "Address saved successfully."]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["success" => false, "message" => "Could not save the address. Please try again."]);
}
?>
