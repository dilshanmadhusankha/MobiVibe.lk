<?php
require_once 'db_connection.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
 $sql="SELECT p.product_id,p.product_name,p.description,p.category,p.image_url,b.brand_name,v.variant_id,v.ram,v.storage,v.price,v.stock_quantity,v.sku FROM products p JOIN brands b ON b.brand_id=p.brand_id JOIN product_variants v ON v.product_id=p.product_id WHERE p.status='active' ORDER BY p.product_id,v.variant_id";
 $rows=$conn->query($sql)->fetch_all(MYSQLI_ASSOC);
 echo json_encode(['success'=>true,'products'=>$rows],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
 http_response_code(500); echo json_encode(['success'=>false,'message'=>'Products could not be loaded. Run database_setup.php first.']);
}
?>