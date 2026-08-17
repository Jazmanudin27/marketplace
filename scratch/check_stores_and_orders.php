<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=marketplace;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== STORES IN DB ===\n";
$stmt = $pdo->query("SELECT * FROM stores");
$stores = $stmt->fetchAll();
print_r($stores);

echo "\n=== TOTAL ORDERS IN DB ===\n";
$stmtCount = $pdo->query("SELECT COUNT(*) as total, order_status FROM orders GROUP BY order_status");
print_r($stmtCount->fetchAll());

echo "\n=== RECENT ORDERS IN DB ===\n";
$stmtRecent = $pdo->query("SELECT id, order_marketplace_id, store_id, order_status, total_amount, net_amount, order_date FROM orders ORDER BY id DESC LIMIT 10");
print_r($stmtRecent->fetchAll());
