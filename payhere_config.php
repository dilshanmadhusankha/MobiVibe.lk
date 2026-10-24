<?php
/*
 * PayHere sandbox configuration.
 * Fill these values from https://sandbox.payhere.lk after adding localhost as a Domain.
 * Never publish your real Merchant Secret to a public repository.
 */
define('PAYHERE_MERCHANT_ID', 'YOUR_SANDBOX_MERCHANT_ID');
define('PAYHERE_MERCHANT_SECRET', 'YOUR_SANDBOX_MERCHANT_SECRET');
define('PAYHERE_SANDBOX', true);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$folder = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$base = $scheme . '://' . $host . rtrim($folder, '/');
define('PAYHERE_RETURN_URL', $base . '/payment_return.php');
define('PAYHERE_CANCEL_URL', $base . '/payment_cancel.php');
define('PAYHERE_NOTIFY_URL', $base . '/payhere_notify.php');
?>
