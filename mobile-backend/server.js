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

function formatDate(d) {
  if (!d) return '';
  const dateObj = (d instanceof Date) ? d : new Date(d);
  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
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

/**
 * Hitung Potongan Marketplace / Total Fee Persis Sama dengan ERP ReportController & Order Model
 */
function calculateOrderFee(order) {
  // 1. Jika pesanan sudah RECONCILED: gunakan marketplace_fee raw dari statement API
  if (order.recon_status === 'RECONCILED') {
    const rawFee = Math.abs(parseFloat(order.marketplace_fee || 0));
    if (rawFee > 0) return rawFee;

    // Cek rincian 5 kolom fee riil di tabel orders jika marketplace_fee utama bernilai 0
    const colSum = Math.abs(parseFloat(order.fee_platform_amount || 0))
                 + Math.abs(parseFloat(order.fee_free_shipping_amount || 0))
                 + Math.abs(parseFloat(order.fee_service_amount || 0))
                 + Math.abs(parseFloat(order.fee_promo_amount || 0))
                 + Math.abs(parseFloat(order.fee_other_amount || 0));
    if (colSum > 0) return colSum;
  }

  // 2. Cek JSON financial_breakdown (Shopee / TikTok escrow breakdown)
  let fb = order.financial_breakdown;
  if (typeof fb === 'string') {
    try { fb = JSON.parse(fb); } catch (_) { fb = null; }
  }

  let totalFee = 0;
  if (fb && typeof fb === 'object') {
    let stmtList = fb.statement_list || fb.statements || fb.statement_transactions || [];
    let st0 = (Array.isArray(stmtList) && stmtList[0] && typeof stmtList[0] === 'object') ? stmtList[0] : {};

    // Komisi Platform
    const platformFee = Math.abs(parseFloat(fb.commission_fee || fb.net_platform_commission || fb.platform_commission || fb.platform_fee || st0.platform_commission_amount || 0));

    // Biaya Gratis Ongkir / Program XTRA
    let freeShipping = Math.abs(parseFloat(fb.growth_xtra_fee || fb.free_shipping_fee || fb.shopee_shipping_rebate_fee || st0.growth_xtra_fee_amount || 0));
    if (freeShipping === 0 && fb.service_fee && !fb.net_platform_commission && !fb.platform_commission && !st0.platform_commission_amount) {
      freeShipping = Math.abs(parseFloat(fb.service_fee || 0));
    }

    // Biaya Layanan & Penanganan
    let serviceFee = Math.abs(parseFloat(fb.seller_order_processing_fee || 0))
                   + Math.abs(parseFloat(fb.preorder_service_fee || fb.preorder_fee || st0.preorder_service_fee_amount || 0))
                   + Math.abs(parseFloat(fb.order_processing_fee || st0.transaction_fee_amount || 0));
    if (fb.net_platform_commission && fb.service_fee && freeShipping === 0 && parseFloat(fb.service_fee) !== parseFloat(order.marketplace_fee || 0)) {
      serviceFee += Math.abs(parseFloat(fb.service_fee || 0));
    }

    // Biaya Promosi
    const promoFee = Math.abs(parseFloat(fb.seller_coin_cash_back || 0))
                   + Math.abs(parseFloat(fb.order_ams_commission_fee || fb.ams_commission_fee || 0))
                   + Math.abs(parseFloat(fb.dynamic_commission || fb.affiliate_commission || st0.affiliate_commission_amount || st0.dynamic_commission_amount || 0));

    // Biaya Lainnya & Ongkir Adjustment
    const actualShipping = Math.abs(parseFloat(fb.actual_shipping_fee || st0.actual_shipping_fee_amount || st0.shipping_cost_amount || 0));
    const buyerPaidShipping = Math.abs(parseFloat(fb.buyer_paid_shipping_fee || fb.shipping_fee_paid_by_buyer || st0.customer_paid_shipping_fee_amount || 0));
    const shopeeRebate = Math.abs(parseFloat(fb.shopee_shipping_rebate || fb.shipping_fee_subsidy || st0.platform_shipping_fee_discount_amount || 0));
    let shippingAdjustment = Math.abs(parseFloat(fb.shipping_fee_adjustment || 0));
    if (shippingAdjustment <= 0 && actualShipping > (buyerPaidShipping + shopeeRebate) && (buyerPaidShipping + shopeeRebate) > 0) {
      shippingAdjustment = Math.max(0, actualShipping - (buyerPaidShipping + shopeeRebate));
    }

    let otherFee = Math.abs(parseFloat(fb.seller_transaction_fee || fb.transaction_fee || 0))
                 + shippingAdjustment
                 + Math.abs(parseFloat(fb.shipping_seller_protection_fee_amount || fb.delivery_seller_protection_fee_premium_amount || 0))
                 + Math.abs(parseFloat(fb.withholding_tax || fb.escrow_tax || fb.vat || fb.buyer_tax_amount || 0))
                 + Math.abs(parseFloat(fb.return_shipping_fee || fb.return_shipping_fee_amount || 0));

    const totalGross = parseFloat(order.total_amount || 0);
    const refundOrAdj = Math.abs(parseFloat(fb.total_adjustment_amount || fb.adjustment_amount || st0.adjustment_amount || 0));
    if (refundOrAdj > 0 && refundOrAdj < totalGross) {
      otherFee += refundOrAdj;
    }

    totalFee = platformFee + freeShipping + serviceFee + promoFee + otherFee;
    if (totalFee === 0 && st0.fee_amount) {
      totalFee = Math.abs(parseFloat(st0.fee_amount || 0));
    }

    // Proteksi escrow tidak melebihi (totalGross - escrowAmt)
    const escrowAmt = parseFloat(fb.escrow_amount || fb.settlement_amount || st0.settlement_amount || fb.seller_settlement_amount || 0);
    if (escrowAmt > 0 && totalGross > escrowAmt) {
      const actualMaxFee = totalGross - escrowAmt;
      if (totalFee > actualMaxFee) {
        totalFee = actualMaxFee;
      }
    }
  }

  if (totalFee > 0) return totalFee;

  // 3. Cek 5 kolom fee riil di database
  const colSum = Math.abs(parseFloat(order.fee_platform_amount || 0))
               + Math.abs(parseFloat(order.fee_free_shipping_amount || 0))
               + Math.abs(parseFloat(order.fee_service_amount || 0))
               + Math.abs(parseFloat(order.fee_promo_amount || 0))
               + Math.abs(parseFloat(order.fee_other_amount || 0));
  if (colSum > 0) return colSum;

  // 4. Cek nilai kolom marketplace_fee
  const directFee = Math.abs(parseFloat(order.marketplace_fee || 0));
  if (directFee > 0) return directFee;

  // 5. Fallback 5% sesuai formula Order::getMarketplaceFeeAttribute di ERP
  const gross = parseFloat(order.total_amount || 0);
  if (gross > 0) return Math.round(gross * 0.05);

  return 0;
}


/**
 * LAPORAN DETAIL TRANSAKSI PENJUALAN DILEPAS (100% Presisi Sama dengan ReportController::getSalesReportDetailData)
 */
async function getReleasedSalesDetailData(tenantId, dateFrom, dateTo, storeId = null) {
  const fromStr = dateFrom + ' 00:00:00';
  const toStr = dateTo + ' 23:59:59';
  const validStatuses = "('COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR')";

  let storeCondition = '';
  let params = [tenantId, fromStr, toStr];
  if (storeId) {
    storeCondition = 'AND o.store_id = ?';
    params.push(storeId);
  }

  // Query order yang persis sama dengan ReportController::getSalesReportDetailData
  const orders = await query(
    `SELECT o.*, s.store_name, c.name as channel_name, c.code as channel_code
     FROM orders o
     LEFT JOIN stores s ON s.id = o.store_id
     LEFT JOIN channels c ON c.id = s.channel_id
     WHERE o.tenant_id = ?
       AND UPPER(o.order_status) IN ${validStatuses}
       AND o.completed_at IS NOT NULL
       AND o.completed_at BETWEEN ? AND ?
       ${storeCondition}
     ORDER BY o.completed_at DESC, o.id DESC`,
    params
  );

  // Return orders map untuk refund persis Order::getRefundAmountAttribute
  let returnMap = {};
  try {
    const returnRows = await query('SELECT order_id, refund_amount FROM return_orders WHERE tenant_id = ?', [tenantId]);
    for (const rr of returnRows) {
      returnMap[rr.order_id] = parseFloat(rr.refund_amount || 0);
    }
  } catch (_) {}

  // Cache master_products tenant by ID dan SKU untuk fallback produk yang belum linked
  const mpById = new Map();
  const mpBySku = new Map();
  try {
    const mpRows = await query('SELECT id, sku, cost_price, name FROM master_products WHERE tenant_id = ?', [tenantId]);
    for (const m of mpRows) {
      if (m.id) mpById.set(m.id, m);
      if (m.sku) {
        const k = String(m.sku).trim().toLowerCase();
        const existing = mpBySku.get(k);
        if (!existing || (parseFloat(m.cost_price || 0) > 0 && parseFloat(existing.cost_price || 0) <= 0)) {
          mpBySku.set(k, m);
        }
      }
    }
  } catch (_) {}

  const orderIds = orders.map(o => o.id);
  let itemsByOrder = {};
  if (orderIds.length > 0) {
    for (let i = 0; i < orderIds.length; i += 500) {
      const chunk = orderIds.slice(i, i + 500);
      const items = await query(
        `SELECT oi.*, mp.sku as master_sku, mp.name as master_name, mp.cost_price as master_cost_price
         FROM order_items oi
         LEFT JOIN master_products mp ON mp.id = oi.master_product_id
         WHERE oi.order_id IN (?)`,
        [chunk]
      );
      for (const it of items) {
        if (!itemsByOrder[it.order_id]) itemsByOrder[it.order_id] = [];
        itemsByOrder[it.order_id].push(it);
      }
    }
  }

  let grandTotalOmset = 0;
  let grandTotalQty = 0;
  let grandTotalRefund = 0;
  let grandTotalTotalFee = 0;
  let grandTotalNetReleased = 0;
  let grandTotalHpp = 0;
  let grandTotalMarginRp = 0;

  const transactions = [];

  for (const o of orders) {
    const oItems = itemsByOrder[o.id] || [];
    let totalQty = 0;
    const itemSummary = [];
    for (const it of oItems) {
      const q = parseInt(it.quantity || 1, 10);
      totalQty += q;
      itemSummary.push((it.sku || it.master_sku || '-') + ' x' + q);
    }
    if (totalQty <= 0) totalQty = 1;

    // Hitung fee persis ReportController:
    let absFee = 0;
    if (o.recon_status === 'RECONCILED') {
      absFee = Math.abs(parseFloat(o.marketplace_fee || 0));
      if (absFee === 0) {
        absFee = Math.abs(parseFloat(o.fee_platform_amount || 0))
               + Math.abs(parseFloat(o.fee_free_shipping_amount || 0))
               + Math.abs(parseFloat(o.fee_service_amount || 0))
               + Math.abs(parseFloat(o.fee_promo_amount || 0))
               + Math.abs(parseFloat(o.fee_other_amount || 0));
      }
    } else {
      absFee = calculateOrderFee(o);
    }

    const omset = parseFloat(o.total_amount || 0);
    // Refund persis Order::getRefundAmountAttribute di ERP:
    const refund = calculateOrderRefund(o, returnMap);
    const isFullRefundOrReturn = ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'].includes(String(o.order_status || '').toUpperCase())
      || (omset > 0 && refund >= omset);

    // HPP persis Order::getHppTotalAttribute di ERP (Hanya pesanan full retur/batal yang HPP-nya 0):
    let hpp = 0;
    if (!isFullRefundOrReturn) {
      for (const it of oItems) {
        const sub = parseFloat(it.hpp_subtotal || 0);
        const q = parseInt(it.quantity || 1, 10);
        if (sub > 0) {
          hpp += sub;
        } else if (parseFloat(it.cost_price || 0) > 0) {
          hpp += (parseFloat(it.cost_price) * q);
        } else {
          let mProd = (it.master_product_id ? mpById.get(it.master_product_id) : null);
          if ((!mProd || parseFloat(mProd.cost_price || 0) <= 0) && it.sku) {
            const bySku = mpBySku.get(String(it.sku).trim().toLowerCase());
            if (bySku && parseFloat(bySku.cost_price || 0) > 0) mProd = bySku;
          }
          if ((!mProd || parseFloat(mProd.cost_price || 0) <= 0) && it.seller_sku) {
            const bySku = mpBySku.get(String(it.seller_sku).trim().toLowerCase());
            if (bySku && parseFloat(bySku.cost_price || 0) > 0) mProd = bySku;
          }
          const cost = parseFloat(mProd?.cost_price || it.master_cost_price || 0);
          hpp += (cost * q);
        }
      }
    }

    const netAmt = parseFloat(o.net_amount || 0);
    const marginRp = netAmt - hpp;
    const marginPct = netAmt > 0 ? Math.round((marginRp / netAmt) * 10000) / 100 : 0;

    grandTotalOmset += omset;
    grandTotalQty += totalQty;
    grandTotalRefund += refund;
    grandTotalTotalFee += absFee;
    grandTotalNetReleased += netAmt;
    grandTotalHpp += hpp;
    grandTotalMarginRp += marginRp;

    const refVal = o.order_marketplace_id || o.order_number || o.invoice_number || `ORD-${o.id}`;

    transactions.push({
      id: o.id,
      orderNumber: refVal,
      ref: refVal,
      invoiceNumber: o.invoice_number || refVal,
      storeName: o.store_name || 'Marketplace Store',
      channel: (o.store_name || '-') + ' (' + (o.channel_name || 'Marketplace') + ')',
      channelName: o.channel_name || 'Shopee',
      orderDate: o.order_date,
      completedAt: o.completed_at,
      releasedDate: o.completed_at,
      buyerName: o.buyer_name || 'Pelanggan MP',
      buyerPhone: o.buyer_phone || '',
      courier: o.shipping_provider || o.courier || 'Ekspedisi MP',
      trackingNumber: o.tracking_number || '',
      itemsSummary: itemSummary.join(', ') || '—',
      quantity: totalQty,
      totalQty,
      omset,
      totalAmount: omset,
      releasedValue: netAmt,
      refund,
      marketplaceFee: absFee,
      totalFee: absFee,
      netAmount: netAmt,
      netReleased: netAmt,
      hpp,
      hppModal: hpp,
      margin: marginRp,
      marginRp,
      marginPct,
      status: o.order_status,
      items: oItems.map(it => {
        let mProd = (it.master_product_id ? mpById.get(it.master_product_id) : null);
        if ((!mProd || parseFloat(mProd.cost_price || 0) <= 0) && it.sku) {
          const bySku = mpBySku.get(String(it.sku).trim().toLowerCase());
          if (bySku && parseFloat(bySku.cost_price || 0) > 0) mProd = bySku;
        }
        if ((!mProd || parseFloat(mProd.cost_price || 0) <= 0) && it.seller_sku) {
          const bySku = mpBySku.get(String(it.seller_sku).trim().toLowerCase());
          if (bySku && parseFloat(bySku.cost_price || 0) > 0) mProd = bySku;
        }
        return {
          id: it.id,
          name: it.product_name || mProd?.name || it.master_name,
          productName: it.product_name || mProd?.name || it.master_name,
          sku: it.master_sku || mProd?.sku || it.sku,
          size: it.sku_name || '',
          quantity: parseInt(it.quantity || 1, 10),
          qty: parseInt(it.quantity || 1, 10),
          price: parseFloat(it.price || it.unit_price || 0),
          totalPrice: parseFloat(it.total_price || 0),
          costPrice: parseFloat(it.cost_price || mProd?.cost_price || it.master_cost_price || 0)
        };
      })
    });
  }

  const grandOverallMarginPct = grandTotalNetReleased > 0
    ? Math.round((grandTotalMarginRp / grandTotalNetReleased) * 10000) / 100
    : 0;

  return {
    transactions,
    totalCount: transactions.length,
    grandTotalOmset,
    grandTotalQty,
    grandTotalRefund,
    grandTotalTotalFee,
    grandTotalNetReleased,
    grandTotalHpp,
    grandTotalMarginRp,
    grandOverallMarginPct
  };
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

    // 2. Laporan Penjualan Dilepas (Persis ReportController::getSalesReportDetailData)
    const dateFrom = req.query.date_from || (`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`);
    const dateTo = req.query.date_to || formatDate(now);
    const reportData = await getReleasedSalesDetailData(tenantId, dateFrom, dateTo, req.query.store_id);

    const monthOmset = reportData.grandTotalOmset;
    const monthRefund = reportData.grandTotalRefund;
    const monthFee = reportData.grandTotalTotalFee;
    const monthHpp = reportData.grandTotalHpp;
    const monthReleased = reportData.grandTotalNetReleased;
    const monthMargin = reportData.grandTotalMarginRp;
    const orderMap = { size: reportData.totalCount };

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
                  o.recon_status, o.financial_breakdown, o.fee_platform_amount,
                  o.fee_free_shipping_amount, o.fee_service_amount, o.fee_promo_amount, o.fee_other_amount,
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
            const rFund = calculateOrderRefund(tr);
            const fee = calculateOrderFee(tr);
            let rel = parseFloat(tr.net_amount || 0);
            if (rel <= 0) {
              rel = Math.max(0, parseFloat(tr.total_amount || 0) - rFund - fee);
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
    const statusTab = (req.query.status || 'all').toLowerCase();
    
    // Jika melihat pesanan selesai / dilepas (Modal finansial atau tab dilepas), ambil persis Laporan Penjualan Dilepas
    const isReleasedOnly = 
      ['completed', 'selesai', 'finished', 'released', 'dilepas'].includes(statusTab) || 
      req.query.todayOnly === 'true' || 
      req.query.today_only === '1' || 
      req.query.tab === 'dilepas' ||
      req.query.released === '1' ||
      req.query.released === 'true';

    if (isReleasedOnly) {
      const now = new Date();
      const dateFrom = req.query.date_from || (`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`);
      const dateTo = req.query.date_to || formatDate(now);
      const rep = await getReleasedSalesDetailData(tenantId, dateFrom, dateTo, req.query.store_id);

      let txList = rep.transactions;
      if (['returned', 'refund', 'retur'].includes(statusTab)) {
        txList = txList.filter(t => (t.refund || 0) > 0 || ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR'].includes(String(t.status || '').toUpperCase()));
      }

      return res.json({
        success: true,
        badges: {
          all: rep.totalCount,
          completed: rep.totalCount,
        },
        data: txList,
        summary: {
          totalCount: txList.length,
          grandTotalOmset: rep.grandTotalOmset,
          grandTotalRefund: rep.grandTotalRefund,
          grandTotalTotalFee: rep.grandTotalTotalFee,
          grandTotalNetReleased: rep.grandTotalNetReleased,
          grandTotalHpp: rep.grandTotalHpp,
          grandTotalMarginRp: rep.grandTotalMarginRp,
        }
      });
    }
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
      marketplaceFee: calculateOrderFee(ord),
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
        marketplaceFee: calculateOrderFee(ord),
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

// Helper tanggal format string YYYY-MM-DD aman timezone
function extractDateStr(val) {
  if (!val) return '';
  if (typeof val === 'string') return val.slice(0, 10);
  if (val instanceof Date) {
    const y = val.getFullYear();
    const m = String(val.getMonth() + 1).padStart(2, '0');
    const d = String(val.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
  return String(val).slice(0, 10);
}

const INDO_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
function formatIndoDate(dateVal) {
  const s = extractDateStr(dateVal);
  if (!s) return '';
  const parts = s.split('-');
  if (parts.length !== 3) return s;
  const day = parseInt(parts[2], 10);
  const monthIdx = parseInt(parts[1], 10) - 1;
  const year = parts[0];
  return `${day} ${INDO_MONTHS[monthIdx] || ''} ${year}`;
}

// ─── 6. TARGET KOMISI (SINKRON 100% DENGAN ERP V2) ──────────────────────────
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

    // Ambil daftar seluruh toko milik tenant untuk opsi form input/edit di mobile
    const availableStores = await query(
      `SELECT s.id, s.store_name as name, COALESCE(c.name, 'POS') as channel 
       FROM stores s 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE s.tenant_id = ? 
       ORDER BY s.store_name ASC`,
      [tenantId]
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

    const validStatuses = [
      'COMPLETED', 'RELEASED', 'COMPLETED_ESCROW', 'SELESAI', 'DELIVERED', 'FINISHED',
      'completed', 'released', 'selesai', 'delivered', 'finished'
    ];
    const invalidStatuses = [
      'CANCELLED', 'CANCELED', 'BATAL', 'RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'FAILED',
      'cancelled', 'canceled', 'batal', 'returned', 'refunded'
    ];

    for (const team of teams) {
      const tTarget = parseFloat(team.target_omset || 0);
      const tTargetQty = parseInt(team.target_qty || 0, 10);
      const isActive = Boolean(team.is_active);

      // Stores linked
      const storeRows = await query(
        `SELECT s.id, s.store_name, COALESCE(c.name, 'POS') as channel_name 
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
        let dateFromStr = null;
        let dateToStr = null;

        if (reqMonth && reqYear) {
          dateFromStr = `${reqYear}-${String(reqMonth).padStart(2, '0')}-01 00:00:00`;
          const lastD = new Date(reqYear, reqMonth, 0).getDate();
          dateToStr = `${reqYear}-${String(reqMonth).padStart(2, '0')}-${String(lastD).padStart(2, '0')} 23:59:59`;
        } else if (team.date_from && team.date_to) {
          dateFromStr = `${extractDateStr(team.date_from)} 00:00:00`;
          dateToStr = `${extractDateStr(team.date_to)} 23:59:59`;
        } else if (team.period_month && team.period_year) {
          dateFromStr = `${team.period_year}-${String(team.period_month).padStart(2, '0')}-01 00:00:00`;
          const lastD = new Date(team.period_year, team.period_month, 0).getDate();
          dateToStr = `${team.period_year}-${String(team.period_month).padStart(2, '0')}-${String(lastD).padStart(2, '0')} 23:59:59`;
        }

        let ordersQuery = `
          SELECT id, store_id, total_amount, net_amount, marketplace_fee, order_status, financial_breakdown,
                 COALESCE(completed_at, updated_at, order_date) as ref_date
          FROM orders 
          WHERE store_id IN (?)
            AND order_status IN (?)
            AND order_status NOT IN (?)
        `;
        const qParams = [storeIds, validStatuses, invalidStatuses];

        if (dateFromStr && dateToStr) {
          ordersQuery += ` AND COALESCE(completed_at, updated_at, order_date) BETWEEN ? AND ?`;
          qParams.push(dateFromStr, dateToStr);
        }

        const orders = await query(ordersQuery, qParams);

        if (orders.length > 0) {
          const orderIds = orders.map(o => o.id);

          // Return items lookup & return orders refund lookup
          const returnMap = new Map();
          const returnRefundMap = {};

          try {
            const returnOrderRows = await query(
              `SELECT order_id, refund_amount FROM return_orders WHERE order_id IN (?)`,
              [orderIds]
            );
            for (const rr of returnOrderRows) {
              returnRefundMap[rr.order_id] = parseFloat(rr.refund_amount || 0);
            }
          } catch (_) {}

          try {
            const returnRows = await query(
              `SELECT roi.order_item_id, SUM(roi.quantity) as ret_qty
               FROM return_orders ro
               JOIN return_order_items roi ON roi.return_order_id = ro.id
               WHERE ro.order_id IN (?)
               GROUP BY roi.order_item_id`,
              [orderIds]
            );
            for (const r of returnRows) {
              returnMap.set(r.order_item_id, parseInt(r.ret_qty || 0, 10));
            }
          } catch (_) {}

          // Query detail item dengan HPP bertingkat persis seperti MarketingTeam ERP
          const items = await query(
            `SELECT oi.id, oi.order_id, oi.quantity, oi.cost_price, oi.hpp_subtotal,
                    mp1.cost_price as master_cost_price,
                    mp2.cost_price as mkt_master_cost_price
             FROM order_items oi
             LEFT JOIN master_products mp1 ON mp1.id = oi.master_product_id
             LEFT JOIN marketplace_products mkp ON mkp.id = oi.marketplace_product_id
             LEFT JOIN master_products mp2 ON mp2.id = mkp.master_product_id
             WHERE oi.order_id IN (?)`,
            [orderIds]
          );

          const itemsByOrder = new Map();
          for (const it of items) {
            if (!itemsByOrder.has(it.order_id)) {
              itemsByOrder.set(it.order_id, []);
            }
            itemsByOrder.get(it.order_id).push(it);
          }

          for (const ord of orders) {
            const totAmt = parseFloat(ord.total_amount || 0);
            const refAmt = calculateOrderRefund(ord, returnRefundMap);
            const mktFee = parseFloat(ord.marketplace_fee || 0);
            let netAmt = parseFloat(ord.net_amount || 0);

            // Hindari pesanan yang full refund persis ERP
            if (refAmt > 0 && totAmt > 0 && refAmt >= totAmt) {
              continue;
            }

            const effectiveOmset = Math.max(0, totAmt - refAmt);
            actOmset += effectiveOmset;

            // Hitung nilai dasar pesanan yang dilepas (released) persis ERP
            let orderReleased = netAmt;
            if (orderReleased <= 0) {
              orderReleased = Math.max(0, totAmt - refAmt - mktFee);
              if (orderReleased <= 0) {
                orderReleased = Math.max(0, totAmt - refAmt);
              }
            }

            const ordItems = itemsByOrder.get(ord.id) || [];
            let eligibleQty = 0;
            let orderEligibleHpp = 0;

            for (const it of ordItems) {
              const qty = parseInt(it.quantity || 0, 10);
              let retQty = returnMap.get(it.id) || 0;

              if (retQty === 0 && refAmt > 0 && totAmt > 0) {
                if (refAmt >= totAmt) {
                  retQty = qty;
                } else {
                  const ratio = refAmt / totAmt;
                  retQty = Math.min(qty, Math.round(qty * ratio));
                }
              }

              const itemNetQty = Math.max(0, qty - retQty);
              eligibleQty += itemNetQty;

              // Unit HPP bertingkat persis MarketingTeam ERP
              let unitHpp = 0;
              const hppSub = parseFloat(it.hpp_subtotal || 0);
              if (hppSub > 0 && qty > 0) {
                unitHpp = hppSub / qty;
              } else if (parseFloat(it.cost_price || 0) > 0) {
                unitHpp = parseFloat(it.cost_price);
              } else if (parseFloat(it.master_cost_price || 0) > 0) {
                unitHpp = parseFloat(it.master_cost_price);
              } else if (parseFloat(it.mkt_master_cost_price || 0) > 0) {
                unitHpp = parseFloat(it.mkt_master_cost_price);
              }

              orderEligibleHpp += (unitHpp * itemNetQty);
            }

            orderReleased = Math.max(0, orderReleased);
            const orderMargin = orderReleased - orderEligibleHpp;

            actQty += eligibleQty;
            actHpp += orderEligibleHpp;
            actMargin += orderMargin;
          }
        }
      }

      // Pembulatan metrik
      actMargin = Math.round(actMargin * 100) / 100;
      actOmset = Math.round(actOmset * 100) / 100;
      actHpp = Math.round(actHpp * 100) / 100;

      // Calculate reward persis MarketingTeam::getTotalRewardAttribute()
      const cType = team.commission_type || 'percentage';
      let reward = 0;
      if (cType === 'percentage') {
        const basisMargin = Math.max(0, actMargin);
        reward = basisMargin * (parseFloat(team.commission_rate || 0) / 100);
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
      reward = Math.round(reward * 100) / 100;

      const progressPct = tTarget > 0 ? Math.min(100, Math.round((actMargin / tTarget) * 1000) / 10) : 0;

      if (isActive) {
        totalTargetMargin += tTarget;
        totalTargetQty += tTargetQty;
        totalActualMargin += actMargin;
        totalActualQty += actQty;
        totalReward += reward;
      }

      // Period label persis ERP (Contoh: "11 Okt 2026 – 10 Nov 2026")
      let pLabel = 'Semua Periode';
      if (team.date_from && team.date_to) {
        pLabel = `${formatIndoDate(team.date_from)} – ${formatIndoDate(team.date_to)}`;
      } else if (team.period_month && team.period_year) {
        pLabel = `${INDO_MONTHS[(team.period_month || 1) - 1]} ${team.period_year}`;
      }

      formattedTeams.push({
        id: team.id,
        name: team.name,
        code: team.code || '',
        description: team.description || '',
        periodLabel: pLabel,
        periodMonth: team.period_month,
        periodYear: team.period_year,
        dateFrom: extractDateStr(team.date_from),
        dateTo: extractDateStr(team.date_to),
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
      availableStores: availableStores || [],
    });
  } catch (err) {
    console.error('[Node-API] Target komisi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// POST /api/v2/owner/target-komisi (Tambah Tim Marketing baru & Target)
app.post('/api/v2/owner/target-komisi', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const {
      name,
      code,
      description,
      target_omset,
      commission_type,
      commission_rate,
      reward_fixed_nominal,
      target_qty,
      reward_per_qty,
      period_month,
      period_year,
      date_from,
      date_to,
      store_ids,
      is_active
    } = req.body;

    if (!name || name.trim() === '') {
      return res.status(422).json({ success: false, message: 'Nama Tim wajib diisi.' });
    }

    const pMonth = parseInt(period_month, 10) || (new Date().getMonth() + 1);
    const pYear = parseInt(period_year, 10) || (new Date().getFullYear());
    let dFrom = date_from;
    let dTo = date_to;
    if (!dFrom || !dTo) {
      dFrom = `${pYear}-${String(pMonth).padStart(2, '0')}-01`;
      const lastDay = new Date(pYear, pMonth, 0).getDate();
      dTo = `${pYear}-${String(pMonth).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    }

    const insertResult = await query(
      `INSERT INTO marketing_teams 
       (tenant_id, name, code, description, target_omset, commission_type, commission_rate, reward_fixed_nominal, target_qty, reward_per_qty, period_month, period_year, date_from, date_to, is_active, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [
        tenantId,
        name.trim(),
        code || null,
        description || null,
        parseFloat(target_omset || 0),
        commission_type || 'percentage',
        parseFloat(commission_rate || 0),
        parseFloat(reward_fixed_nominal || 0),
        parseInt(target_qty || 0, 10),
        parseFloat(reward_per_qty || 0),
        pMonth,
        pYear,
        dFrom,
        dTo,
        is_active !== undefined ? (is_active ? 1 : 0) : 1
      ]
    );

    const newTeamId = insertResult.insertId;

    if (Array.isArray(store_ids) && store_ids.length > 0) {
      for (const sId of store_ids) {
        await query(
          `INSERT INTO marketing_team_stores (marketing_team_id, store_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())`,
          [newTeamId, sId]
        );
      }
    }

    return res.json({ success: true, message: `Tim '${name}' berhasil ditambahkan.` });
  } catch (err) {
    console.error('[Node-API] Create target komisi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// PUT /api/v2/owner/target-komisi/:id (Update Tim & Target)
app.put('/api/v2/owner/target-komisi/:id', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const teamId = parseInt(req.params.id, 10);
    const {
      name,
      code,
      description,
      target_omset,
      commission_type,
      commission_rate,
      reward_fixed_nominal,
      target_qty,
      reward_per_qty,
      period_month,
      period_year,
      date_from,
      date_to,
      store_ids,
      is_active
    } = req.body;

    const [existing] = await query('SELECT id FROM marketing_teams WHERE id = ? AND tenant_id = ?', [teamId, tenantId]);
    if (!existing) {
      return res.status(404).json({ success: false, message: 'Tim tidak ditemukan.' });
    }

    const pMonth = parseInt(period_month, 10) || (new Date().getMonth() + 1);
    const pYear = parseInt(period_year, 10) || (new Date().getFullYear());
    let dFrom = date_from;
    let dTo = date_to;
    if (!dFrom || !dTo) {
      dFrom = `${pYear}-${String(pMonth).padStart(2, '0')}-01`;
      const lastDay = new Date(pYear, pMonth, 0).getDate();
      dTo = `${pYear}-${String(pMonth).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    }

    await query(
      `UPDATE marketing_teams SET
         name = ?, code = ?, description = ?, target_omset = ?, commission_type = ?,
         commission_rate = ?, reward_fixed_nominal = ?, target_qty = ?, reward_per_qty = ?,
         period_month = ?, period_year = ?, date_from = ?, date_to = ?, is_active = ?,
         updated_at = NOW()
       WHERE id = ? AND tenant_id = ?`,
      [
        name ? name.trim() : 'Tim Marketing',
        code || null,
        description || null,
        parseFloat(target_omset || 0),
        commission_type || 'percentage',
        parseFloat(commission_rate || 0),
        parseFloat(reward_fixed_nominal || 0),
        parseInt(target_qty || 0, 10),
        parseFloat(reward_per_qty || 0),
        pMonth,
        pYear,
        dFrom,
        dTo,
        is_active !== undefined ? (is_active ? 1 : 0) : 1,
        teamId,
        tenantId
      ]
    );

    if (Array.isArray(store_ids)) {
      await query('DELETE FROM marketing_team_stores WHERE marketing_team_id = ?', [teamId]);
      for (const sId of store_ids) {
        await query(
          `INSERT INTO marketing_team_stores (marketing_team_id, store_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())`,
          [teamId, sId]
        );
      }
    }

    return res.json({ success: true, message: `Data tim berhasil diperbarui.` });
  } catch (err) {
    console.error('[Node-API] Update target komisi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// DELETE /api/v2/owner/target-komisi/:id (Hapus Tim)
app.delete('/api/v2/owner/target-komisi/:id', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const teamId = parseInt(req.params.id, 10);
    await query('DELETE FROM marketing_team_stores WHERE marketing_team_id = ?', [teamId]);
    await query('DELETE FROM marketing_teams WHERE id = ? AND tenant_id = ?', [teamId, tenantId]);
    return res.json({ success: true, message: 'Tim berhasil dihapus.' });
  } catch (err) {
    console.error('[Node-API] Delete target komisi error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// POST /api/v2/owner/target-komisi/:id/toggle (Toggle Aktif/Nonaktif)
app.post('/api/v2/owner/target-komisi/:id/toggle', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const teamId = parseInt(req.params.id, 10);
    await query(
      'UPDATE marketing_teams SET is_active = NOT is_active, updated_at = NOW() WHERE id = ? AND tenant_id = ?',
      [teamId, tenantId]
    );
    return res.json({ success: true, message: 'Status tim berhasil diubah.' });
  } catch (err) {
    console.error('[Node-API] Toggle target komisi error:', err);
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

    // 1. Online Revenue & HPP (Diselaraskan persis Laporan Penjualan Dilepas)
    const repOnline = await getReleasedSalesDetailData(tenantId, formatDate(dateFrom), formatDate(dateTo));
    const onlineGross = repOnline.grandTotalOmset;
    const onlineNet = repOnline.grandTotalNetReleased;
    const onlineFee = repOnline.grandTotalTotalFee;
    const onlineHpp = repOnline.grandTotalHpp;
    const onlineRevenue = onlineNet > 0 ? onlineNet : onlineGross;

    // 2. Offline / POS Sales (sold_at or created_at)
    let offlineRevenue = 0;
    let offlineHpp = 0;
    let offlineOrdersCount = 0;
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
      offlineOrdersCount = seenOffline.size;
    } catch (_) {}

    // 1b. Store Breakdown dari repOnline.transactions
    const storeMap = new Map();
    for (const tr of repOnline?.transactions || []) {
      const sKey = tr.storeName || 'Toko Marketplace';
      if (!storeMap.has(sKey)) {
        storeMap.set(sKey, {
          storeName: sKey,
          channelName: tr.channelName || 'Marketplace',
          ordersCount: 0,
          omset: 0,
          fee: 0,
          refund: 0,
          net: 0,
          hpp: 0,
        });
      }
      const st = storeMap.get(sKey);
      st.ordersCount += 1;
      st.omset += parseFloat(tr.omset || 0);
      st.fee += parseFloat(tr.totalFee || 0);
      st.refund += parseFloat(tr.refund || 0);
      st.net += parseFloat(tr.netReleased || 0);
      st.hpp += parseFloat(tr.hpp || 0);
    }
    const storeBreakdown = Array.from(storeMap.values()).map(st => ({
      ...st,
      omset: Math.round(st.omset),
      fee: Math.round(st.fee),
      refund: Math.round(st.refund),
      net: Math.round(st.net),
      hpp: Math.round(st.hpp),
    })).sort((a, b) => b.net - a.net);

    const totalOmset = onlineRevenue + offlineRevenue;
    const totalHpp = onlineHpp + offlineHpp;
    const labaKotor = totalOmset - totalHpp;
    const grossMarginPct = totalOmset > 0 ? Math.round((labaKotor / totalOmset) * 1000) / 10 : 0;

    // 3. Operating Expenses with Individual Transactions Drilldown
    let totalOperasional = 0;
    const expenseCategories = [];
    try {
      const fromDateOnly = formatDate(dateFrom);
      const toDateOnly = formatDate(dateTo);
      const expenses = await query(
        `SELECT id, category, title, description, expense_date, amount, payment_source 
         FROM expenses 
         WHERE tenant_id = ? AND expense_date BETWEEN ? AND ?
           AND LOWER(category) NOT IN ('pembelian_supplier', 'bahan_baku', 'biaya_produksi', 'supplier_payable')
         ORDER BY expense_date DESC, id DESC`,
        [tenantId, fromDateOnly, toDateOnly]
      );

      const labelMap = {
        salary: 'Gaji Karyawan',
        rent: 'Sewa Tempat',
        utilities: 'Utilitas & Operasional',
        other: 'Lain-lain',
      };

      const expGrouped = new Map();
      for (const exp of expenses) {
        const catKey = (exp.category || 'other').toLowerCase();
        if (!expGrouped.has(catKey)) {
          expGrouped.set(catKey, {
            code: catKey,
            name: labelMap[catKey] || (exp.category ? exp.category.replace(/_/g, ' ') : 'Lain-lain'),
            amount: 0,
            items: [],
          });
        }
        const g = expGrouped.get(catKey);
        const amt = parseFloat(exp.amount || 0);
        g.amount += amt;
        totalOperasional += amt;
        g.items.push({
          id: exp.id,
          date: exp.expense_date ? formatDate(new Date(exp.expense_date)) : '',
          title: exp.title || exp.description || 'Pengeluaran',
          amount: Math.round(amt),
          paymentSource: exp.payment_source || 'kas',
          notes: exp.description || '',
        });
      }

      for (const [_, val] of expGrouped) {
        val.amount = Math.round(val.amount);
        expenseCategories.push(val);
      }
    } catch (expErr) {
      console.error('[Node-API] Error fetching expenses for laba-rugi:', expErr);
    }

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
        storeBreakdown,
        labaBersih: Math.round(labaBersih),
        netMarginPct,
        ordersCount: (repOnline?.totalCount || 0) + offlineOrdersCount,
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
