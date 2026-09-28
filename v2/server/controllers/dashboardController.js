import db from '../config/database.js';

/**
 * GET /api/dashboard
 * Fetches dashboard summary statistics (stores count, monthly revenue, total orders, channel breakdown, and recent orders)
 */
export const getDashboardStats = async (req, res) => {
  try {
    // 1. Total connected stores
    const [storesRow] = await db.query('SELECT COUNT(*) as total FROM stores');
    const totalStoresCount = storesRow[0]?.total || 0;

    // 2. Current month revenue from non-cancelled orders
    const [omsetRow] = await db.query(`
      SELECT COALESCE(SUM(net_amount), 0) as total 
      FROM orders 
      WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) AND order_status != 'CANCELLED'
    `);
    const totalOmset = parseFloat(omsetRow[0]?.total || 0);

    // 3. Total all orders count
    const [ordersRow] = await db.query('SELECT COUNT(*) as total FROM orders');
    const totalOrdersCount = ordersRow[0]?.total || 0;

    // 4. Pending ready-to-ship fulfillment orders
    const [pendingRow] = await db.query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'READY_TO_SHIP'");
    const pendingOrdersCount = pendingRow[0]?.total || 0;

    // 5. Channel Breakdown: Shopee
    const [shopeeRow] = await db.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%shopee%'
    `);
    const shopeeOmset = parseFloat(shopeeRow[0]?.total || 0);
    const shopeeOrdersCount = shopeeRow[0]?.orders_count || 0;

    // 6. Channel Breakdown: TikTok
    const [tiktokRow] = await db.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%tiktok%'
    `);
    const tiktokOmset = parseFloat(tiktokRow[0]?.total || 0);
    const tiktokOrdersCount = tiktokRow[0]?.orders_count || 0;

    // 7. Channel Breakdown: Lazada
    const [lazadaRow] = await db.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%lazada%'
    `);
    const lazadaOmset = parseFloat(lazadaRow[0]?.total || 0);
    const lazadaOrdersCount = lazadaRow[0]?.orders_count || 0;

    // 8. Recent 10 marketplace orders
    const [recentOrders] = await db.query(`
      SELECT o.id, 
             COALESCE(o.order_marketplace_id, o.invoice_number, CONCAT('ORD-', o.id)) as order_number, 
             COALESCE(c.name, 'Marketplace') as channel, 
             o.order_date, 
             o.created_at, 
             o.buyer_name, 
             o.total_amount, 
             o.order_status as status, 
             s.store_name
      FROM orders o
      LEFT JOIN stores s ON o.store_id = s.id
      LEFT JOIN channels c ON s.channel_id = c.id
      ORDER BY o.created_at DESC
      LIMIT 10
    `);

    return res.json({
      success: true,
      data: {
        totalStoresCount,
        totalOmset,
        totalOrdersCount,
        pendingOrdersCount,
        shopeeOmset,
        shopeeOrdersCount,
        tiktokOmset,
        tiktokOrdersCount,
        lazadaOmset,
        lazadaOrdersCount,
        recentOrders: recentOrders.map(o => ({
          ...o,
          store: { name: o.store_name || '-' }
        }))
      }
    });
  } catch (err) {
    console.error('[DashboardController] Error fetching dashboard stats:', err);
    return res.status(500).json({ success: false, error: err.message });
  }
};
