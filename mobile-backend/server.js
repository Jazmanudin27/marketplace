const path = require('path');
const dotenv = require('dotenv');

// Load environment variables
dotenv.config({ path: path.join(__dirname, '../.env') });
dotenv.config({ path: path.join(__dirname, '.env') });

const express = require('express');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { query } = require('./db');

const app = express();
const PORT = parseInt(process.env.PORT || '5009', 10);
const JWT_SECRET = process.env.JWT_SECRET || 'erp_mobile_owner_secret_jwt_2026';

app.use(cors());
app.use(express.json());

// Helper Tenant ID resolver
function getTenantId(req) {
  const header = req.headers['x-tenant-id'] || req.query.tenant_id;
  if (header) return parseInt(header, 10);
  return 1;
}

// Format date helper: YYYY-MM-DD
function formatDate(d) {
  return d.toISOString().split('T')[0];
}

// Helper akurat untuk mendeteksi nominal refund/retur (persis Order.php accessor ERP)
function calculateOrderRefund(order, returnMap = {}) {
  // 1. Cek relasi return_orders jika ada
  if (returnMap && returnMap[order.id]) {
    const r = parseFloat(returnMap[order.id]);
    if (r > 0) return r;
  }

  // 2. Cek JSON financial_breakdown
  let fb = order.financial_breakdown;
  if (typeof fb === 'string') {
    try { fb = JSON.parse(fb); } catch (_) { fb = {}; }
  }
  if (fb && typeof fb === 'object') {
    const keys = [
      'customer_refund_amount', 'gross_sales_refund_amount', 'seller_return_refund',
      'buyer_return_refund_amount', 'refund_amount', 'return_amount',
      'customer_order_refund_amount', 'total_adjustment_amount'
    ];
    for (const k of keys) {
      if (fb[k] && parseFloat(fb[k]) !== 0) {
        return Math.abs(parseFloat(fb[k]));
      }
    }
    const stmtList = fb.statement_transactions || fb.statement_transaction_list || fb.transactions || [];
    if (Array.isArray(stmtList)) {
      for (const st of stmtList) {
        if (!st || typeof st !== 'object') continue;
        for (const k of keys) {
          if (st[k] && parseFloat(st[k]) !== 0) {
            return Math.abs(parseFloat(st[k]));
          }
        }
      }
    }
  }

  // 3. Fallback jika status pesanan RETURNED / REFUNDED / RETUR / CANCELLED
  const st = (order.order_status || '').toUpperCase();
  if (['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL'].includes(st)) {
    return parseFloat(order.total_amount || 0);
  }

  return 0;
}

// ─── 1. AUTH: LOGIN ──────────────────────────────────────────────────────────
app.post('/api/v2/owner/login', async (req, res) => {
  try {
    const { login, password } = req.body;
    if (!login || !password) {
      return res.status(400).json({ success: false, message: 'Email/username dan kata sandi wajib diisi.' });
    }

    const users = await query(
      'SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1',
      [login, login]
    );

    if (!users || users.length === 0) {
      return res.status(401).json({ success: false, message: 'Email atau kata sandi tidak cocok.' });
    }

    const user = users[0];
    const hash = (user.password || '').replace(/^\$2y\$/, '$2a$');
    const isMatch = bcrypt.compareSync(password, hash);

    if (!isMatch) {
      return res.status(401).json({ success: false, message: 'Email atau kata sandi tidak cocok.' });
    }

    const tenantId = user.tenant_id || 1;
    const token = jwt.sign(
      { id: user.id, email: user.email, tenant_id: tenantId },
      JWT_SECRET,
      { expiresIn: '30d' }
    );

    let tenantName = 'Store Owner';
    const tenants = await query('SELECT name FROM tenants WHERE id = ? LIMIT 1', [tenantId]);
    if (tenants.length > 0) tenantName = tenants[0].name;

    return res.json({
      success: true,
      message: 'Login berhasil.',
      data: {
        token,
        user: {
          id: user.id,
          name: user.name,
          email: user.email,
          username: user.username,
          role: user.role || 'Business Owner',
          tenant_id: tenantId,
          tenantName,
        }
      }
    });
  } catch (err) {
    console.error('[Node-API] Login error:', err);
    return res.status(500).json({ success: false, message: 'Terjadi kesalahan server: ' + err.message });
  }
});

// ─── 2. METRICS OVERVIEW ────────────────────────────────────────────────────
app.get('/api/v2/owner/metrics/overview', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const startOfMonthStr = formatDate(startOfMonth) + ' 00:00:00';
    const todayStr = formatDate(now);

    // 1. Order Status Counters
    const [qNew] = await query(
      `SELECT COUNT(*) AS cnt FROM orders 
       WHERE tenant_id = ? AND UPPER(order_status) IN ('NEW', 'UNPAID', 'PENDING')`,
      [tenantId]
    );
    const [qShip] = await query(
      `SELECT COUNT(*) AS cnt FROM orders 
       WHERE tenant_id = ? AND UPPER(order_status) IN ('READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM')`,
      [tenantId]
    );
    const [qRet] = await query(
      `SELECT COUNT(*) AS cnt FROM orders 
       WHERE tenant_id = ? AND UPPER(order_status) IN ('RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'CANCELLED', 'CANCELED') 
       AND created_at >= NOW() - INTERVAL 30 DAY`,
      [tenantId]
    );

    // 2. Completed orders in current month
    const validStatuses = "('COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR')";
    const monthOrders = await query(
      `SELECT o.*, oi.quantity, oi.total_price, oi.cost_price, mp.cost_price as master_cost_price
       FROM orders o
       LEFT JOIN order_items oi ON oi.order_id = o.id
       LEFT JOIN master_products mp ON mp.id = oi.master_product_id
       WHERE o.tenant_id = ? 
         AND UPPER(o.order_status) IN ${validStatuses}
         AND (o.completed_at >= ? OR (o.completed_at IS NULL AND o.order_date >= ?))`,
      [tenantId, startOfMonthStr, startOfMonthStr]
    );

    // Return orders map untuk refund akurat
    let returnMap = {};
    try {
      const returnRows = await query('SELECT order_id, refund_amount FROM return_orders WHERE tenant_id = ?', [tenantId]);
      for (const rr of returnRows) {
        returnMap[rr.order_id] = parseFloat(rr.refund_amount || 0);
      }
    } catch (_) {}

    const orderMap = new Map();
    let monthGrossSales = 0;
    let monthRefund = 0;
    let monthFee = 0;
    let monthHpp = 0;
    let monthReleased = 0;

    for (const row of monthOrders) {
      if (!orderMap.has(row.id)) {
        orderMap.set(row.id, true);
        const gSales = parseFloat(row.total_amount || 0);
        const rFund = calculateOrderRefund(row, returnMap);
        const fee = Math.abs(parseFloat(row.marketplace_fee || 0));

        monthGrossSales += gSales;
        monthRefund += rFund;
        monthFee += fee;

        let rel = parseFloat(row.net_amount || 0);
        if (rel <= 0) {
          rel = Math.max(0, gSales - rFund - fee);
          if (rel <= 0) {
            rel = Math.max(0, gSales - rFund);
          }
        }
        monthReleased += rel;
      }
      const qty = parseInt(row.quantity || 0, 10);
      const cost = parseFloat(row.cost_price || row.master_cost_price || 0);
      monthHpp += (cost * qty);
    }

    const monthOmset = monthGrossSales > 0 ? monthGrossSales : monthReleased;
    const monthMargin = Math.max(0, monthReleased - monthHpp);

    // 3. Marketing Teams Target & Realisasi
    const teams = await query(
      'SELECT * FROM marketing_teams WHERE tenant_id = ? AND is_active = 1',
      [tenantId]
    );

    let targetMonthlyMargin = 0;
    let actualMonthlyMargin = 0;
    let totalEarnedCommission = 0;

    for (const team of teams) {
      targetMonthlyMargin += parseFloat(team.target_omset || 0);

      // Store IDs for this team
      const storeLinks = await query(
        'SELECT store_id FROM marketing_team_stores WHERE marketing_team_id = ?',
        [team.id]
      );
      const storeIds = storeLinks.map(s => s.store_id);

      let teamMargin = 0;
      if (storeIds.length > 0) {
        let dateCondition = '';
        const params = [storeIds];

        if (team.date_from && team.date_to) {
          dateCondition = 'AND COALESCE(o.completed_at, o.updated_at, o.order_date) BETWEEN ? AND ?';
          params.push(formatDate(new Date(team.date_from)) + ' 00:00:00');
          params.push(formatDate(new Date(team.date_to)) + ' 23:59:59');
        } else if (team.period_month && team.period_year) {
          dateCondition = 'AND MONTH(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ? AND YEAR(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ?';
          params.push(team.period_month, team.period_year);
        }

        const teamRows = await query(
          `SELECT o.id, o.net_amount, o.total_amount, o.marketplace_fee,
                  oi.quantity, oi.cost_price, mp.cost_price as master_cost_price
           FROM orders o
           LEFT JOIN order_items oi ON oi.order_id = o.id
           LEFT JOIN master_products mp ON mp.id = oi.master_product_id
           WHERE o.store_id IN (?)
             AND UPPER(o.order_status) IN ('COMPLETED', 'RELEASED', 'COMPLETED_ESCROW', 'SELESAI', 'DELIVERED', 'FINISHED')
             AND UPPER(o.order_status) NOT IN ('CANCELLED', 'CANCELED', 'BATAL', 'RETURNED', 'REFUNDED')
             ${dateCondition}`,
          params
        );

        const seenOrders = new Set();
        let teamVal = 0;
        let teamHpp = 0;
        for (const tr of teamRows) {
          if (!seenOrders.has(tr.id)) {
            seenOrders.add(tr.id);
            const rFund = calculateOrderRefund(tr, returnMap);
            let rel = parseFloat(tr.net_amount || 0);
            if (rel <= 0) {
              rel = Math.max(0, parseFloat(tr.total_amount || 0) - rFund - Math.abs(parseFloat(tr.marketplace_fee || 0)));
              if (rel <= 0) {
                rel = Math.max(0, parseFloat(tr.total_amount || 0) - rFund);
              }
            }
            teamVal += rel;
          }
          const q = parseInt(tr.quantity || 0, 10);
          const c = parseFloat(tr.cost_price || tr.master_cost_price || 0);
          teamHpp += (c * q);
        }
        teamMargin = Math.max(0, teamVal - teamHpp);
      }

      actualMonthlyMargin += teamMargin;

      // Komisi reward
      const cType = team.commission_type || 'percentage';
      let r = 0;
      if (cType === 'percentage') {
        r = teamMargin * (parseFloat(team.commission_rate || 0) / 100);
        if (parseFloat(team.target_omset || 0) > 0 && teamMargin >= parseFloat(team.target_omset || 0)) {
          r += parseFloat(team.reward_fixed_nominal || 0);
        }
      } else if (cType === 'nominal') {
        if (parseFloat(team.target_omset || 0) > 0 && teamMargin >= parseFloat(team.target_omset || 0)) {
          r = parseFloat(team.reward_fixed_nominal || 0);
        }
      }
      totalEarnedCommission += r;
    }

    const targetProgressPercent = targetMonthlyMargin > 0
      ? Math.round((actualMonthlyMargin / targetMonthlyMargin) * 1000) / 10
      : 0;

    // 4. Stores & Channels
    const stores = await query(
      `SELECT s.*, c.name as channel_name 
       FROM stores s 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE s.tenant_id = ?`,
      [tenantId]
    );
    const connectedMarketplaces = [...new Set(stores.map(s => s.channel_name).filter(Boolean))];

    // 5. Saldo Kas & Escrow
    let cashBalance = 0;
    try {
      const [bankRow] = await query(
        'SELECT SUM(current_balance) AS total FROM bank_accounts WHERE tenant_id = ? AND is_active = 1',
        [tenantId]
      );
      cashBalance = parseFloat(bankRow?.total || 0);
    } catch (_) {}

    const [escrowRow] = await query(
      `SELECT SUM(total_amount) AS total FROM orders 
       WHERE tenant_id = ? AND UPPER(order_status) IN ('SHIPPED', 'READY_TO_SHIP')`,
      [tenantId]
    );
    const receivableEscrow = parseFloat(escrowRow?.total || 0);

    // 6. Owner profile
    const users = await query('SELECT * FROM users WHERE tenant_id = ? LIMIT 1', [tenantId]);
    const ownerUser = users[0] || {};
    const tenants = await query('SELECT name FROM tenants WHERE id = ? LIMIT 1', [tenantId]);
    const tenantName = tenants[0]?.name || '';

    return res.json({
      success: true,
      data: {
        ordersNew: qNew?.cnt || 0,
        ordersToShip: qShip?.cnt || 0,
        ordersReturned: qRet?.cnt || 0,
        ordersCompletedToday: orderMap.size,
        ordersCompletedMonth: orderMap.size,
        todayOmset: Math.round(monthOmset),
        todayMargin: Math.round(monthMargin),
        todayHpp: Math.round(monthHpp),
        todayMarketplaceFee: Math.round(monthFee),
        todayRefund: Math.round(monthRefund),
        monthOmset: Math.round(monthOmset),
        monthMargin: Math.round(monthMargin),
        monthHpp: Math.round(monthHpp),
        monthMarketplaceFee: Math.round(monthFee),
        monthRefund: Math.round(monthRefund),
        targetMonthlyMargin: Math.round(targetMonthlyMargin),
        actualMonthlyMargin: Math.round(actualMonthlyMargin),
        targetProgressPercent,
        totalEarnedCommission: Math.round(totalEarnedCommission),
        cashBalance: Math.round(cashBalance),
        receivableEscrow: Math.round(receivableEscrow),
        storesCount: stores.length,
        connectedMarketplaces,
      },
      profile: {
        name: ownerUser.name || 'Owner',
        role: ownerUser.role || 'Business Owner',
        tenantName,
        avatarInitials: ((ownerUser.name || 'OW').substring(0, 2)).toUpperCase(),
        email: ownerUser.email || '',
        storesCount: stores.length,
        connectedMarketplaces,
      }
    });
  } catch (err) {
    console.error('[Node-API] Overview error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 3. ORDERS LIST ─────────────────────────────────────────────────────────
app.get('/api/v2/owner/orders', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const statusTab = req.query.status || 'all'; // all, perlu_dikirim, dikirim
    const subStatus = req.query.sub_status;      // unprocessed, processed
    const search = req.query.search;
    const startDate = req.query.start_date;
    const endDate = req.query.end_date;

    const notCancelled = "UPPER(o.order_status) NOT IN ('CANCELLED', 'CANCELED', 'BATAL', 'IN_CANCEL')";
    const perluDikirimStatuses = "('NEW', 'UNPAID', 'PENDING', 'READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM')";
    const unprocessedStatuses = "('NEW', 'UNPAID', 'PENDING')";
    const processedStatuses = "('READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM')";
    const dikirimStatuses = "('SHIPPED', 'ON_DELIVERY', 'DIKIRIM')";

    // Tab badges counter
    const [cAll] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND ${notCancelled}`, [tenantId]);
    const [cPerlu] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN ${perluDikirimStatuses}`, [tenantId]);
    const [cUnproc] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN ${unprocessedStatuses}`, [tenantId]);
    const [cProc] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN ${processedStatuses}`, [tenantId]);
    const [cKirim] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN ${dikirimStatuses}`, [tenantId]);

    // Build filter query
    let whereClause = `o.tenant_id = ? AND ${notCancelled}`;
    const params = [tenantId];

    if (statusTab === 'perlu_dikirim') {
      if (subStatus === 'unprocessed') {
        whereClause += ` AND UPPER(o.order_status) IN ${unprocessedStatuses}`;
      } else if (subStatus === 'processed') {
        whereClause += ` AND UPPER(o.order_status) IN ${processedStatuses}`;
      } else {
        whereClause += ` AND UPPER(o.order_status) IN ${perluDikirimStatuses}`;
      }
    } else if (statusTab === 'dikirim' || statusTab === 'shipped') {
      whereClause += ` AND UPPER(o.order_status) IN ${dikirimStatuses}`;
    }

    if (search) {
      whereClause += ` AND (o.order_marketplace_id LIKE ? OR o.buyer_name LIKE ? OR o.tracking_number LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s);
    }

    if (startDate) {
      whereClause += ` AND o.order_date >= ?`;
      params.push(startDate + ' 00:00:00');
    }
    if (endDate) {
      whereClause += ` AND o.order_date <= ?`;
      params.push(endDate + ' 23:59:59');
    }

    const orders = await query(
      `SELECT o.*, s.store_name, c.name as channel_name 
       FROM orders o 
       LEFT JOIN stores s ON s.id = o.store_id 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE ${whereClause} 
       ORDER BY o.order_date DESC, o.id DESC 
       LIMIT 50`,
      params
    );

    // Attach items for each order
    const orderIds = orders.map(o => o.id);
    let itemsByOrderId = {};
    if (orderIds.length > 0) {
      const items = await query(
        `SELECT oi.*, mp.sku as master_sku 
         FROM order_items oi 
         LEFT JOIN master_products mp ON mp.id = oi.master_product_id 
         WHERE oi.order_id IN (?)`,
        [orderIds]
      );
      for (const item of items) {
        if (!itemsByOrderId[item.order_id]) itemsByOrderId[item.order_id] = [];
        const unitP = parseFloat(item.unit_price || 0);
        const totP = parseFloat(item.total_price || 0);
        const qty = parseInt(item.quantity || 1, 10);
        const price = unitP > 0 ? unitP : (qty > 0 && totP > 0 ? totP / qty : totP);

        itemsByOrderId[item.order_id].push({
          id: item.id,
          name: item.product_name,
          sku: item.master_sku || item.sku || '-',
          qty,
          price,
          totalPrice: totP > 0 ? totP : (price * qty),
        });
      }
    }

    const formattedOrders = orders.map(ord => ({
      id: ord.id,
      orderNumber: ord.order_marketplace_id || `ORD-${ord.id}`,
      storeName: ord.store_name || 'Marketplace Store',
      channel: ord.channel_name || 'Shopee',
      orderDate: ord.order_date,
      status: ord.order_status,
      isProcessed: ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'].includes((ord.order_status || '').toUpperCase()),
      totalAmount: parseFloat(ord.total_amount || 0),
      netAmount: parseFloat(ord.net_amount || 0),
      buyerName: ord.buyer_name || 'Pembeli',
      resi: ord.tracking_number || '-',
      courier: ord.courier || 'Reguler',
      itemsCount: (itemsByOrderId[ord.id] || []).reduce((acc, it) => acc + (it.qty || 1), 0),
      items: itemsByOrderId[ord.id] || [],
    }));

    return res.json({
      success: true,
      badges: {
        all: cAll?.cnt || 0,
        perlu_dikirim: cPerlu?.cnt || 0,
        unprocessed: cUnproc?.cnt || 0,
        processed: cProc?.cnt || 0,
        dikirim: cKirim?.cnt || 0,
      },
      data: formattedOrders,
    });
  } catch (err) {
    console.error('[Node-API] Orders error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 4. ORDER DETAIL ────────────────────────────────────────────────────────
app.get('/api/v2/owner/orders/:id', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const orderId = req.params.id;

    const orders = await query(
      `SELECT o.*, s.store_name, c.name as channel_name 
       FROM orders o 
       LEFT JOIN stores s ON s.id = o.store_id 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE o.id = ? AND o.tenant_id = ? LIMIT 1`,
      [orderId, tenantId]
    );

    if (orders.length === 0) {
      return res.status(404).json({ success: false, message: 'Pesanan tidak ditemukan.' });
    }

    const ord = orders[0];
    const items = await query(
      `SELECT oi.*, mp.sku as master_sku, mp.name as master_name 
       FROM order_items oi 
       LEFT JOIN master_products mp ON mp.id = oi.master_product_id 
       WHERE oi.order_id = ?`,
      [ord.id]
    );

    return res.json({
      success: true,
      data: {
        id: ord.id,
        orderNumber: ord.order_marketplace_id || `ORD-${ord.id}`,
        storeName: ord.store_name,
        channel: ord.channel_name,
        status: ord.order_status,
        orderDate: ord.order_date,
        completedAt: ord.completed_at,
        buyerName: ord.buyer_name,
        buyerPhone: ord.buyer_phone,
        shippingAddress: ord.shipping_address,
        courier: ord.courier,
        resi: ord.tracking_number,
        totalAmount: parseFloat(ord.total_amount || 0),
        netAmount: parseFloat(ord.net_amount || 0),
        marketplaceFee: Math.abs(parseFloat(ord.marketplace_fee || 0)),
        items: items.map(it => ({
          id: it.id,
          name: it.product_name || it.master_name,
          sku: it.master_sku || it.sku,
          qty: it.quantity,
          unitPrice: parseFloat(it.unit_price || 0),
          totalPrice: parseFloat(it.total_price || 0),
        }))
      }
    });
  } catch (err) {
    console.error('[Node-API] Order detail error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 5. NOTIFICATIONS ───────────────────────────────────────────────────────
app.get('/api/v2/owner/notifications', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const needShip = "('NEW', 'UNPAID', 'PENDING', 'READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM')";

    const [cRow] = await query(
      `SELECT COUNT(*) as cnt FROM orders 
       WHERE tenant_id = ? AND UPPER(order_status) IN ${needShip}`,
      [tenantId]
    );

    const orders = await query(
      `SELECT o.*, s.store_name, c.name as channel_name 
       FROM orders o 
       LEFT JOIN stores s ON s.id = o.store_id 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE o.tenant_id = ? AND UPPER(o.order_status) IN ${needShip} 
       ORDER BY o.order_date DESC, o.id DESC 
       LIMIT 5`,
      [tenantId]
    );

    return res.json({
      success: true,
      unreadCount: cRow?.cnt || 0,
      notifications: orders.map(o => ({
        id: o.id,
        orderNumber: o.order_marketplace_id || `ORD-${o.id}`,
        storeName: o.store_name,
        channel: o.channel_name,
        totalAmount: parseFloat(o.total_amount || 0),
        orderDate: o.order_date,
        status: o.order_status,
      }))
    });
  } catch (err) {
    console.error('[Node-API] Notifications error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 6. TARGET KOMISI ───────────────────────────────────────────────────────
app.get('/api/v2/owner/target-komisi', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const reqMonth = req.query.month ? parseInt(req.query.month, 10) : null;
    const reqYear = req.query.year ? parseInt(req.query.year, 10) : null;

    let whereClause = 'tenant_id = ?';
    const params = [tenantId];
    if (reqMonth) {
      whereClause += ' AND period_month = ?';
      params.push(reqMonth);
    }
    if (reqYear) {
      whereClause += ' AND period_year = ?';
      params.push(reqYear);
    }

    const teams = await query(
      `SELECT * FROM marketing_teams WHERE ${whereClause} ORDER BY id DESC`,
      params
    );

    // Available years
    const orderYears = await query(
      'SELECT DISTINCT YEAR(order_date) as yr FROM orders WHERE tenant_id = ? AND order_date IS NOT NULL',
      [tenantId]
    );
    const currentYear = new Date().getFullYear();
    const availableYears = [...new Set([currentYear, ...orderYears.map(r => r.yr).filter(Boolean)])].sort((a,b) => b - a);

    let totalTargetMargin = 0;
    let totalTargetQty = 0;
    let totalActualMargin = 0;
    let totalActualQty = 0;
    let totalReward = 0;
    const formattedTeams = [];

    for (const team of teams) {
      const tTarget = parseFloat(team.target_omset || 0);
      const tTargetQty = parseInt(team.target_qty || 0, 10);
      const isActive = Boolean(team.is_active);

      // Stores linked
      const storeRows = await query(
        `SELECT s.id, s.store_name, c.name as channel_name 
         FROM marketing_team_stores mts 
         JOIN stores s ON s.id = mts.store_id 
         LEFT JOIN channels c ON c.id = s.channel_id 
         WHERE mts.marketing_team_id = ?`,
        [team.id]
      );
      const storeIds = storeRows.map(s => s.id);

      let actMargin = 0;
      let actQty = 0;
      let actOmset = 0;
      let actHpp = 0;

      if (storeIds.length > 0) {
        let dateCondition = '';
        const qParams = [storeIds];

        if (reqMonth && reqYear) {
          dateCondition = 'AND MONTH(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ? AND YEAR(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ?';
          qParams.push(reqMonth, reqYear);
        } else if (team.date_from && team.date_to) {
          dateCondition = 'AND COALESCE(o.completed_at, o.updated_at, o.order_date) BETWEEN ? AND ?';
          qParams.push(formatDate(new Date(team.date_from)) + ' 00:00:00');
          qParams.push(formatDate(new Date(team.date_to)) + ' 23:59:59');
        } else if (team.period_month && team.period_year) {
          dateCondition = 'AND MONTH(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ? AND YEAR(COALESCE(o.completed_at, o.updated_at, o.order_date)) = ?';
          qParams.push(team.period_month, team.period_year);
        }

        const orders = await query(
          `SELECT o.id, o.net_amount, o.total_amount, o.marketplace_fee,
                  oi.quantity, oi.cost_price, mp.cost_price as master_cost_price
           FROM orders o 
           LEFT JOIN order_items oi ON oi.order_id = o.id 
           LEFT JOIN master_products mp ON mp.id = oi.master_product_id 
           WHERE o.store_id IN (?)
             AND UPPER(o.order_status) IN ('COMPLETED', 'RELEASED', 'COMPLETED_ESCROW', 'SELESAI', 'DELIVERED', 'FINISHED')
             AND UPPER(o.order_status) NOT IN ('CANCELLED', 'CANCELED', 'BATAL', 'RETURNED', 'REFUNDED')
             ${dateCondition}`,
          qParams
        );

        const seenOrders = new Set();
        for (const ord of orders) {
          if (!seenOrders.has(ord.id)) {
            seenOrders.add(ord.id);
            const rFund = calculateOrderRefund(ord, returnMap);
            let released = parseFloat(ord.net_amount || 0);
            if (released <= 0) {
              released = Math.max(0, parseFloat(ord.total_amount || 0) - rFund - Math.abs(parseFloat(ord.marketplace_fee || 0)));
              if (released <= 0) {
                released = Math.max(0, parseFloat(ord.total_amount || 0) - rFund);
              }
            }
            actOmset += Math.max(0, parseFloat(ord.total_amount || 0) - rFund);
            actMargin += released;
          }
          const q = parseInt(ord.quantity || 0, 10);
          const c = parseFloat(ord.cost_price || ord.master_cost_price || 0);
          actQty += q;
          actHpp += (c * q);
        }
        actMargin = Math.max(0, actMargin - actHpp);
      }

      // Calculate reward
      const cType = team.commission_type || 'percentage';
      let reward = 0;
      if (cType === 'percentage') {
        reward = actMargin * (parseFloat(team.commission_rate || 0) / 100);
        if (tTarget > 0 && actMargin >= tTarget && parseFloat(team.reward_fixed_nominal || 0) > 0) {
          reward += parseFloat(team.reward_fixed_nominal || 0);
        }
      } else if (cType === 'nominal') {
        if (tTarget > 0 && actMargin >= tTarget) {
          reward = parseFloat(team.reward_fixed_nominal || 0);
        }
      } else {
        reward = actQty * parseFloat(team.reward_per_qty || 0);
      }

      const progressPct = tTarget > 0 ? Math.min(100, Math.round((actMargin / tTarget) * 1000) / 10) : 0;

      if (isActive) {
        totalTargetMargin += tTarget;
        totalTargetQty += tTargetQty;
        totalActualMargin += actMargin;
        totalActualQty += actQty;
        totalReward += reward;
      }

      // Period label
      let pLabel = 'Semua Periode';
      if (team.date_from && team.date_to) {
        pLabel = `${formatDate(new Date(team.date_from))} – ${formatDate(new Date(team.date_to))}`;
      } else if (team.period_month && team.period_year) {
        pLabel = `Bulan ${team.period_month} ${team.period_year}`;
      }

      formattedTeams.push({
        id: team.id,
        name: team.name,
        description: team.description,
        periodLabel: pLabel,
        periodMonth: team.period_month,
        periodYear: team.period_year,
        isActive,
        targetMargin: Math.round(tTarget),
        targetQty: tTargetQty,
        actualMargin: Math.round(actMargin),
        actualQty: actQty,
        actualOmset: Math.round(actOmset),
        actualHpp: Math.round(actHpp),
        progressPercent: progressPct,
        rewardEarned: Math.round(reward),
        commissionType: cType,
        commissionRate: parseFloat(team.commission_rate || 0),
        rewardFixedNominal: parseFloat(team.reward_fixed_nominal || 0),
        rewardPerQty: parseFloat(team.reward_per_qty || 0),
        stores: storeRows.map(s => ({
          id: s.id,
          name: s.store_name,
          channel: s.channel_name || 'POS',
        }))
      });
    }

    const overallProgress = totalTargetMargin > 0
      ? Math.round((totalActualMargin / totalTargetMargin) * 1000) / 10
      : 0;

    const [stCount] = await query('SELECT COUNT(*) as cnt FROM stores WHERE tenant_id = ?', [tenantId]);
    const allLinkedStores = new Set();
    formattedTeams.forEach(t => t.stores.forEach(s => allLinkedStores.add(s.id)));

    return res.json({
      success: true,
      summary: {
        totalTargetMargin: Math.round(totalTargetMargin),
        totalTargetQty,
        totalActualMargin: Math.round(totalActualMargin),
        totalActualQty,
        totalReward: Math.round(totalReward),
        progressPercent: overallProgress,
        totalTeams: teams.length,
        activeTeamsCount: teams.filter(t => t.is_active).length,
        totalStoresLinked: allLinkedStores.size,
        totalStoresCount: stCount?.cnt || 0,
        availableYears,
        selectedMonth: reqMonth,
        selectedYear: reqYear,
      },
      teams: formattedTeams,
    });
  } catch (err) {
    console.error('[Node-API] Target komisi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 7. LABA RUGI ───────────────────────────────────────────────────────────
app.get('/api/v2/owner/laba-rugi', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const period = req.query.period || 'month'; // today, month, year

    const now = new Date();
    let dateFrom, dateTo, periodLabel;

    if (period === 'today') {
      dateFrom = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
      dateTo = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
      periodLabel = formatDate(now);
    } else if (period === 'year') {
      dateFrom = new Date(now.getFullYear(), 0, 1, 0, 0, 0);
      dateTo = new Date(now.getFullYear(), 11, 31, 23, 59, 59);
      periodLabel = 'Tahun ' + now.getFullYear();
    } else {
      dateFrom = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0, 0);
      dateTo = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
      periodLabel = `Bulan ${now.getMonth() + 1} ${now.getFullYear()}`;
    }

    const fromStr = formatDate(dateFrom) + ' 00:00:00';
    const toStr = formatDate(dateTo) + ' 23:59:59';
    const validStatuses = "('COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR')";

    // 1. Online Revenue & HPP
    const onlineRows = await query(
      `SELECT o.id, o.net_amount, o.total_amount, o.marketplace_fee,
              oi.quantity, oi.cost_price, mp.cost_price as master_cost_price
       FROM orders o
       LEFT JOIN order_items oi ON oi.order_id = o.id
       LEFT JOIN master_products mp ON mp.id = oi.master_product_id
       WHERE o.tenant_id = ?
         AND UPPER(o.order_status) IN ${validStatuses}
         AND (o.completed_at BETWEEN ? AND ? OR (o.completed_at IS NULL AND o.order_date BETWEEN ? AND ?))`,
      [tenantId, fromStr, toStr, fromStr, toStr]
    );

    const seenOrders = new Set();
    let onlineGross = 0;
    let onlineNet = 0;
    let onlineFee = 0;
    let onlineHpp = 0;

    for (const r of onlineRows) {
      if (!seenOrders.has(r.id)) {
        seenOrders.add(r.id);
        onlineGross += parseFloat(r.total_amount || 0);
        onlineNet += parseFloat(r.net_amount || 0);
        onlineFee += Math.abs(parseFloat(r.marketplace_fee || 0));
      }
      const q = parseInt(r.quantity || 0, 10);
      const c = parseFloat(r.cost_price || r.master_cost_price || 0);
      onlineHpp += (c * q);
    }
    const onlineRevenue = onlineNet > 0 ? onlineNet : onlineGross;

    // 2. Offline / POS Sales (sold_at or created_at)
    let offlineRevenue = 0;
    let offlineHpp = 0;
    try {
      const offlineRows = await query(
        `SELECT os.id, os.grand_total, os.total_amount,
                osi.quantity, mp.cost_price as master_cost_price
         FROM offline_sales os
         LEFT JOIN offline_sale_items osi ON osi.offline_sale_id = os.id
         LEFT JOIN master_products mp ON mp.id = osi.master_product_id
         WHERE os.tenant_id = ? AND os.status = 'completed'
           AND (os.sold_at BETWEEN ? AND ? OR (os.sold_at IS NULL AND os.created_at BETWEEN ? AND ?))`,
        [tenantId, fromStr, toStr, fromStr, toStr]
      );

      const seenOffline = new Set();
      for (const off of offlineRows) {
        if (!seenOffline.has(off.id)) {
          seenOffline.add(off.id);
          offlineRevenue += parseFloat(off.grand_total || off.total_amount || 0);
        }
        const q = parseInt(off.quantity || 0, 10);
        const c = parseFloat(off.master_cost_price || 0);
        offlineHpp += (c * q);
      }
    } catch (_) {}

    const totalOmset = onlineRevenue + offlineRevenue;
    const totalHpp = onlineHpp + offlineHpp;
    const labaKotor = totalOmset - totalHpp;
    const grossMarginPct = totalOmset > 0 ? Math.round((labaKotor / totalOmset) * 1000) / 10 : 0;

    // 3. Operating Expenses
    let totalOperasional = 0;
    const expenseCategories = [];
    try {
      const fromDateOnly = formatDate(dateFrom);
      const toDateOnly = formatDate(dateTo);
      const expenses = await query(
        `SELECT category, SUM(amount) as total 
         FROM expenses 
         WHERE tenant_id = ? AND expense_date BETWEEN ? AND ?
           AND LOWER(category) NOT IN ('pembelian_supplier', 'bahan_baku', 'biaya_produksi', 'supplier_payable')
         GROUP BY category`,
        [tenantId, fromDateOnly, toDateOnly]
      );

      const labelMap = {
        salary: 'Gaji Karyawan',
        rent: 'Sewa Tempat',
        utilities: 'Utilitas & Operasional',
        other: 'Lain-lain',
      };

      for (const exp of expenses) {
        const amt = parseFloat(exp.total || 0);
        totalOperasional += amt;
        const catKey = (exp.category || '').toLowerCase();
        expenseCategories.push({
          name: labelMap[catKey] || exp.category.replace(/_/g, ' '),
          amount: Math.round(amt),
        });
      }
    } catch (_) {}

    const labaBersih = labaKotor - totalOperasional;
    const netMarginPct = totalOmset > 0 ? Math.round((labaBersih / totalOmset) * 1000) / 10 : 0;

    return res.json({
      success: true,
      period: {
        type: period,
        label: periodLabel,
      },
      data: {
        totalOmset: Math.round(totalOmset),
        onlineRevenue: Math.round(onlineRevenue),
        offlineRevenue: Math.round(offlineRevenue),
        totalHpp: Math.round(totalHpp),
        marketplaceFee: Math.round(onlineFee),
        labaKotor: Math.round(labaKotor),
        grossMarginPct,
        totalOperasional: Math.round(totalOperasional),
        expenseCategories,
        labaBersih: Math.round(labaBersih),
        netMarginPct,
        ordersCount: seenOrders.size,
      }
    });
  } catch (err) {
    console.error('[Node-API] Laba rugi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'mobile-owner-backend', time: new Date() });
});

app.listen(PORT, () => {
  console.log(`[Node-API] Mobile Owner Backend listening on http://localhost:${PORT}`);
});
