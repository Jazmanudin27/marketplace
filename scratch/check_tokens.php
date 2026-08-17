<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=marketplace;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SELECT * FROM stores');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo 'ID: ' . $r['id'] . ' | Name: ' . $r['store_name'] . ' | Token: ' . (empty($r['access_token']) ? 'EMPTY' : 'FILLED (' . substr($r['access_token'], 0, 10) . '...)') . ' | Cipher: ' . (empty($r['shop_cipher']) ? 'EMPTY' : $r['shop_cipher']) . "\n";
}
