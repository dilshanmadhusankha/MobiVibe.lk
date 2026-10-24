<?php
require_once 'auth_guard.php';header('Content-Type: application/json; charset=utf-8');
$user=mv_get_logged_in_user($conn);if(!$user){echo json_encode(['success'=>false,'orders'=>[]]);exit;}
$s=$conn->prepare("SELECT order_id,total_amount,order_status,payment_status,order_date FROM orders WHERE user_id=? ORDER BY order_id DESC");$s->bind_param('i',$user['user_id']);$s->execute();$orders=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();echo json_encode(['success'=>true,'orders'=>$orders]);
?>