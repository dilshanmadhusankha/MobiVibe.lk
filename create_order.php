<?php
require_once 'auth_guard.php';require_once 'payhere_config.php';
$user=mv_require_login_page();
function stop_order($msg){http_response_code(400);echo '<!doctype html><meta charset="utf-8"><p>'.htmlspecialchars($msg,ENT_QUOTES,'UTF-8').'</p><p><a href="checkout.php">Go back to checkout</a></p>';exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')stop_order('Invalid request.');
$first=trim($_POST['first_name']??'');$last=trim($_POST['last_name']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');$city=trim($_POST['city']??'');
$cart=json_decode($_POST['cart_json']??'',true);
if(!$first||!$last||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$phone||!$address||!$city||!is_array($cart)||!count($cart))stop_order('Please complete all checkout fields and add at least one item.');
if(count($cart)>30)stop_order('Cart contains too many items.');
try{
 $conn->begin_transaction();
 $s=$conn->prepare("INSERT INTO addresses(user_id,recipient_name,phone,address_line,city,is_default) VALUES(?,?,?,?,?,TRUE)");
 $full=$first.' '.$last;$s->bind_param('issss',$user['user_id'],$full,$phone,$address,$city);$s->execute();$addressId=$conn->insert_id;$s->close();
 $items=[];$total=0;
 foreach($cart as $item){$name=trim((string)($item['name']??''));$qty=(int)($item['qty']??0);if($name===''||$qty<1||$qty>10)throw new RuntimeException('Invalid cart item or quantity.');
  $s=$conn->prepare("SELECT p.product_id,p.product_name,v.variant_id,v.price,v.stock_quantity FROM products p JOIN product_variants v ON v.product_id=p.product_id WHERE p.product_name=? AND p.status='active' AND v.stock_quantity>=? ORDER BY v.variant_id LIMIT 1 FOR UPDATE");$s->bind_param('si',$name,$qty);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();if(!$row)throw new RuntimeException('Product unavailable or stock insufficient: '.$name);
  $items[]=['variant_id'=>(int)$row['variant_id'],'qty'=>$qty,'price'=>(float)$row['price'],'name'=>$row['product_name']];$total+=(float)$row['price']*$qty;
 }
 if($total<=0)throw new RuntimeException('Invalid order total.');
 $status='Pending';$method='PayHere Sandbox';$payment='Pending';$s=$conn->prepare("INSERT INTO orders(user_id,address_id,total_amount,order_status,payment_method,payment_status) VALUES(?,?,?,?,?,?)");$s->bind_param('iidsss',$user['user_id'],$addressId,$total,$status,$method,$payment);$s->execute();$orderId=$conn->insert_id;$s->close();
 foreach($items as $it){$sub=$it['price']*$it['qty'];$s=$conn->prepare("INSERT INTO order_items(order_id,variant_id,quantity,unit_price,subtotal) VALUES(?,?,?,?,?)");$s->bind_param('iiidd',$orderId,$it['variant_id'],$it['qty'],$it['price'],$sub);$s->execute();$s->close();}
 $s=$conn->prepare("INSERT INTO payments(order_id,payment_method,amount,payment_status) VALUES(?,?,?,'Pending')");$s->bind_param('isd',$orderId,$method,$total);$s->execute();$s->close();
 $conn->commit();
}catch(Throwable $e){$conn->rollback();stop_order($e instanceof RuntimeException?$e->getMessage():'Could not create the order. Check database setup and try again.');}
if(PAYHERE_MERCHANT_ID==='YOUR_SANDBOX_MERCHANT_ID'||PAYHERE_MERCHANT_SECRET==='YOUR_SANDBOX_MERCHANT_SECRET'){
 echo '<!doctype html><meta charset="utf-8"><h1>Order #'.(int)$orderId.' created (Pending)</h1><p>PayHere sandbox is not configured yet. Add your sandbox Merchant ID and Secret to <code>payhere_config.php</code>, then retry checkout. This order is not paid.</p><a href="account.html">My account</a>';exit;
}
$merchant=PAYHERE_MERCHANT_ID;$secret=strtoupper(md5(PAYHERE_MERCHANT_SECRET));$amount=number_format($total,2,'.','');$currency='LKR';$hash=strtoupper(md5($merchant.$orderId.$amount.$currency.$secret));$name=$first.' '.$last;$parts=preg_split('/\s+/',trim($name),2);$f=$parts[0]??$first;$l=$parts[1]??$last;
?><!doctype html><html><head><meta charset="utf-8"><title>Redirecting to PayHere</title></head><body><p>Redirecting order #<?= (int)$orderId ?> to PayHere Sandbox…</p><form id="payhere" method="post" action="<?=PAYHERE_SANDBOX?'https://sandbox.payhere.lk/pay/checkout':'https://www.payhere.lk/pay/checkout'?>"><?php foreach(['merchant_id'=>$merchant,'return_url'=>PAYHERE_RETURN_URL.'?order_id='.$orderId,'cancel_url'=>PAYHERE_CANCEL_URL.'?order_id='.$orderId,'notify_url'=>PAYHERE_NOTIFY_URL,'order_id'=>(string)$orderId,'items'=>'MobiVibe order #'.$orderId,'currency'=>$currency,'amount'=>$amount,'first_name'=>$f,'last_name'=>$l,'email'=>$email,'phone'=>$phone,'address'=>$address,'city'=>$city,'country'=>'Sri Lanka','hash'=>$hash] as $k=>$v):?><input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars((string)$v,ENT_QUOTES)?>"><?php endforeach;?></form><script>document.getElementById('payhere').submit()</script></body></html>