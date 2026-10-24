<?php
require_once 'db_connection.php';require_once 'payhere_config.php';
/* PayHere server-to-server notification: verify signature and amount before marking paid. */
$merchant=$_POST['merchant_id']??'';$orderId=(int)($_POST['order_id']??0);$payhereAmount=(string)($_POST['payhere_amount']??'');$payhereCurrency=(string)($_POST['payhere_currency']??'');$status=(string)($_POST['status_code']??'');$md5sig=strtoupper((string)($_POST['md5sig']??''));
$localSig=strtoupper(md5($merchant.$orderId.$payhereAmount.$payhereCurrency.$status.strtoupper(md5(PAYHERE_MERCHANT_SECRET))));
if(PAYHERE_MERCHANT_ID==='YOUR_SANDBOX_MERCHANT_ID'||PAYHERE_MERCHANT_SECRET==='YOUR_SANDBOX_MERCHANT_SECRET'||$merchant!==PAYHERE_MERCHANT_ID||!hash_equals($localSig,$md5sig)||$orderId<1){http_response_code(400);exit('Invalid notification');}
$s=$conn->prepare("SELECT total_amount,payment_status FROM orders WHERE order_id=?");$s->bind_param('i',$orderId);$s->execute();$order=$s->get_result()->fetch_assoc();$s->close();
if(!$order){http_response_code(404);exit('Unknown order');}
if(number_format((float)$order['total_amount'],2,'.','')!==number_format((float)$payhereAmount,2,'.','')||$payhereCurrency!=='LKR'){http_response_code(400);exit('Amount mismatch');}
if($status==='2'&&$order['payment_status']!=='Completed'){
 $conn->begin_transaction();
 try{$s=$conn->prepare("UPDATE orders SET payment_status='Completed',order_status='Processing' WHERE order_id=? AND payment_status='Pending'");$s->bind_param('i',$orderId);$s->execute();$s->close();
  $s=$conn->prepare("UPDATE payments SET payment_status='Completed',transaction_reference=? WHERE order_id=?");$paymentId=(string)($_POST['payment_id']??'');$s->bind_param('si',$paymentId,$orderId);$s->execute();$s->close();
  $s=$conn->prepare("SELECT variant_id,quantity FROM order_items WHERE order_id=?");$s->bind_param('i',$orderId);$s->execute();$items=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
  foreach($items as $it){$v=(int)$it['variant_id'];$q=(int)$it['quantity'];$u=$conn->prepare("UPDATE product_variants SET stock_quantity=GREATEST(0,stock_quantity-?) WHERE variant_id=?");$u->bind_param('ii',$q,$v);$u->execute();$u->close();}
  $conn->commit();
 }catch(Throwable $e){$conn->rollback();http_response_code(500);exit('Could not update order');}
}elseif($status==='-1'||$status==='-2'||$status==='-3'){$s=$conn->prepare("UPDATE orders SET payment_status='Failed' WHERE order_id=? AND payment_status='Pending'");$s->bind_param('i',$orderId);$s->execute();$s->close();}
echo 'OK';
?>