<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=marketplace;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $sn = '585148224195429874';
    
    echo "--- ORDERS TABLE ---\n";
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_marketplace_id = ? OR id = ?");
    $stmt->execute([$sn, $sn]);
    $orders = $stmt->fetchAll();
    print_r($orders);

    if (!empty($orders)) {
        $order = $orders[0];
        $orderDbId = $order['id'];

        echo "--- ORDER ITEMS ---\n";
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderDbId]);
        print_r($stmtItems->fetchAll());

        echo "--- STORES TABLE ---\n";
        $stmtStore = $pdo->prepare("SELECT id, name, channel_id, tenant_id FROM stores WHERE id = ?");
        $stmtStore->execute([$order['store_id']]);
        print_r($stmtStore->fetchAll());
    }

    // Check returns/refunds/settlements/statements
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $t) {
        if (preg_match('/return|refund|settle|statement|escrow|finance|tiktok|shopee/i', $t)) {
            $colsStmt = $pdo->query("SHOW COLUMNS FROM `$t`");
            $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $matchCol = null;
            foreach (['order_marketplace_id', 'order_id', 'marketplace_order_id', 'order_sn', 'order_no'] as $c) {
                if (in_array($c, $cols)) {
                    $matchCol = $c;
                    break;
                }
            }

            if ($matchCol) {
                $q = $pdo->prepare("SELECT * FROM `$t` WHERE `$matchCol` = ? OR `$matchCol` = ?");
                $q->execute([$sn, $orders[0]['id'] ?? 0]);
                $res = $q->fetchAll();
                if (!empty($res)) {
                    echo "--- MATCH IN TABLE `$t` (col `$matchCol`) ---\n";
                    print_r($res);
                }
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
