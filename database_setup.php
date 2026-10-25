<?php
$host = "localhost";
$user = "root";
$pass = "1234"; 

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
CREATE DATABASE IF NOT EXISTS mobivibe_db;
USE mobivibe_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status VARCHAR(10) DEFAULT 'Not Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    os VARCHAR(50),
    display VARCHAR(100),
    camera VARCHAR(100),
    battery VARCHAR(50),
    processor VARCHAR(100),
    category VARCHAR(50),
    image_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    ram VARCHAR(20),
    storage VARCHAR(20),
    color VARCHAR(30),
    warranty VARCHAR(50),
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wishlists (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (address_id) REFERENCES addresses(address_id)
);

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    transaction_reference VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);
";

if (!$conn->multi_query($sql)) {
    die ("Error creating database: " . $conn->error);
}
/* Drain every result so subsequent queries can run on the same connection. */
do { if ($result = $conn->store_result()) { $result->free(); } } while ($conn->more_results() && $conn->next_result());
$conn->select_db('mobivibe_db');
/* Create a ready-to-use demo administrator so the student can enter the dashboard. */
$adminEmail = 'admin@mobivibe.lk';
$adminPasswordHash = password_hash('Admin@123', PASSWORD_DEFAULT);
$adminFirst = 'MobiVibe';
$adminLast = 'Admin';
$adminRole = 'admin';
$adminStatus = 'Not Active';
$adminStmt = $conn->prepare("INSERT INTO users(first_name,last_name,email,password_hash,role,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),last_name=VALUES(last_name),password_hash=VALUES(password_hash),role='admin'");
$adminStmt->bind_param('ssssss', $adminFirst, $adminLast, $adminEmail, $adminPasswordHash, $adminRole, $adminStatus);
$adminStmt->execute();
$adminStmt->close();
/* Seed the phones already shown by the storefront; existing products are preserved. */
$seed = [
 ['APPLE','iPhone 16 Pro','8GB RAM','128GB',349999,12,'MV-IPH16P-128','https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=700&q=80'],
 ['SAMSUNG','Galaxy S25 Ultra','12GB RAM','256GB',389999,8,'MV-S25U-256','https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=700&q=80'],
 ['GOOGLE','Google Pixel 9 Pro','16GB RAM','256GB',259999,9,'MV-PX9P-256','https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=700&q=80'],
 ['ONEPLUS','OnePlus 13','12GB RAM','256GB',219999,10,'MV-OP13-256','https://images.unsplash.com/photo-1603899122634-f086ca5f5ddd?auto=format&fit=crop&w=700&q=80'],
 ['XIAOMI','Xiaomi 14T Pro','12GB RAM','512GB',189999,7,'MV-X14TP-512','https://images.unsplash.com/photo-1592286927505-1def25115558?auto=format&fit=crop&w=700&q=80'],
 ['NOTHING','Nothing Phone (3)','12GB RAM','256GB',179999,6,'MV-NP3-256','https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?auto=format&fit=crop&w=700&q=80']
];
foreach ($seed as $p) {
 [$brand,$name,$ram,$storage,$price,$stock,$sku,$image]=$p;
 $s=$conn->prepare("INSERT INTO brands(brand_name) VALUES(?) ON DUPLICATE KEY UPDATE brand_name=VALUES(brand_name)");$s->bind_param('s',$brand);$s->execute();$s->close();
 $s=$conn->prepare("SELECT brand_id FROM brands WHERE brand_name=?");$s->bind_param('s',$brand);$s->execute();$brandId=(int)$s->get_result()->fetch_assoc()['brand_id'];$s->close();
 $s=$conn->prepare("SELECT product_id FROM products WHERE product_name=? LIMIT 1");$s->bind_param('s',$name);$s->execute();$found=$s->get_result()->fetch_assoc();$s->close();
 if(!$found){$category='Smartphone';$status='active';$s=$conn->prepare("INSERT INTO products(brand_id,product_name,category,image_url,status) VALUES(?,?,?,?,?)");$s->bind_param('issss',$brandId,$name,$category,$image,$status);$s->execute();$pid=$conn->insert_id;$s->close();}else{$pid=(int)$found['product_id'];}
 $s=$conn->prepare("SELECT variant_id FROM product_variants WHERE sku=?");$s->bind_param('s',$sku);$s->execute();$variant=$s->get_result()->fetch_assoc();$s->close();
 if(!$variant){$s=$conn->prepare("INSERT INTO product_variants(product_id,ram,storage,price,stock_quantity,sku) VALUES(?,?,?,?,?,?)");$s->bind_param('issdis',$pid,$ram,$storage,$price,$stock,$sku);$s->execute();$s->close();}
}
$conn->close();
?>