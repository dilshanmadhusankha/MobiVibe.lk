<?php
/*
 * PayHere sandbox configuration.
 * Fill these values from https://sandbox.payhere.lk after adding localhost as a Domain.
 * Never publish your real Merchant Secret to a public repository.
 */
define('PAYHERE_MERCHANT_ID', '1238903');
define('PAYHERE_MERCHANT_SECRET', 'MzU3ODQ0NDIzODQyNjUzMDEzNzk0MzAzOTUzNDcyMDQ2Mjk5ODYz');
define('PAYHERE_SANDBOX', true);
$base = 'http://localhost/MobiVibe%208/MobiVibe%208';
define('PAYHERE_RETURN_URL', $base . '/payment_return.php');
define('PAYHERE_CANCEL_URL', $base . '/payment_cancel.php');
define('PAYHERE_NOTIFY_URL', $base . '/payhere_notify.php');
?>
