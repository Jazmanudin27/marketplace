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
  if (header) {
    const val = parseInt(header, 10);
    if (!isNaN(val) && val > 0) return val;
  }
  if (req.headers.authorization && req.headers.authorization.startsWith('Bearer ')) {
    try {
      const token = req.headers.authorization.split(' ')[1];
      const decoded = jwt.decode(token);
      if (decoded && decoded.tenant_id) {
        const val = parseInt(decoded.tenant_id, 10);
        if (!isNaN(val) && val > 0) return val;
      }
    } catch (_) {}
  }
  return 1;
}

// Helper Auth User resolver from JWT Bearer token
function getAuthUser(req) {
  if (req.headers.authorization && req.headers.authorization.startsWith('Bearer ')) {
    try {
      const token = req.headers.authorization.split(' ')[1];
      const decoded = jwt.decode(token);
      if (decoded && decoded.id) {
        return decoded;
      }
    } catch (_) {}
  }
  return null;
}

function formatDate(d) {
  if (!d) return '';
  const dateObj = (d instanceof Date) ? d : new Date(d);
  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function extractDateStr(val) {
  if (!val) return '';
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
const formatDateIndo = formatIndoDate;

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

      // Partial refund: hitung HPP proporsional hanya untuk barang yang tidak direfund
      if (refund > 0 && omset > 0 && refund < omset) {
        const remainingRatio = Math.max(0, (omset - refund) / omset);
        hpp = Math.round(hpp * remainingRatio);
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
app.post(['/api/v2/owner/login', '/api/owner/login'], async (req, res) => {
  try {
    const { login, password } = req.body;
    if (!login || !password) {
      return res.status(400).json({ success: false, message: 'Email/username dan kata sandi wajib diisi.' });
    }

    const users = await query(
      'SELECT * FROM users WHERE email = ? OR name = ? LIMIT 1',
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

    let tenantName = 'Ruang Market Pro';
    const tenants = await query('SELECT name FROM tenants WHERE id = ? LIMIT 1', [tenantId]);
    if (tenants.length > 0 && tenants[0].name && !tenants[0].name.includes('Ruang Seragam')) tenantName = tenants[0].name;

    return res.json({
      success: true,
      message: 'Login berhasil.',
      data: {
        token,
        user: {
          id: user.id,
          name: user.name,
          email: user.email,
          username: user.name || (user.email ? user.email.split('@')[0] : ''),
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

// ─── 1B. AUTH: UPDATE CREDENTIALS (USERNAME & PASSWORD) ─────────────────────
app.post('/api/v2/owner/profile/update-credentials', async (req, res) => {
  try {
    const authUser = getAuthUser(req);
    const tenantId = getTenantId(req);
    const { username, current_password, new_password, email } = req.body;

    let targetUserId = authUser?.id;
    if (!targetUserId) {
      const headerUserStr = req.headers['x-user-id'];
      if (headerUserStr) targetUserId = parseInt(headerUserStr, 10);
    }

    let users;
    if (targetUserId) {
      users = await query('SELECT * FROM users WHERE id = ? LIMIT 1', [targetUserId]);
    } else {
      users = await query('SELECT * FROM users WHERE tenant_id = ? LIMIT 1', [tenantId]);
    }

    if (!users || users.length === 0) {
      return res.status(404).json({ success: false, message: 'User tidak ditemukan.' });
    }

    const user = users[0];

    // Jika ingin ganti password, wajib periksa current_password
    if (new_password) {
      if (!current_password) {
        return res.status(400).json({ success: false, message: 'Kata sandi saat ini wajib diisi untuk mengubah kata sandi baru.' });
      }
      const hash = (user.password || '').replace(/^\$2y\$/, '$2a$');
      const isMatch = bcrypt.compareSync(current_password, hash);
      if (!isMatch) {
        return res.status(400).json({ success: false, message: 'Kata sandi saat ini salah.' });
      }
      if (new_password.length < 6) {
        return res.status(400).json({ success: false, message: 'Kata sandi baru minimal 6 karakter.' });
      }
      const newHash = bcrypt.hashSync(new_password, 10).replace(/^\$2a\$/, '$2y$');
      await query('UPDATE users SET password = ? WHERE id = ?', [newHash, user.id]);
    }

    // Jika ganti username / nama
    const newName = (username || '').trim();
    if (newName && newName !== user.name) {
      await query('UPDATE users SET name = ? WHERE id = ?', [newName, user.id]);
      user.name = newName;
    }

    // Jika ganti email
    const newEmail = (email || '').trim();
    if (newEmail && newEmail !== user.email) {
      const existing = await query('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1', [newEmail, user.id]);
      if (existing.length > 0) {
        return res.status(400).json({ success: false, message: 'Email sudah digunakan oleh akun lain.' });
      }
      await query('UPDATE users SET email = ? WHERE id = ?', [newEmail, user.id]);
      user.email = newEmail;
    }

    const updatedUser = {
      id: user.id,
      name: user.name,
      email: user.email,
      username: user.name,
      role: user.role || 'Business Owner',
      tenant_id: user.tenant_id,
    };

    return res.json({
      success: true,
      message: 'Profil & kredensial berhasil diperbarui.',
      data: { user: updatedUser }
    });
  } catch (err) {
    console.error('[Node-API] Update credentials error:', err);
    return res.status(500).json({ success: false, message: 'Terjadi kesalahan server: ' + err.message });
  }
});

// ─── 2. METRICS OVERVIEW ────────────────────────────────────────────────────
app.get(['/api/v2/owner/metrics/overview', '/api/owner/metrics/overview'], async (req, res) => {
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
        const params = [tenantId, storeIds];

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
           WHERE o.tenant_id = ? AND o.store_id IN (?)
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

    // 6. Owner profile (Resolve to logged-in user from JWT token)
    const authUser = getAuthUser(req);
    let ownerUser = null;
    if (authUser && authUser.id) {
      const usersById = await query('SELECT * FROM users WHERE id = ? LIMIT 1', [authUser.id]);
      if (usersById && usersById.length > 0) {
        ownerUser = usersById[0];
      }
    }
    if (!ownerUser) {
      const users = await query('SELECT * FROM users WHERE tenant_id = ? LIMIT 1', [tenantId]);
      ownerUser = users[0] || {};
    }
    const tenants = await query('SELECT name FROM tenants WHERE id = ? LIMIT 1', [tenantId]);
    let tenantName = tenants[0]?.name || 'Ruang Market Pro';
    if (tenantName.includes('Ruang Seragam')) tenantName = 'Ruang Market Pro';

    const resolvedName = ownerUser.name || 'Owner';
    const nameParts = resolvedName.trim().split(' ').filter(Boolean);
    const initials = nameParts.length >= 2
      ? (nameParts[0][0] + nameParts[1][0]).toUpperCase()
      : resolvedName.substring(0, 2).toUpperCase();

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
        name: resolvedName,
        role: ownerUser.role || 'Business Owner',
        tenantName,
        avatarInitials: initials,
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
app.get(['/api/v2/owner/orders', '/api/owner/orders'], async (req, res) => {
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
    const perluDikirimStatusesList = "'READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'";
    const shippedStatusesList = "'SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'DELIVERED'";
    const completedStatusesList = "'COMPLETED', 'FINISHED', 'SELESAI'";
    const returnStatusesList = "'TO_RETURN', 'RETURN', 'RETURNED', 'REFUNDED', 'RETUR', 'RETURNING'";
    const cancelledStatusesList = "'CANCELLED', 'CANCELED', 'BATAL', 'IN_CANCEL'";
    const unpaidStatusesList = "'UNPAID', 'PENDING'";

    const unprocessedStatusesList = "'UNPAID', 'PENDING', 'READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'";
    const processedStatusesList = "'SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'COMPLETED', 'FINISHED', 'SELESAI', 'DELIVERED'";

    // ── Tab counts & Process counts persis query PesananController ERP ──
    const [cAllActive] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND ${notCancelled}`, [tenantId]);
    const [cTotalAll] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ?`, [tenantId]);
    const [cPerlu] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${perluDikirimStatusesList})`, [tenantId]);
    const [cShipped] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${shippedStatusesList})`, [tenantId]);
    const [cCompleted] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${completedStatusesList})`, [tenantId]);
    const [cReturn] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${returnStatusesList})`, [tenantId]);
    const [cCancelled] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${cancelledStatusesList})`, [tenantId]);
    const [cUnpaid] = await query(`SELECT COUNT(*) as cnt FROM orders o WHERE o.tenant_id = ? AND UPPER(o.order_status) IN (${unpaidStatusesList})`, [tenantId]);

    // Sub-tab Belum diproses & Telah diproses (khusus pesanan Perlu Dikirim)
    const [cToProcess] = await query(
      `SELECT COUNT(*) as cnt FROM orders o 
       WHERE o.tenant_id = ? 
         AND UPPER(o.order_status) IN (${perluDikirimStatusesList})
         AND (o.is_printed = 0 OR o.is_printed IS NULL) 
         AND (o.tracking_number IS NULL OR o.tracking_number = '')`,
      [tenantId]
    );

    const [cProcessed] = await query(
      `SELECT COUNT(*) as cnt FROM orders o 
       WHERE o.tenant_id = ? 
         AND UPPER(o.order_status) IN (${perluDikirimStatusesList})
         AND (
           o.is_printed = 1 
           OR (o.tracking_number IS NOT NULL AND o.tracking_number != '')
         )`,
      [tenantId]
    );

    const toProcessVal = cToProcess?.cnt || 0;
    const processedVal = cProcessed?.cnt ?? Math.max(0, (cPerlu?.cnt || 0) - toProcessVal);

    const tabCounts = {
      ALL: cAllActive?.cnt || 0,
      __all__: cTotalAll?.cnt || 0,
      READY_TO_SHIP: cPerlu?.cnt || 0,
      SHIPPED: cShipped?.cnt || 0,
      COMPLETED: cCompleted?.cnt || 0,
      TO_RETURN: cReturn?.cnt || 0,
      CANCELLED: cCancelled?.cnt || 0,
      UNPAID: cUnpaid?.cnt || 0,
    };

    const processCounts = {
      to_process: toProcessVal,
      processed: processedVal,
    };

    // Build filter query
    let whereClause = `o.tenant_id = ?`;
    const params = [tenantId];

    const normalizedStatus = (statusTab || '').toUpperCase();
    if (normalizedStatus === 'READY_TO_SHIP' || normalizedStatus === 'PERLU_DIKIRIM') {
      whereClause += ` AND UPPER(o.order_status) IN (${perluDikirimStatusesList})`;
    } else if (normalizedStatus === 'SHIPPED' || normalizedStatus === 'DIKIRIM') {
      whereClause += ` AND UPPER(o.order_status) IN (${shippedStatusesList})`;
    } else if (normalizedStatus === 'COMPLETED' || normalizedStatus === 'SELESAI') {
      whereClause += ` AND UPPER(o.order_status) IN (${completedStatusesList})`;
    } else if (normalizedStatus === 'TO_RETURN' || normalizedStatus === 'RETUR') {
      whereClause += ` AND UPPER(o.order_status) IN (${returnStatusesList})`;
    } else if (normalizedStatus === 'CANCELLED' || normalizedStatus === 'BATAL') {
      whereClause += ` AND UPPER(o.order_status) IN (${cancelledStatusesList})`;
    } else {
      // Default: ALL (Semua pesanan aktif, persis seperti halaman Pesanan Masuk ERP)
      whereClause += ` AND ${notCancelled}`;
    }

    if (subStatus === 'to_process' || subStatus === 'unprocessed') {
      whereClause += ` AND (o.is_printed = 0 OR o.is_printed IS NULL) AND (o.tracking_number IS NULL OR o.tracking_number = '') AND UPPER(o.order_status) IN (${unprocessedStatusesList})`;
    } else if (subStatus === 'processed') {
      whereClause += ` AND (o.is_printed = 1 OR UPPER(o.order_status) IN (${processedStatusesList}) OR (o.tracking_number IS NOT NULL AND o.tracking_number != ''))`;
    }

    if (search) {
      whereClause += ` AND (o.order_marketplace_id LIKE ? OR o.buyer_name LIKE ? OR o.tracking_number LIKE ? OR o.invoice_number LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s, s);
    }

    if (startDate) {
      whereClause += ` AND o.order_date >= ?`;
      params.push(startDate + ' 00:00:00');
    }
    if (endDate) {
      whereClause += ` AND o.order_date <= ?`;
      params.push(endDate + ' 23:59:59');
    }

    const orderLimit = Math.min(parseInt(req.query.limit, 10) || 100, 300);
    const orders = await query(
      `SELECT o.*, s.store_name, c.name as channel_name 
       FROM orders o 
       LEFT JOIN stores s ON s.id = o.store_id 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE ${whereClause} 
       ORDER BY o.order_date DESC, o.id DESC 
       LIMIT ${orderLimit}`,
      params
    );

    // Cache master_products & return_orders & marketing_teams for financial calculation
    let mpById = new Map();
    let mpBySku = new Map();
    try {
      const mpRows = await query('SELECT id, sku, cost_price, name FROM master_products WHERE tenant_id = ?', [tenantId]);
      for (const p of mpRows) {
        mpById.set(p.id, p);
        if (p.sku) mpBySku.set(String(p.sku).trim().toLowerCase(), p);
      }
    } catch (_) {}

    let returnMap = {};
    try {
      const returnRows = await query('SELECT order_id, refund_amount FROM return_orders WHERE tenant_id = ?', [tenantId]);
      for (const rr of returnRows) {
        returnMap[rr.order_id] = parseFloat(rr.refund_amount || 0);
      }
    } catch (_) {}

    let commissionByStore = new Map();
    try {
      const teamStoreRows = await query(`
        SELECT mts.store_id, mt.commission_type, mt.commission_rate, mt.reward_per_qty
        FROM marketing_team_stores mts
        JOIN marketing_teams mt ON mt.id = mts.marketing_team_id
        WHERE mt.tenant_id = ? AND mt.is_active = 1
      `, [tenantId]);
      for (const ts of teamStoreRows) {
        commissionByStore.set(ts.store_id, ts);
      }
    } catch (_) {}

    // Attach items for each order
    const orderIds = orders.map(o => o.id);
    let itemsByOrderId = {};
    if (orderIds.length > 0) {
      const items = await query(
        `SELECT oi.*, mp.sku as master_sku, mp.cost_price as master_cost_price, mp.name as master_name 
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
          name: item.product_name || item.master_name || 'Produk',
          sku: item.master_sku || item.sku || '-',
          qty,
          price,
          totalPrice: totP > 0 ? totP : (price * qty),
          costPrice: parseFloat(item.cost_price || item.master_cost_price || 0),
          hppSubtotal: parseFloat(item.hpp_subtotal || 0),
          masterProductId: item.master_product_id,
        });
      }
    }

    const formattedOrders = orders.map(ord => {
      const oItems = itemsByOrderId[ord.id] || [];
      let totalQty = 0;
      for (const it of oItems) {
        totalQty += (it.qty || 1);
      }
      if (totalQty <= 0) totalQty = 1;

      const omset = parseFloat(ord.total_amount || 0);
      const absFee = calculateOrderFee(ord);
      const refund = calculateOrderRefund(ord, returnMap);
      const isFullRefundOrReturn = ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'].includes(String(ord.order_status || '').toUpperCase())
        || (omset > 0 && refund >= omset);

      let hpp = 0;
      if (!isFullRefundOrReturn) {
        for (const it of oItems) {
          const sub = it.hppSubtotal;
          const q = it.qty || 1;
          if (sub > 0) {
            hpp += sub;
          } else if (it.costPrice > 0) {
            hpp += (it.costPrice * q);
          } else {
            let mProd = (it.masterProductId ? mpById.get(it.masterProductId) : null);
            if ((!mProd || parseFloat(mProd.cost_price || 0) <= 0) && it.sku) {
              const bySku = mpBySku.get(String(it.sku).trim().toLowerCase());
              if (bySku && parseFloat(bySku.cost_price || 0) > 0) mProd = bySku;
            }
            const cost = parseFloat(mProd?.cost_price || 0);
            hpp += (cost * q);
          }
        }

        // Partial refund: hitung HPP proporsional hanya untuk barang yang tidak direfund
        if (refund > 0 && omset > 0 && refund < omset) {
          const remainingRatio = Math.max(0, (omset - refund) / omset);
          hpp = Math.round(hpp * remainingRatio);
        }
      }

      const netAmount = parseFloat(ord.net_amount || 0);
      const releasedVal = netAmount > 0 ? netAmount : Math.max(0, omset - absFee);
      const margin = releasedVal - hpp;
      const marginPct = releasedVal > 0 ? Math.round((margin / releasedVal) * 10000) / 100 : 0;

      let commission = 0;
      const team = commissionByStore.get(ord.store_id);
      if (team) {
        if (team.commission_type === 'fixed_per_qty') {
          commission = totalQty * parseFloat(team.reward_per_qty || 0);
        } else {
          commission = Math.max(0, margin * (parseFloat(team.commission_rate || 0) / 100));
        }
      }

      const inv = ord.invoice_number || ord.order_marketplace_id || `ORD-${ord.id}`;
      const cDate = ord.completed_at ? formatDateIndo(ord.completed_at) : (ord.order_date ? formatDateIndo(ord.order_date) : '');

      return {
        id: ord.id,
        orderNumber: ord.order_marketplace_id || ord.order_number || inv,
        invoiceNumber: inv,
        storeName: ord.store_name || 'Marketplace Store',
        channel: ord.channel_name || 'Shopee',
        orderDate: ord.order_date,
        completedAt: cDate,
        status: ord.order_status,
        isProcessed: ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'].includes((ord.order_status || '').toUpperCase()),
        totalAmount: omset,
        omset,
        netAmount: releasedVal,
        netReleased: releasedVal,
        releasedValue: releasedVal,
        marketplaceFee: absFee,
        totalFee: absFee,
        refund,
        hpp,
        hppModal: hpp,
        margin,
        marginRp: margin,
        marginPct,
        commission: Math.round(commission),
        buyerName: ord.buyer_name || 'Pembeli',
        resi: ord.tracking_number || '-',
        trackingNumber: ord.tracking_number || '',
        courier: ord.courier || ord.shipping_provider || 'Reguler',
        quantity: totalQty,
        itemsCount: totalQty,
        items: oItems,
      };
    });

    return res.json({
      success: true,
      tabCounts,
      processCounts,
      badges: {
        all: tabCounts.ALL,
        perlu_dikirim: tabCounts.READY_TO_SHIP,
        unprocessed: processCounts.to_process,
        processed: processCounts.processed,
        dikirim: tabCounts.SHIPPED,
      },
      data: formattedOrders,
      total: tabCounts.ALL,
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
      `SELECT oi.*, mp.sku as master_sku, mp.cost_price as master_cost_price, mp.name as master_name 
       FROM order_items oi 
       LEFT JOIN master_products mp ON mp.id = oi.master_product_id 
       WHERE oi.order_id = ?`,
      [ord.id]
    );

    let totalQty = 0;
    let hpp = 0;
    const formattedItems = items.map(it => {
      const q = parseInt(it.quantity || 1, 10);
      totalQty += q;
      const unitP = parseFloat(it.unit_price || 0);
      const totP = parseFloat(it.total_price || 0);
      const sub = parseFloat(it.hpp_subtotal || 0);
      const cost = parseFloat(it.cost_price || it.master_cost_price || 0);
      if (sub > 0) hpp += sub;
      else hpp += (cost * q);

      return {
        id: it.id,
        name: it.product_name || it.master_name || 'Produk',
        sku: it.master_sku || it.sku || '-',
        qty: q,
        price: unitP > 0 ? unitP : (q > 0 && totP > 0 ? totP / q : totP),
        totalPrice: totP > 0 ? totP : (unitP * q),
        costPrice: cost,
      };
    });
    if (totalQty <= 0) totalQty = 1;

    let returnMap = {};
    try {
      const [retRow] = await query('SELECT refund_amount FROM return_orders WHERE order_id = ? AND tenant_id = ? LIMIT 1', [ord.id, tenantId]);
      if (retRow) returnMap[ord.id] = parseFloat(retRow.refund_amount || 0);
    } catch (_) {}

    const omset = parseFloat(ord.total_amount || 0);
    const absFee = calculateOrderFee(ord);
    const refund = calculateOrderRefund(ord, returnMap);

    const isFullRefundOrReturn = ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'].includes(String(ord.order_status || '').toUpperCase())
      || (omset > 0 && refund >= omset);

    if (isFullRefundOrReturn) {
      hpp = 0;
    } else if (refund > 0 && omset > 0 && refund < omset) {
      const remainingRatio = Math.max(0, (omset - refund) / omset);
      hpp = Math.round(hpp * remainingRatio);
    }

    const netAmount = parseFloat(ord.net_amount || 0);
    const releasedVal = netAmount > 0 ? netAmount : Math.max(0, omset - absFee);
    const margin = releasedVal - hpp;
    const marginPct = releasedVal > 0 ? Math.round((margin / releasedVal) * 10000) / 100 : 0;

    let commission = 0;
    try {
      const [team] = await query(`
        SELECT mt.commission_type, mt.commission_rate, mt.reward_per_qty
        FROM marketing_team_stores mts
        JOIN marketing_teams mt ON mt.id = mts.marketing_team_id
        WHERE mts.store_id = ? AND mt.tenant_id = ? AND mt.is_active = 1
        LIMIT 1
      `, [ord.store_id, tenantId]);
      if (team) {
        if (team.commission_type === 'fixed_per_qty') {
          commission = totalQty * parseFloat(team.reward_per_qty || 0);
        } else {
          commission = Math.max(0, margin * (parseFloat(team.commission_rate || 0) / 100));
        }
      }
    } catch (_) {}

    const inv = ord.invoice_number || ord.order_marketplace_id || `ORD-${ord.id}`;
    const cDate = ord.completed_at ? formatDateIndo(ord.completed_at) : (ord.order_date ? formatDateIndo(ord.order_date) : '');

    return res.json({
      success: true,
      data: {
        id: ord.id,
        orderNumber: ord.order_marketplace_id || ord.order_number || inv,
        invoiceNumber: inv,
        storeName: ord.store_name,
        channel: ord.channel_name,
        status: ord.order_status,
        orderDate: ord.order_date,
        completedAt: cDate,
        buyerName: ord.buyer_name || 'Pelanggan MP',
        buyerPhone: ord.buyer_phone || '',
        shippingAddress: ord.shipping_address || '',
        courier: ord.courier || ord.shipping_provider || 'Reguler',
        resi: ord.tracking_number || '',
        trackingNumber: ord.tracking_number || '',
        quantity: totalQty,
        itemsCount: totalQty,
        totalAmount: omset,
        omset,
        netAmount: releasedVal,
        netReleased: releasedVal,
        releasedValue: releasedVal,
        marketplaceFee: absFee,
        totalFee: absFee,
        refund,
        hpp,
        hppModal: hpp,
        margin,
        marginRp: margin,
        marginPct,
        commission: Math.round(commission),
        items: formattedItems
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

    const monthParam = parseInt(req.query.month, 10);
    const yearParam = parseInt(req.query.year, 10);

    if (!isNaN(monthParam) && !isNaN(yearParam)) {
      dateFrom = new Date(yearParam, monthParam - 1, 1, 0, 0, 0);
      dateTo = new Date(yearParam, monthParam, 0, 23, 59, 59);
      const monthsIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      periodLabel = `${monthsIndo[monthParam] || monthParam} ${yearParam}`;
    } else if (req.query.date_from && req.query.date_to) {
      dateFrom = new Date(req.query.date_from + 'T00:00:00');
      dateTo = new Date(req.query.date_to + 'T23:59:59');
      periodLabel = `${req.query.date_from} s/d ${req.query.date_to}`;
    } else if (period === 'today') {
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
        other: 'Lain Lain',
        others: 'Lain Lain',
      };

      const expGrouped = new Map();
      for (const exp of expenses) {
        const catKey = (exp.category || 'other').toLowerCase();
        if (!expGrouped.has(catKey)) {
          expGrouped.set(catKey, {
            code: catKey,
            name: labelMap[catKey] || (exp.category ? exp.category.replace(/_/g, ' ') : 'Lain Lain'),
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

// ─── 8. MUTASI KEUANGAN V2 (KAS MASUK, KELUAR, DAN TRANSFER) ───────────────
app.get('/api/v2/owner/mutasi-keuangan', async (req, res) => {
  try {
    const tenantId = req.headers['x-tenant-id'] || '1';
    const now = new Date();
    const curYear = now.getFullYear();
    const curMonth = String(now.getMonth() + 1).padStart(2, '0');
    const todayStr = `${curYear}-${curMonth}-${String(now.getDate()).padStart(2, '0')}`;
    const firstDayStr = `${curYear}-${curMonth}-01`;

    const dateFrom = req.query.date_from || firstDayStr;
    const dateTo = req.query.date_to || todayStr;
    const account = req.query.account || 'all';
    const category = req.query.category || 'all';
    const direction = req.query.direction || 'all';
    const sourceType = req.query.source_type || 'all';
    const search = (req.query.search || '').trim().toLowerCase();

    const formatMutationCategory = (raw) => {
      if (!raw) return 'Lain Lain';
      const clean = String(raw).trim().toLowerCase();
      const map = {
        'other': 'Lain Lain',
        'others': 'Lain Lain',
        'lain-lain': 'Lain Lain',
        'lain_lain': 'Lain Lain',
        'utilities': 'Utilitas & Operasional',
        'utility': 'Utilitas & Operasional',
        'salary': 'Gaji Karyawan',
        'salaries': 'Gaji Karyawan',
        'rent': 'Sewa Tempat',
        'ad': 'Iklan & Marketing',
        'ads': 'Iklan & Marketing',
        'advertising': 'Iklan & Marketing',
        'marketing': 'Iklan & Marketing',
        'operational': 'Operasional',
        'supplies': 'Packing & Perlengkapan',
        'packing': 'Packing & Perlengkapan',
        'maintenance': 'Pemeliharaan',
        'tax': 'Pajak',
        'pemasukan lain': 'Pemasukan Lain',
        'pemasukan_lain': 'Pemasukan Lain',
      };
      if (map[clean]) return map[clean];
      return String(raw).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    // 1. Bank Accounts
    const bankAccounts = await query(
      'SELECT id, bank_name, account_number, account_name FROM bank_accounts WHERE tenant_id = ? AND is_active = 1 ORDER BY bank_name ASC',
      [tenantId]
    );

    const resolveAccountLabel = (val) => {
      if (!val || val === 'all') return 'Semua Akun Kas / Bank';
      const b = bankAccounts.find(x => String(x.id) === String(val) || String(x.bank_name).toLowerCase() === String(val).toLowerCase());
      if (b) {
        let lbl = b.bank_name;
        if (b.account_number) lbl += ` (${b.account_number})`;
        if (b.account_name) lbl += ` - ${b.account_name}`;
        return lbl;
      }
      if (val === 'kas_kecil') return 'Kas Kecil';
      if (val === 'kas_besar') return 'Kas Besar';
      return String(val).replace(/_/g, ' ').toUpperCase();
    };

    const matchedBank = bankAccounts.find(x => String(x.id) === String(account) || String(x.bank_name).toLowerCase() === String(account).toLowerCase());

    const accountMatches = (recordVal) => {
      if (!recordVal || account === 'all') return true;
      if (String(recordVal).toLowerCase() === String(account).toLowerCase()) return true;
      if (matchedBank) {
        if (String(recordVal).toLowerCase() === String(matchedBank.bank_name).toLowerCase()) return true;
        if (String(recordVal) === String(matchedBank.id)) return true;
      }
      return false;
    };

    // 2. Saldo Awal (Beginning Balance before dateFrom)
    let beginningBalance = 0;

    let prevIncomesQuery = 'SELECT SUM(amount) as tot FROM incomes WHERE tenant_id = ? AND income_date < ?';
    const prevIncParams = [tenantId, dateFrom];
    if (account !== 'all') {
      if (matchedBank) {
        prevIncomesQuery += ' AND (payment_destination = ? OR payment_destination = ? OR payment_destination = ?)';
        prevIncParams.push(account, matchedBank.bank_name, String(matchedBank.id));
      } else {
        prevIncomesQuery += ' AND payment_destination = ?';
        prevIncParams.push(account);
      }
    }
    const prevIncRow = await query(prevIncomesQuery, prevIncParams);
    beginningBalance += parseFloat(prevIncRow[0]?.tot || 0);

    let prevExpensesQuery = 'SELECT SUM(amount) as tot FROM expenses WHERE tenant_id = ? AND expense_date < ?';
    const prevExpParams = [tenantId, dateFrom];
    if (account !== 'all') {
      if (matchedBank) {
        prevExpensesQuery += ' AND (payment_source = ? OR payment_source = ? OR payment_source = ?)';
        prevExpParams.push(account, matchedBank.bank_name, String(matchedBank.id));
      } else {
        prevExpensesQuery += ' AND payment_source = ?';
        prevExpParams.push(account);
      }
    }
    const prevExpRow = await query(prevExpensesQuery, prevExpParams);
    beginningBalance -= parseFloat(prevExpRow[0]?.tot || 0);

    const prevTransfers = await query(
      'SELECT source, destination, amount FROM fund_transfers WHERE tenant_id = ? AND transfer_date < ?',
      [tenantId, dateFrom]
    );
    for (const pt of prevTransfers) {
      const amt = parseFloat(pt.amount || 0);
      if (account !== 'all') {
        if (accountMatches(pt.destination)) beginningBalance += amt;
        if (accountMatches(pt.source)) beginningBalance -= amt;
      }
    }

    // 3. Transactions within period
    let transactions = [];

    // A. Incomes
    if (sourceType === 'all' || sourceType === 'income') {
      let incQuery = 'SELECT id, title, category, payment_destination, amount, income_date, description FROM incomes WHERE tenant_id = ? AND income_date >= ? AND income_date <= ?';
      const incParams = [tenantId, dateFrom, dateTo];
      if (account !== 'all') {
        if (matchedBank) {
          incQuery += ' AND (payment_destination = ? OR payment_destination = ? OR payment_destination = ?)';
          incParams.push(account, matchedBank.bank_name, String(matchedBank.id));
        } else {
          incQuery += ' AND payment_destination = ?';
          incParams.push(account);
        }
      }
      if (category !== 'all') {
        incQuery += ' AND category = ?';
        incParams.push(category);
      }
      const incRows = await query(incQuery, incParams);
      for (const inc of incRows) {
        const fullDesc = `${inc.title || ''}${inc.description ? ' (' + inc.description + ')' : ''}`;
        if (search && !fullDesc.toLowerCase().includes(search) && !String(inc.category || '').toLowerCase().includes(search)) {
          continue;
        }
        const amt = parseFloat(inc.amount || 0);
        const dStr = extractDateStr(inc.income_date);
        transactions.push({
          id: inc.id,
          model_type: 'income',
          datetime: `${dStr} 00:00:00`,
          date_formatted: formatDateIndo(dStr),
          date: dStr,
          reference: `INC-${String(inc.id).padStart(5, '0')}`,
          type: 'income',
          type_label: 'Pemasukan',
          type_badge: 'bg-success',
          category_label: formatMutationCategory(inc.category || 'Pemasukan Lain'),
          account_label: resolveAccountLabel(inc.payment_destination),
          description: fullDesc,
          inflow: amt,
          outflow: 0
        });
      }
    }

    // B. Expenses
    if (sourceType === 'all' || sourceType === 'expense') {
      let expQuery = `SELECT e.id, e.title, e.category, e.payment_source, e.amount, e.expense_date, e.description,
                             e.employee_id, emp.name as employee_name
                      FROM expenses e
                      LEFT JOIN employees emp ON emp.id = e.employee_id
                      WHERE e.tenant_id = ? AND e.expense_date >= ? AND e.expense_date <= ?`;
      const expParams = [tenantId, dateFrom, dateTo];
      if (account !== 'all') {
        if (matchedBank) {
          expQuery += ' AND (e.payment_source = ? OR e.payment_source = ? OR e.payment_source = ?)';
          expParams.push(account, matchedBank.bank_name, String(matchedBank.id));
        } else {
          expQuery += ' AND e.payment_source = ?';
          expParams.push(account);
        }
      }
      if (category !== 'all') {
        expQuery += ' AND e.category = ?';
        expParams.push(category);
      }
      const expRows = await query(expQuery, expParams);
      for (const exp of expRows) {
        let desc = exp.title || '';
        if (exp.employee_name) desc += ` [PIC: ${exp.employee_name}]`;
        if (exp.description) desc += ` - ${exp.description}`;
        if (search && !desc.toLowerCase().includes(search) && !String(exp.category || '').toLowerCase().includes(search)) {
          continue;
        }
        const amt = parseFloat(exp.amount || 0);
        const dStr = extractDateStr(exp.expense_date);
        transactions.push({
          id: exp.id,
          model_type: 'expense',
          datetime: `${dStr} 00:00:00`,
          date_formatted: formatDateIndo(dStr),
          date: dStr,
          reference: `EXP-${String(exp.id).padStart(5, '0')}`,
          type: 'expense',
          type_label: 'Pengeluaran',
          type_badge: 'bg-danger',
          category_label: formatMutationCategory(exp.category || 'Pengeluaran'),
          account_label: resolveAccountLabel(exp.payment_source),
          description: desc,
          inflow: 0,
          outflow: amt
        });
      }
    }

    // C. Fund Transfers
    if ((sourceType === 'all' || sourceType === 'transfer') && (category === 'all' || category === 'transfer')) {
      const trQuery = 'SELECT id, source, destination, amount, transfer_date, description FROM fund_transfers WHERE tenant_id = ? AND transfer_date >= ? AND transfer_date <= ?';
      const trRows = await query(trQuery, [tenantId, dateFrom, dateTo]);
      for (const tr of trRows) {
        const srcLabel = resolveAccountLabel(tr.source);
        const dstLabel = resolveAccountLabel(tr.destination);
        const amt = parseFloat(tr.amount || 0);
        const dStr = extractDateStr(tr.transfer_date);
        const baseDesc = tr.description ? ` (${tr.description})` : '';

        if (search && !String(tr.description || '').toLowerCase().includes(search) && !srcLabel.toLowerCase().includes(search) && !dstLabel.toLowerCase().includes(search)) {
          continue;
        }

        if (account === 'all') {
          transactions.push({
            id: tr.id,
            model_type: 'transfer',
            datetime: `${dStr} 00:00:00`,
            date_formatted: formatDateIndo(dStr),
            date: dStr,
            reference: `TRF-${String(tr.id).padStart(5, '0')}`,
            type: 'transfer',
            type_label: 'Transfer Kas',
            type_badge: 'bg-warning',
            category_label: 'Transfer Internal',
            account_label: `${srcLabel} ➔ ${dstLabel}`,
            description: `Pindah dana dari ${srcLabel} ke ${dstLabel}${baseDesc}`,
            inflow: 0,
            outflow: 0
          });
        } else {
          if (accountMatches(tr.source)) {
            transactions.push({
              id: tr.id,
              model_type: 'transfer_out',
              datetime: `${dStr} 00:00:00`,
              date_formatted: formatDateIndo(dStr),
              date: dStr,
              reference: `TRF-${String(tr.id).padStart(5, '0')}`,
              type: 'transfer_out',
              type_label: 'Transfer Keluar',
              type_badge: 'bg-warning',
              category_label: 'Transfer Keluar',
              account_label: srcLabel,
              description: `Transfer keluar ke ${dstLabel}${baseDesc}`,
              inflow: 0,
              outflow: amt
            });
          }
          if (accountMatches(tr.destination)) {
            transactions.push({
              id: tr.id,
              model_type: 'transfer_in',
              datetime: `${dStr} 00:00:00`,
              date_formatted: formatDateIndo(dStr),
              date: dStr,
              reference: `TRF-${String(tr.id).padStart(5, '0')}`,
              type: 'transfer_in',
              type_label: 'Transfer Masuk',
              type_badge: 'bg-info',
              category_label: 'Transfer Masuk',
              account_label: dstLabel,
              description: `Transfer masuk dari ${srcLabel}${baseDesc}`,
              inflow: amt,
              outflow: 0
            });
          }
        }
      }
    }

    // Direction filter
    if (direction === 'in') {
      transactions = transactions.filter(t => t.inflow > 0);
    } else if (direction === 'out') {
      transactions = transactions.filter(t => t.outflow > 0);
    }

    // Sort Chronologically (ASC) to compute running balance
    transactions.sort((a, b) => {
      if (a.datetime < b.datetime) return -1;
      if (a.datetime > b.datetime) return 1;
      return a.id - b.id;
    });

    let running = beginningBalance;
    let totalInflow = 0;
    let totalOutflow = 0;
    let inflowCount = 0;
    let outflowCount = 0;

    for (const t of transactions) {
      if (t.inflow > 0) {
        totalInflow += t.inflow;
        inflowCount++;
      }
      if (t.outflow > 0) {
        totalOutflow += t.outflow;
        outflowCount++;
      }
      running = running + t.inflow - t.outflow;
      t.running_balance = running;
    }

    const endingBalance = running;
    const netCashFlow = totalInflow - totalOutflow;

    // Reverse for UI display (newest first)
    transactions.reverse();

    // Master lists for modal dropdowns
    const expenseCategories = await query(
      'SELECT id, name FROM finance_categories WHERE tenant_id = ? AND type = "expense" AND is_active = 1 ORDER BY name ASC',
      [tenantId]
    );
    const incomeCategories = await query(
      'SELECT id, name FROM finance_categories WHERE tenant_id = ? AND type = "income" AND is_active = 1 ORDER BY name ASC',
      [tenantId]
    );
    const employees = await query(
      'SELECT id, name FROM employees WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC',
      [tenantId]
    );

    return res.json({
      success: true,
      summary: {
        beginningBalance: Math.round(beginningBalance),
        totalInflow: Math.round(totalInflow),
        totalOutflow: Math.round(totalOutflow),
        netCashFlow: Math.round(netCashFlow),
        endingBalance: Math.round(endingBalance),
        inflowCount,
        outflowCount,
        totalCount: transactions.length
      },
      filters: {
        dateFrom,
        dateTo,
        account,
        category,
        direction,
        search
      },
      bankAccounts,
      expenseCategories,
      incomeCategories,
      employees,
      mutations: transactions
    });
  } catch (err) {
    console.error('[Node-API] Mutasi keuangan error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Create Income
app.post('/api/v2/owner/mutasi-keuangan/income', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { title, category, payment_destination, amount, income_date, description } = req.body;
    if (!title || !amount || parseFloat(amount) <= 0) {
      return res.status(422).json({ success: false, message: 'Judul dan nominal wajib diisi.' });
    }
    const result = await query(
      `INSERT INTO incomes (tenant_id, title, category, payment_destination, amount, income_date, description, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [tenantId, title, category || 'Pemasukan Lain', payment_destination || 'kas_besar', parseFloat(amount), income_date || new Date(), description || null]
    );
    return res.json({ success: true, message: 'Pemasukan berhasil dicatat!', id: result.insertId });
  } catch (err) {
    console.error('[Node-API] Create income error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Create Expense
app.post('/api/v2/owner/mutasi-keuangan/expense', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { title, category, payment_source, amount, expense_date, description, employee_id } = req.body;
    if (!title || !amount || parseFloat(amount) <= 0) {
      return res.status(422).json({ success: false, message: 'Judul dan nominal wajib diisi.' });
    }
    const result = await query(
      `INSERT INTO expenses (tenant_id, employee_id, title, category, payment_source, amount, expense_date, description, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [tenantId, employee_id || null, title, category || 'Operasional', payment_source || 'kas_besar', parseFloat(amount), expense_date || new Date(), description || null]
    );
    return res.json({ success: true, message: 'Pengeluaran berhasil dicatat!', id: result.insertId });
  } catch (err) {
    console.error('[Node-API] Create expense error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Create Fund Transfer
app.post('/api/v2/owner/mutasi-keuangan/transfer', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { source, destination, amount, transfer_date, description } = req.body;
    if (!source || !destination || !amount || parseFloat(amount) <= 0) {
      return res.status(422).json({ success: false, message: 'Akun asal, tujuan, dan nominal wajib diisi.' });
    }
    if (source === destination) {
      return res.status(422).json({ success: false, message: 'Akun sumber dan akun tujuan tidak boleh sama.' });
    }
    const result = await query(
      `INSERT INTO fund_transfers (tenant_id, source, destination, amount, transfer_date, description, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [tenantId, source, destination, parseFloat(amount), transfer_date || new Date(), description || null]
    );
    return res.json({ success: true, message: 'Transfer dana berhasil dicatat!', id: result.insertId });
  } catch (err) {
    console.error('[Node-API] Create transfer error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Delete Mutation
app.delete('/api/v2/owner/mutasi-keuangan/:type/:id', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { type, id } = req.params;
    let table = null;
    if (type === 'income') table = 'incomes';
    else if (type === 'expense') table = 'expenses';
    else if (type === 'transfer' || type === 'transfer_in' || type === 'transfer_out') table = 'fund_transfers';
    else return res.status(400).json({ success: false, message: 'Tipe mutasi tidak valid.' });

    await query(`DELETE FROM ${table} WHERE id = ? AND tenant_id = ?`, [id, tenantId]);
    return res.json({ success: true, message: 'Transaksi mutasi berhasil dihapus!' });
  } catch (err) {
    console.error('[Node-API] Delete mutation error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 8. SALDO MARKETPLACE V2 ────────────────────────────────────────────────
app.get('/api/v2/owner/saldo-marketplace', async (req, res) => {
  try {
    const tenantId = getTenantId(req);

    const stores = await query(
      `SELECT s.id, s.store_name, s.status, s.updated_at,
              c.name as channel_name, c.code as channel_code
       FROM stores s
       LEFT JOIN channels c ON c.id = s.channel_id
       WHERE s.tenant_id = ?
       ORDER BY s.store_name ASC`,
      [tenantId]
    );

    let totalWalletBalance = 0;
    let totalPendingBalance = 0;
    let totalPendingCount = 0;

    const activeStatuses = [
      'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'IN_TRANSIT',
      'DELIVERED', 'UNPAID', 'ON_HOLD', '111', '112', '114',
      'PERLU_DIKIRIM', 'DIKIRIM', 'MENUNGGU_DIKIRIM'
    ];
    const statusPlaceholders = activeStatuses.map(() => '?').join(',');

    const storeCards = [];

    for (const store of stores) {
      let currentBalance = 0;
      let lastTxDate = null;
      try {
        const [latestTx] = await query(
          `SELECT current_balance, transaction_date 
           FROM marketplace_wallet_transactions 
           WHERE store_id = ? AND current_balance IS NOT NULL 
           ORDER BY transaction_date DESC, id DESC LIMIT 1`,
          [store.id]
        );
        if (latestTx && latestTx.current_balance !== null) {
          currentBalance = parseFloat(latestTx.current_balance || 0);
          lastTxDate = latestTx.transaction_date;
        }
      } catch (_) {}

      let storePendingBalance = 0;
      let storePendingCount = 0;
      try {
        const pendingOrders = await query(
          `SELECT total_amount, net_amount, marketplace_fee, financial_breakdown 
           FROM orders 
           WHERE tenant_id = ? AND store_id = ? AND UPPER(order_status) IN (${statusPlaceholders})`,
          [tenantId, store.id, ...activeStatuses]
        );

        storePendingCount = pendingOrders.length;
        for (const ord of pendingOrders) {
          let escrow = 0;
          if (ord.financial_breakdown) {
            let fb = ord.financial_breakdown;
            if (typeof fb === 'string') {
              try { fb = JSON.parse(fb); } catch (_) {}
            }
            if (fb && typeof fb === 'object') {
              escrow = parseFloat(fb.escrow_amount_after_adjustment || fb.escrow_amount || fb.settlement_amount || 0);
            }
          }
          if (escrow <= 0 && parseFloat(ord.net_amount || 0) > 0) {
            escrow = parseFloat(ord.net_amount);
          }
          if (escrow <= 0) {
            const gross = parseFloat(ord.total_amount || 0);
            const fee = parseFloat(ord.marketplace_fee || 0);
            escrow = Math.max(0, gross - fee);
          }
          storePendingBalance += escrow;
        }
      } catch (_) {}

      totalWalletBalance += currentBalance;
      totalPendingBalance += storePendingBalance;
      totalPendingCount += storePendingCount;

      storeCards.push({
        id: store.id,
        name: store.store_name,
        channelName: store.channel_name || 'Marketplace',
        channelCode: (store.channel_code || '').toLowerCase(),
        status: store.status || 'connected',
        currentBalance,
        pendingBalance: storePendingBalance,
        pendingCount: storePendingCount,
        totalEstimated: currentBalance + storePendingBalance,
        lastSyncedAt: lastTxDate ? formatDateIndo(lastTxDate) : (store.updated_at ? formatDateIndo(store.updated_at) : 'Belum sync'),
      });
    }

    return res.json({
      success: true,
      data: {
        totalWalletBalance,
        totalPendingBalance,
        totalPendingCount,
        totalEstimated: totalWalletBalance + totalPendingBalance,
        stores: storeCards,
      }
    });
  } catch (err) {
    console.error('[Node-API] Saldo marketplace error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Mutasi Dompet per Toko
app.get('/api/v2/owner/saldo-marketplace/:storeId/mutasi', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const storeId = req.params.storeId;
    const dateFrom = req.query.date_from;
    const dateTo = req.query.date_to;
    const search = req.query.search;

    const [store] = await query(
      `SELECT s.id, s.store_name, s.status, c.name as channel_name, c.code as channel_code 
       FROM stores s 
       LEFT JOIN channels c ON c.id = s.channel_id 
       WHERE s.id = ? AND s.tenant_id = ?`,
      [storeId, tenantId]
    );

    if (!store) {
      return res.status(404).json({ success: false, message: 'Toko tidak ditemukan.' });
    }

    let whereSql = `WHERE store_id = ?`;
    const params = [storeId];

    if (dateFrom) {
      whereSql += ` AND transaction_date >= ?`;
      params.push(dateFrom + ' 00:00:00');
    }
    if (dateTo) {
      whereSql += ` AND transaction_date <= ?`;
      params.push(dateTo + ' 23:59:59');
    }
    if (search) {
      whereSql += ` AND (transaction_id LIKE ? OR description LIKE ?)`;
      params.push(`%${search}%`, `%${search}%`);
    }

    const rows = await query(
      `SELECT * FROM marketplace_wallet_transactions 
       ${whereSql} 
       ORDER BY transaction_date DESC, id DESC 
       LIMIT 100`,
      params
    );

    const formatted = rows.map(r => ({
      id: r.id,
      transactionId: r.transaction_id || `TX-${r.id}`,
      transactionDate: r.transaction_date,
      dateFormatted: formatDateIndo(r.transaction_date),
      type: r.type || 'SETTLEMENT',
      description: r.description || 'Pelepasan Saldo Dompet',
      amount: parseFloat(r.amount || 0),
      direction: r.direction || 'in',
      currentBalance: r.current_balance !== null ? parseFloat(r.current_balance || 0) : null,
    }));

    return res.json({
      success: true,
      store: {
        id: store.id,
        name: store.store_name,
        channelName: store.channel_name,
        channelCode: (store.channel_code || '').toLowerCase(),
      },
      data: formatted,
    });
  } catch (err) {
    console.error('[Node-API] Mutasi dompet error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 9. LAPORAN PENJUALAN V2 ────────────────────────────────────────────────
app.get('/api/v2/owner/laporan-penjualan', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const now = new Date();
    const dateFrom = req.query.date_from || (`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`);
    const dateTo = req.query.date_to || formatDate(now);
    const storeId = req.query.store_id || null;

    const rep = await getReleasedSalesDetailData(tenantId, dateFrom, dateTo, storeId);

    const storeMap = {};
    const channelMap = {};
    const dateMap = {};

    for (const t of rep.transactions) {
      const sName = t.storeName || 'Toko Lain';
      if (!storeMap[sName]) {
        storeMap[sName] = {
          storeName: sName,
          channelName: t.channelName || 'Marketplace',
          omset: 0,
          refund: 0,
          fee: 0,
          netReleased: 0,
          hpp: 0,
          margin: 0,
          count: 0,
          quantity: 0
        };
      }
      storeMap[sName].omset += t.omset;
      storeMap[sName].refund += t.refund;
      storeMap[sName].fee += t.marketplaceFee;
      storeMap[sName].netReleased += t.releasedValue;
      storeMap[sName].hpp += t.hppModal;
      storeMap[sName].margin += t.margin;
      storeMap[sName].count += 1;
      storeMap[sName].quantity += t.quantity;

      const cName = t.channelName || 'Shopee';
      if (!channelMap[cName]) {
        channelMap[cName] = { channelName: cName, omset: 0, margin: 0, count: 0, quantity: 0 };
      }
      channelMap[cName].omset += t.omset;
      channelMap[cName].margin += t.margin;
      channelMap[cName].count += 1;
      channelMap[cName].quantity += t.quantity;

      const dKey = extractDateStr(t.completedAt || t.orderDate);
      if (dKey) {
        if (!dateMap[dKey]) {
          dateMap[dKey] = { date: dKey, omset: 0, margin: 0, count: 0 };
        }
        dateMap[dKey].omset += t.omset;
        dateMap[dKey].margin += t.margin;
        dateMap[dKey].count += 1;
      }
    }

    const productMap = {};
    for (const t of rep.transactions) {
      for (const it of (t.items || [])) {
        const pKey = it.sku || it.name || 'Produk';
        if (!productMap[pKey]) {
          productMap[pKey] = {
            sku: it.sku || '-',
            name: it.name || it.productName || 'Produk',
            quantity: 0,
            omset: 0,
            hpp: 0,
            margin: 0
          };
        }
        const q = parseInt(it.qty || it.quantity || 1, 10);
        const itemOmset = parseFloat(it.totalPrice || (it.price * q) || 0);
        const itemCost = parseFloat(it.costPrice || 0) * q;
        productMap[pKey].quantity += q;
        productMap[pKey].omset += itemOmset;
        productMap[pKey].hpp += itemCost;
        productMap[pKey].margin += (itemOmset - itemCost);
      }
    }

    const byStore = Object.values(storeMap).sort((a, b) => b.omset - a.omset);
    const byChannel = Object.values(channelMap).sort((a, b) => b.omset - a.omset);
    const byDate = Object.values(dateMap).sort((a, b) => a.date.localeCompare(b.date));
    const byProduct = Object.values(productMap).sort((a, b) => b.quantity - a.quantity);

    let storeOptions = [];
    try {
      storeOptions = await query(
        `SELECT s.id, s.store_name, c.name as channel_name 
         FROM stores s 
         LEFT JOIN channels c ON c.id = s.channel_id 
         WHERE s.tenant_id = ? 
         ORDER BY s.store_name ASC`,
        [tenantId]
      );
    } catch (_) {}

    return res.json({
      success: true,
      filters: {
        dateFrom,
        dateTo,
        storeId,
        stores: storeOptions.map(s => ({ id: s.id, name: s.store_name, channel: s.channel_name })),
      },
      summary: {
        omset: rep.grandTotalOmset,
        refund: rep.grandTotalRefund,
        totalFee: rep.grandTotalTotalFee,
        netReleased: rep.grandTotalNetReleased,
        hpp: rep.grandTotalHpp,
        margin: rep.grandTotalMarginRp,
        marginPct: rep.grandTotalNetReleased > 0 ? Math.round((rep.grandTotalMarginRp / rep.grandTotalNetReleased) * 10000) / 100 : 0,
        totalOrders: rep.totalCount,
        totalQty: rep.grandTotalQty,
      },
      byStore,
      byChannel,
      byDate,
      byProduct,
      transactions: rep.transactions,
    });
  } catch (err) {
    console.error('[Node-API] Laporan penjualan error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 10. RETUR MASUK (SINKRON ERP V2 ReturController) ───────────────────────
app.get('/api/v2/owner/retur', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { search, store_id: storeId, status, is_restocked: isRestocked } = req.query;
    const page = Math.max(1, parseInt(req.query.page || '1', 10));
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit || '30', 10)));

    let baseWhere = 'r.tenant_id = ?';
    const baseParams = [tenantId];
    if (search) {
      baseWhere += ' AND (r.return_sn LIKE ? OR o.invoice_number LIKE ? OR o.order_marketplace_id LIKE ?)';
      const s = `%${search}%`;
      baseParams.push(s, s, s);
    }
    if (storeId) {
      baseWhere += ' AND r.store_id = ?';
      baseParams.push(storeId);
    }

    let listWhere = baseWhere;
    const listParams = [...baseParams];
    if (status) {
      listWhere += ' AND r.status = ?';
      listParams.push(status);
    }
    if (isRestocked !== undefined && isRestocked !== '') {
      listWhere += ' AND r.is_restocked = ?';
      listParams.push(parseInt(isRestocked, 10) ? 1 : 0);
    }

    const fromSql = `FROM return_orders r
      LEFT JOIN orders o ON o.id = r.order_id
      LEFT JOIN stores s ON s.id = r.store_id
      LEFT JOIN channels c ON c.id = s.channel_id`;

    const [statRow] = await query(
      `SELECT COUNT(*) AS total,
              SUM(CASE WHEN r.is_restocked = 0 OR r.is_restocked IS NULL THEN 1 ELSE 0 END) AS pending_qc,
              SUM(CASE WHEN r.is_restocked = 1 THEN 1 ELSE 0 END) AS already_qc,
              SUM(CASE WHEN UPPER(r.status) = 'REQUESTED' THEN 1 ELSE 0 END) AS requested,
              SUM(CASE WHEN UPPER(r.status) LIKE '%CLOSED%' OR UPPER(r.status) LIKE '%COMPLETED%'
                        OR UPPER(r.status) LIKE '%SUCCESS%' OR UPPER(r.status) LIKE '%REFUNDED%' THEN 1 ELSE 0 END) AS completed,
              COALESCE(SUM(r.refund_amount), 0) AS total_refund
       ${fromSql} WHERE ${baseWhere}`,
      baseParams
    );

    const [cntRow] = await query(`SELECT COUNT(*) AS cnt ${fromSql} WHERE ${listWhere}`, listParams);
    const totalFiltered = parseInt(cntRow?.cnt || 0, 10);

    const rows = await query(
      `SELECT r.*, o.invoice_number, o.order_marketplace_id, o.buyer_name, o.total_amount AS order_total,
              s.store_name, c.name AS channel_name, c.code AS channel_code
       ${fromSql} WHERE ${listWhere}
       ORDER BY r.created_at DESC, r.id DESC
       LIMIT ? OFFSET ?`,
      [...listParams, limit, (page - 1) * limit]
    );

    // Items: return_order_items -> order_items; fallback order_items milik order
    const retIds = rows.map(r => r.id);
    const orderIds = rows.map(r => r.order_id).filter(Boolean);
    const retItemsMap = {};
    const orderItemsMap = {};
    if (retIds.length) {
      try {
        const ri = await query(
          `SELECT roi.id AS return_item_id, roi.return_order_id, roi.quantity AS ret_qty, roi.inspection_status, roi.inspection_notes, oi.*
           FROM return_order_items roi
           LEFT JOIN order_items oi ON oi.id = roi.order_item_id
           WHERE roi.return_order_id IN (?)`,
          [retIds]
        );
        for (const it of ri) {
          (retItemsMap[it.return_order_id] = retItemsMap[it.return_order_id] || []).push({
            id: it.return_item_id,
            orderItemId: it.id,
            name: it.product_name || 'Barang Retur',
            sku: it.sku || '-',
            variant: it.variant_name || '',
            image: it.product_image || '',
            qty: parseInt(it.ret_qty || 1, 10),
            price: parseFloat(it.unit_price || 0),
            inspectionStatus: it.inspection_status || '',
            inspectionNotes: it.inspection_notes || '',
          });
        }
      } catch (_) {}
    }
    if (orderIds.length) {
      try {
        const oi = await query(
          `SELECT * FROM order_items WHERE order_id IN (?)`,
          [orderIds]
        );
        for (const it of oi) {
          (orderItemsMap[it.order_id] = orderItemsMap[it.order_id] || []).push({
            id: null,
            orderItemId: it.id,
            name: it.product_name || 'Barang Retur',
            sku: it.sku || '-',
            variant: it.variant_name || '',
            image: it.product_image || '',
            qty: parseInt(it.quantity || 1, 10),
            price: parseFloat(it.unit_price || 0),
            inspectionStatus: '',
            inspectionNotes: '',
          });
        }
      } catch (_) {}
    }

    const data = rows.map(r => {
      const items = (retItemsMap[r.id] && retItemsMap[r.id].length) ? retItemsMap[r.id] : (orderItemsMap[r.order_id] || []);
      return {
        id: r.id,
        returnSn: r.return_sn || `RTR-${r.id}`,
        invoiceNumber: r.invoice_number || r.order_marketplace_id || '-',
        storeName: r.store_name || 'Toko',
        channelName: r.channel_name || 'Marketplace',
        channelCode: (r.channel_code || '').toLowerCase(),
        buyerName: r.buyer_name || '-',
        reason: r.reason || '',
        status: (r.status || 'REQUESTED').toUpperCase(),
        refundAmount: parseFloat(r.refund_amount || 0),
        isRestocked: !!r.is_restocked,
        inspectionStatus: r.inspection_status || '',
        inspectionNotes: r.inspection_notes || '',
        trackingNumber: r.return_tracking_number || '',
        shippingProvider: r.shipping_provider || '',
        replacementOrderId: r.replacement_order_id || null,
        createdAt: r.created_at,
        dateFormatted: formatDateIndo(r.created_at),
        items,
        totalQty: items.reduce((a, b) => a + (b.qty || 0), 0),
      };
    });

    let stores = [];
    try {
      stores = await query(
        `SELECT s.id, s.store_name, c.name AS channel_name FROM stores s
         LEFT JOIN channels c ON c.id = s.channel_id WHERE s.tenant_id = ? ORDER BY s.store_name`,
        [tenantId]
      );
    } catch (_) {}

    let statuses = [];
    try {
      const st = await query(
        'SELECT DISTINCT status FROM return_orders WHERE tenant_id = ? AND status IS NOT NULL',
        [tenantId]
      );
      statuses = st.map(x => x.status);
    } catch (_) {}

    return res.json({
      success: true,
      stats: {
        total: parseInt(statRow?.total || 0, 10),
        pendingQc: parseInt(statRow?.pending_qc || 0, 10),
        alreadyQc: parseInt(statRow?.already_qc || 0, 10),
        requested: parseInt(statRow?.requested || 0, 10),
        completed: parseInt(statRow?.completed || 0, 10),
        totalRefund: parseFloat(statRow?.total_refund || 0),
      },
      pagination: { page, limit, total: totalFiltered, lastPage: Math.max(1, Math.ceil(totalFiltered / limit)) },
      filters: {
        stores: stores.map(s => ({ id: s.id, name: s.store_name, channel: s.channel_name })),
        statuses,
      },
      data,
    });
  } catch (err) {
    console.error('[Node-API] Retur error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Terima Barang & Simpan Hasil QC Retur
app.post('/api/v2/owner/retur/:id/qc', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const returnId = req.params.id;
    const { items, inspection_status, inspection_notes } = req.body;

    const [ret] = await query(
      `SELECT r.*, s.store_name, o.id AS orig_order_id, o.invoice_number 
       FROM return_orders r
       LEFT JOIN stores s ON s.id = r.store_id
       LEFT JOIN orders o ON o.id = r.order_id
       WHERE r.id = ? AND r.tenant_id = ?`,
      [returnId, tenantId]
    );

    if (!ret) {
      return res.status(404).json({ success: false, message: 'Data retur tidak ditemukan.' });
    }

    // Ambil item retur yang tersimpan
    let rItems = await query(
      `SELECT roi.*, oi.master_product_id, oi.marketplace_product_id, oi.sku, oi.product_name
       FROM return_order_items roi
       LEFT JOIN order_items oi ON oi.id = roi.order_item_id
       WHERE roi.return_order_id = ?`,
      [returnId]
    );

    // Jika tabel return_order_items masih kosong (data lama), isi dari order_items
    if (!rItems || rItems.length === 0) {
      if (ret.order_id) {
        const origOrderItems = await query(
          `SELECT * FROM order_items WHERE order_id = ?`,
          [ret.order_id]
        );
        for (const oi of origOrderItems) {
          try {
            await query(
              `INSERT INTO return_order_items (return_order_id, order_item_id, quantity, inspection_status, inspection_notes, created_at, updated_at)
               VALUES (?, ?, ?, 'PENDING', NULL, NOW(), NOW())`,
              [returnId, oi.id, oi.quantity || 1]
            );
          } catch (_) {}
        }
        rItems = await query(
          `SELECT roi.*, oi.master_product_id, oi.marketplace_product_id, oi.sku, oi.product_name
           FROM return_order_items roi
           LEFT JOIN order_items oi ON oi.id = roi.order_item_id
           WHERE roi.return_order_id = ?`,
          [returnId]
        );
      }
    }

    const itemsInputMap = {};
    if (Array.isArray(items)) {
      items.forEach(it => {
        if (it.id) itemsInputMap[it.id] = it;
        else if (it.orderItemId) itemsInputMap[`oi_${it.orderItemId}`] = it;
      });
    }

    const wasAlreadyRestocked = !!ret.is_restocked;
    let hasGood = false;

    for (const rItem of rItems) {
      const input = itemsInputMap[rItem.id] || itemsInputMap[`oi_${rItem.order_item_id}`] || {};
      const status = input.inspection_status || inspection_status || 'GOOD';
      const notes = input.inspection_notes !== undefined ? input.inspection_notes : (inspection_notes || '');

      if (status === 'GOOD') {
        hasGood = true;
      }

      await query(
        `UPDATE return_order_items 
         SET inspection_status = ?, inspection_notes = ?, updated_at = NOW() 
         WHERE id = ?`,
        [status, notes, rItem.id]
      );

      // Hanya restock jika belum pernah direstock sebelumnya & kondisi GOOD (Layak Jual)
      if (!wasAlreadyRestocked && status === 'GOOD') {
        let masterProductId = rItem.master_product_id;
        if (!masterProductId && rItem.marketplace_product_id) {
          const [mp] = await query(
            `SELECT master_product_id FROM marketplace_products WHERE id = ? LIMIT 1`,
            [rItem.marketplace_product_id]
          );
          if (mp && mp.master_product_id) masterProductId = mp.master_product_id;
        }
        if (!masterProductId && rItem.sku) {
          const [mpBySku] = await query(
            `SELECT id FROM master_products WHERE tenant_id = ? AND sku = ? LIMIT 1`,
            [tenantId, rItem.sku]
          );
          if (mpBySku) masterProductId = mpBySku.id;
        }

        if (masterProductId) {
          const restockQty = Math.max(1, parseInt(rItem.quantity || 1, 10));
          await query(
            `UPDATE master_products SET stock = stock + ?, updated_at = NOW() WHERE id = ?`,
            [restockQty, masterProductId]
          );
          const [freshMp] = await query(`SELECT stock FROM master_products WHERE id = ?`, [masterProductId]);
          const newStock = freshMp ? freshMp.stock : 0;

          const refText = `Terima Retur (Layak Jual): ${ret.return_sn}${notes ? ' - ' + notes : ''}`;
          try {
            await query(
              `INSERT INTO stock_movements (tenant_id, master_product_id, user_id, type, quantity, reference, balance_after, created_at, updated_at)
               VALUES (?, ?, ?, 'in', ?, ?, ?, NOW(), NOW())`,
              [tenantId, masterProductId, null, restockQty, refText, newStock]
            );
          } catch (_) {}

          try {
            await query(
              `UPDATE marketplace_products 
               SET stock = ?, sync_stock = 1, updated_at = NOW() 
               WHERE master_product_id = ?`,
              [newStock, masterProductId]
            );
          } catch (_) {}
        }
      }
    }

    const overallStatus = hasGood ? 'GOOD' : 'DEFECTIVE';
    const finalNotes = inspection_notes || (hasGood ? 'Pemeriksaan fisik: Layak Jual (stok gudang bertambah)' : 'Pemeriksaan fisik: Rusak / Cacat');

    await query(
      `UPDATE return_orders 
       SET is_restocked = 1, inspection_status = ?, inspection_notes = ?, updated_at = NOW() 
       WHERE id = ? AND tenant_id = ?`,
      [overallStatus, finalNotes, returnId, tenantId]
    );

    return res.json({
      success: true,
      message: hasGood 
        ? 'Pemeriksaan QC berhasil disimpan. Barang layak jual telah dimasukkan ke stok gudang.'
        : 'Pemeriksaan QC berhasil disimpan (Barang ditandai rusak/cacat).',
      data: {
        id: returnId,
        isRestocked: true,
        inspectionStatus: overallStatus,
        inspectionNotes: finalNotes
      }
    });
  } catch (err) {
    console.error('[Node-API] Restock QC error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 11. SCAN KEMASAN PRODUK ────────────────────────────────────────────────
app.get('/api/v2/owner/scan-kemasan/search', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const code = (req.query.code || '').trim();

    if (!code) {
      const recent = await query(
        `SELECT id, name, sku, barcode, stock, min_stock, cost_price, price, image_url 
         FROM master_products 
         WHERE tenant_id = ? 
         ORDER BY updated_at DESC LIMIT 12`,
        [tenantId]
      );
      return res.json({ success: true, products: recent, matched: null });
    }

    let [matched] = await query(
      `SELECT id, name, sku, barcode, stock, min_stock, cost_price, price, image_url 
       FROM master_products 
       WHERE tenant_id = ? AND (sku = ? OR barcode = ?) LIMIT 1`,
      [tenantId, code, code]
    );

    if (!matched) {
      const [mp] = await query(
        `SELECT mp.master_product_id 
         FROM marketplace_products mp
         JOIN stores s ON s.id = mp.store_id
         WHERE s.tenant_id = ? AND (mp.marketplace_sku = ? OR mp.barcode = ?) AND mp.master_product_id IS NOT NULL LIMIT 1`,
        [tenantId, code, code]
      );
      if (mp && mp.master_product_id) {
        const [found] = await query(
          `SELECT id, name, sku, barcode, stock, min_stock, cost_price, price, image_url 
           FROM master_products WHERE id = ? AND tenant_id = ? LIMIT 1`,
          [mp.master_product_id, tenantId]
        );
        if (found) matched = found;
      }
    }

    let suggestions = [];
    if (!matched) {
      suggestions = await query(
        `SELECT id, name, sku, barcode, stock, min_stock, cost_price, price, image_url 
         FROM master_products 
         WHERE tenant_id = ? AND (sku LIKE ? OR barcode LIKE ? OR name LIKE ?) 
         ORDER BY name ASC LIMIT 10`,
        [tenantId, `%${code}%`, `%${code}%`, `%${code}%`]
      );
      if (suggestions.length === 1) {
        matched = suggestions[0];
      }
    }

    let recentMovements = [];
    let marketplaceListings = [];
    if (matched) {
      try {
        recentMovements = await query(
          `SELECT type, quantity, reference, balance_after, created_at 
          FROM stock_movements 
          WHERE tenant_id = ? AND master_product_id = ? 
          ORDER BY id DESC LIMIT 5`,
          [tenantId, matched.id]
        );
      } catch (_) {}

      try {
        marketplaceListings = await query(
          `SELECT mp.marketplace_sku, mp.name, mp.stock, s.store_name, c.name as channel_name 
           FROM marketplace_products mp
           JOIN stores s ON s.id = mp.store_id AND s.tenant_id = ?
           LEFT JOIN channels c ON c.id = s.channel_id
           WHERE mp.master_product_id = ? LIMIT 6`,
          [tenantId, matched.id]
        );
      } catch (_) {}
    }

    return res.json({
      success: true,
      matched,
      suggestions: matched ? [] : suggestions,
      recentMovements: recentMovements.map(m => ({
        type: m.type,
        quantity: m.quantity,
        reference: m.reference,
        balanceAfter: m.balance_after,
        dateFormatted: formatDateIndo(m.created_at)
      })),
      marketplaceListings
    });
  } catch (err) {
    console.error('[Node-API] Scan Kemasan search error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/v2/owner/scan-kemasan/adjust-stock', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { product_id, type, quantity, reference } = req.body;

    if (!product_id || !type || quantity === undefined) {
      return res.status(400).json({ success: false, message: 'Data penyesuaian stok belum lengkap.' });
    }

    const qty = parseInt(quantity, 10);
    if (isNaN(qty) || (type !== 'adj' && qty <= 0)) {
      return res.status(400).json({ success: false, message: 'Jumlah kuantitas tidak valid.' });
    }

    const [product] = await query(
      `SELECT id, sku, name, stock FROM master_products WHERE id = ? AND tenant_id = ? LIMIT 1`,
      [product_id, tenantId]
    );

    if (!product) {
      return res.status(404).json({ success: false, message: 'Produk tidak ditemukan.' });
    }

    let actualQty = 0;
    let newStock = product.stock;

    if (type === 'in') {
      actualQty = Math.abs(qty);
      newStock += actualQty;
      await query(`UPDATE master_products SET stock = stock + ?, updated_at = NOW() WHERE id = ?`, [actualQty, product.id]);
    } else if (type === 'out') {
      actualQty = -Math.abs(qty);
      newStock = Math.max(0, newStock - Math.abs(qty));
      await query(`UPDATE master_products SET stock = GREATEST(0, stock - ?), updated_at = NOW() WHERE id = ?`, [Math.abs(qty), product.id]);
    } else {
      actualQty = qty - product.stock;
      newStock = Math.max(0, qty);
      await query(`UPDATE master_products SET stock = ?, updated_at = NOW() WHERE id = ?`, [newStock, product.id]);
    }

    const refText = reference || `Scan Kemasan: Penyesuaian stok (${type.toUpperCase()})`;
    try {
      await query(
        `INSERT INTO stock_movements (tenant_id, master_product_id, user_id, type, quantity, reference, balance_after, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
        [tenantId, product.id, null, type, actualQty, refText, newStock]
      );
    } catch (_) {}

    try {
      await query(
        `UPDATE marketplace_products SET stock = ?, sync_stock = 1, updated_at = NOW() WHERE master_product_id = ?`,
        [newStock, product.id]
      );
    } catch (_) {}

    return res.json({
      success: true,
      message: `Stok produk ${product.sku} berhasil diperbarui menjadi ${newStock} pcs.`,
      newStock
    });
  } catch (err) {
    console.error('[Node-API] Adjust stock error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/v2/owner/scan-kemasan/request-production', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { product_id, quantity, notes } = req.body;

    if (!product_id || !quantity) {
      return res.status(400).json({ success: false, message: 'Produk dan kuantitas wajib diisi.' });
    }

    const qty = parseInt(quantity, 10);
    if (isNaN(qty) || qty <= 0) {
      return res.status(400).json({ success: false, message: 'Kuantitas harus lebih dari 0.' });
    }

    const [product] = await query(
      `SELECT id, name, sku FROM master_products WHERE id = ? AND tenant_id = ? LIMIT 1`,
      [product_id, tenantId]
    );

    if (!product) {
      return res.status(404).json({ success: false, message: 'Produk tidak ditemukan.' });
    }

    const [adminUser] = await query(`SELECT id FROM users WHERE tenant_id = ? LIMIT 1`, [tenantId]);
    const userId = adminUser?.id || 1;

    await query(
      `INSERT INTO production_orders (tenant_id, master_product_id, quantity, status, requested_by, created_at, updated_at)
       VALUES (?, ?, ?, 'pending', ?, NOW(), NOW())`,
      [tenantId, product.id, qty, userId]
    );

    return res.json({
      success: true,
      message: `Permintaan produksi ${qty} pcs untuk ${product.name} (${product.sku}) berhasil dibuat.`
    });
  } catch (err) {
    console.error('[Node-API] Request production error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 12. SCAN SPK PRODUKSI ──────────────────────────────────────────────────
app.get('/api/v2/owner/scan-spk/lookup', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    let code = (req.query.code || '').trim();
    code = code.replace(/\\"/g, '"').replace(/^["']|["']$/g, '');

    if (!code) {
      const activeSpks = await query(
        `SELECT s.id, s.no_spk, s.no_produksi, s.pemesan, s.instansi, s.tahap_saat_ini, s.is_urgent, s.deadline,
                COUNT(si.id) as total_items, COALESCE(SUM(si.quantity), 0) as total_qty
         FROM spks s
         LEFT JOIN spk_items si ON si.spk_id = s.id
         WHERE s.tenant_id = ? AND s.tahap_saat_ini != 'Selesai (Finished Good)'
         GROUP BY s.id
         ORDER BY s.is_urgent DESC, s.deadline ASC, s.id DESC LIMIT 15`,
        [tenantId]
      );

      const recentPickups = await query(
        `SELECT sip.id, sip.qty_diambil, sip.tanggal_ambil, sip.nama_pengambil, sip.catatan,
                si.nama_produk, si.ukuran, si.sku, s.no_spk
         FROM spk_item_pickups sip
         JOIN spk_items si ON si.id = sip.spk_item_id
         JOIN spks s ON s.id = si.spk_id
         WHERE s.tenant_id = ?
         ORDER BY sip.id DESC LIMIT 15`,
        [tenantId]
      );

      return res.json({
        success: true,
        activeSpks: activeSpks.map(s => ({
          ...s,
          deadlineFormatted: s.deadline ? formatDateIndo(s.deadline) : '-'
        })),
        recentPickups: recentPickups.map(p => ({
          ...p,
          dateFormatted: formatDateIndo(p.tanggal_ambil)
        })),
        matchedSpk: null
      });
    }

    let parsedItemId = null;
    let parsedSku = null;
    let parsedSpkId = null;
    if (code.startsWith('{') && code.endsWith('}')) {
      try {
        const j = JSON.parse(code);
        if (j.item_id) parsedItemId = parseInt(j.item_id, 10);
        if (j.spk_id) parsedSpkId = parseInt(j.spk_id, 10);
        if (j.sku) parsedSku = String(j.sku).trim();
      } catch (_) {}
    }

    if (!parsedItemId) {
      const mItem = code.match(/ITEM-(\d+)/i);
      if (mItem) parsedItemId = parseInt(mItem[1], 10);
    }
    if (!parsedSpkId) {
      const mSpk = code.match(/SPK-(\d+)/i);
      if (mSpk) parsedSpkId = parseInt(mSpk[1], 10);
    }

    let targetSpkId = parsedSpkId;
    let targetItemId = parsedItemId;

    if (targetItemId && !targetSpkId) {
      const [it] = await query(`SELECT spk_id FROM spk_items WHERE id = ? LIMIT 1`, [targetItemId]);
      if (it) targetSpkId = it.spk_id;
    }

    if (!targetSpkId) {
      const [byNo] = await query(
        `SELECT id FROM spks WHERE tenant_id = ? AND (no_spk = ? OR no_produksi = ? OR no_spk LIKE ?) LIMIT 1`,
        [tenantId, code, code, `%${code}%`]
      );
      if (byNo) targetSpkId = byNo.id;
    }

    if (!targetSpkId) {
      const [bySku] = await query(
        `SELECT s.id, si.id as item_id 
         FROM spk_items si
         JOIN spks s ON s.id = si.spk_id
         WHERE s.tenant_id = ? AND (si.sku = ? OR si.sku LIKE ?) 
         ORDER BY s.id DESC LIMIT 1`,
        [tenantId, code, `%${code}%`]
      );
      if (bySku) {
        targetSpkId = bySku.id;
        targetItemId = bySku.item_id;
      }
    }

    if (!targetSpkId) {
      return res.status(404).json({
        success: false,
        message: `SPK atau Item dengan kode "${code}" tidak ditemukan.`
      });
    }

    const [spk] = await query(
      `SELECT s.*, u.name as penginput_name 
       FROM spks s 
       LEFT JOIN users u ON u.id = s.penginput_id
       WHERE s.id = ? AND s.tenant_id = ? LIMIT 1`,
      [targetSpkId, tenantId]
    );

    if (!spk) {
      return res.status(404).json({ success: false, message: 'SPK tidak ditemukan atau beda tenant.' });
    }

    const items = await query(
      `SELECT si.*, 
              COALESCE(SUM(sip.qty_diambil), 0) as qty_diambil
       FROM spk_items si
       LEFT JOIN spk_item_pickups sip ON sip.spk_item_id = si.id
       WHERE si.spk_id = ?
       GROUP BY si.id
       ORDER BY si.id ASC`,
      [targetSpkId]
    );

    const formattedItems = items.map(it => {
      const targetQty = parseInt(it.quantity || 1, 10);
      const taken = parseInt(it.qty_diambil || 0, 10);
      const sisa = Math.max(0, targetQty - taken);
      return {
        id: it.id,
        spkId: it.spk_id,
        masterProductId: it.master_product_id,
        namaProduk: it.nama_produk,
        sku: it.sku || '-',
        ukuran: it.ukuran || 'All Size',
        quantity: targetQty,
        qtyDiambil: taken,
        sisaQty: sisa,
        isCompleted: sisa === 0,
        status: it.status || 'Belum Mulai',
        hpp: parseFloat(it.hpp || 0),
      };
    });

    const totalTarget = formattedItems.reduce((a, b) => a + b.quantity, 0);
    const totalDiambil = formattedItems.reduce((a, b) => a + b.qtyDiambil, 0);
    const totalSisa = Math.max(0, totalTarget - totalDiambil);
    const percentComplete = totalTarget > 0 ? Math.min(100, Math.round((totalDiambil / totalTarget) * 100)) : 0;

    const spkPickups = await query(
      `SELECT sip.id, sip.qty_diambil, sip.tanggal_ambil, sip.nama_pengambil, sip.catatan,
              si.nama_produk, si.ukuran, si.sku
       FROM spk_item_pickups sip
       JOIN spk_items si ON si.id = sip.spk_item_id
       WHERE si.spk_id = ?
       ORDER BY sip.id DESC LIMIT 20`,
      [targetSpkId]
    );

    return res.json({
      success: true,
      spk: {
        id: spk.id,
        noSpk: spk.no_spk,
        noProduksi: spk.no_produksi || '-',
        tanggal: spk.tanggal,
        tanggalFormatted: formatDateIndo(spk.tanggal),
        deadline: spk.deadline,
        deadlineFormatted: spk.deadline ? formatDateIndo(spk.deadline) : '-',
        pemesan: spk.pemesan || '-',
        noHpPemesan: spk.no_hp_pemesan || '-',
        instansi: spk.instansi || '-',
        tahapSaatIni: spk.tahap_saat_ini || 'Perencanaan',
        isUrgent: !!spk.is_urgent,
        imageUrl: spk.image_url || null,
        penginput: spk.penginput_name || '-',
      },
      items: formattedItems,
      matchedItemId: targetItemId,
      progress: {
        totalTarget,
        totalDiambil,
        totalSisa,
        percentComplete,
        allCompleted: totalSisa === 0,
      },
      recentPickups: spkPickups.map(p => ({
        ...p,
        dateFormatted: formatDateIndo(p.tanggal_ambil)
      }))
    });
  } catch (err) {
    console.error('[Node-API] Scan SPK lookup error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/v2/owner/scan-spk/pickup', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const { spk_id, item_id, qty, nama_pengambil, catatan } = req.body;

    if (!spk_id || !item_id) {
      return res.status(400).json({ success: false, message: 'SPK ID dan Item ID wajib diisi.' });
    }

    const qtyRequested = Math.max(1, parseInt(qty || 1, 10));
    const [spk] = await query(`SELECT * FROM spks WHERE id = ? AND tenant_id = ? LIMIT 1`, [spk_id, tenantId]);
    if (!spk) {
      return res.status(404).json({ success: false, message: 'SPK tidak ditemukan.' });
    }

    const [item] = await query(`SELECT * FROM spk_items WHERE id = ? AND spk_id = ? LIMIT 1`, [item_id, spk_id]);
    if (!item) {
      return res.status(404).json({ success: false, message: 'Item SPK tidak ditemukan.' });
    }

    const [takenRow] = await query(
      `SELECT COALESCE(SUM(qty_diambil), 0) as tot FROM spk_item_pickups WHERE spk_item_id = ?`,
      [item.id]
    );
    const curTaken = parseInt(takenRow?.tot || 0, 10);
    const targetQty = parseInt(item.quantity || 1, 10);
    const sisaKuota = Math.max(0, targetQty - curTaken);

    if (sisaKuota <= 0) {
      return res.status(422).json({
        success: false,
        message: `Kuota untuk item "${item.nama_produk}" (${item.ukuran || 'All Size'}) sudah lengkap (${targetQty}/${targetQty} pcs).`
      });
    }

    const qtyToTake = Math.min(qtyRequested, sisaKuota);
    const [adminUser] = await query(`SELECT id FROM users WHERE tenant_id = ? LIMIT 1`, [tenantId]);
    const pemberiId = adminUser?.id || 1;
    const penerima = (nama_pengambil || 'Petugas Gudang').trim();
    const noteText = (catatan || 'Scan Terima Hasil SPK Mobile').trim();

    await query(
      `INSERT INTO spk_item_pickups (spk_item_id, qty_diambil, tanggal_ambil, nama_pengambil, pemberi_id, catatan, created_at, updated_at)
       VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())`,
      [item.id, qtyToTake, penerima, pemberiId, noteText]
    );

    let masterProductId = item.master_product_id;
    if (!masterProductId && item.sku) {
      const [mp] = await query(`SELECT id FROM master_products WHERE tenant_id = ? AND sku = ? LIMIT 1`, [tenantId, item.sku]);
      if (mp) masterProductId = mp.id;
    }

    if (masterProductId) {
      try {
        await query(`UPDATE master_products SET stock = stock + ?, updated_at = NOW() WHERE id = ?`, [qtyToTake, masterProductId]);
        const [fresh] = await query(`SELECT stock FROM master_products WHERE id = ?`, [masterProductId]);
        const newStock = fresh ? fresh.stock : 0;

        const refText = `Scan Terima SPK #${spk.no_spk} (${item.nama_produk} - ${item.ukuran || 'All Size'})`;
        await query(
          `INSERT INTO stock_movements (tenant_id, master_product_id, user_id, type, quantity, reference, balance_after, created_at, updated_at)
           VALUES (?, ?, ?, 'in', ?, ?, ?, NOW(), NOW())`,
          [tenantId, masterProductId, pemberiId, qtyToTake, refText, newStock]
        );

        await query(
          `UPDATE marketplace_products SET stock = ?, sync_stock = 1, updated_at = NOW() WHERE master_product_id = ?`,
          [newStock, masterProductId]
        );
      } catch (e) {
        console.warn('[Node-API] Restock from SPK warning:', e.message);
      }
    }

    const allItems = await query(
      `SELECT si.quantity, COALESCE(SUM(sip.qty_diambil), 0) as qty_diambil
       FROM spk_items si
       LEFT JOIN spk_item_pickups sip ON sip.spk_item_id = si.id
       WHERE si.spk_id = ?
       GROUP BY si.id`,
      [spk_id]
    );

    const allComplete = allItems.every(it => parseInt(it.qty_diambil, 10) >= parseInt(it.quantity, 10));
    if (allComplete && spk.tahap_saat_ini !== 'Selesai (Finished Good)') {
      await query(`UPDATE spks SET tahap_saat_ini = 'Selesai (Finished Good)', updated_at = NOW() WHERE id = ?`, [spk_id]);
    }

    return res.json({
      success: true,
      message: `Berhasil menerima ${qtyToTake} pcs "${item.nama_produk}" (${item.ukuran || 'All Size'}). Stok barang jadi otomatis bertambah.`,
      qtyTaken: qtyToTake,
      allComplete
    });
  } catch (err) {
    console.error('[Node-API] SPK pickup error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Auto-process direct barcode / QR scan karung SPK
app.post('/api/v2/owner/scan-spk/process-scan', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    let rawCode = String(req.body.code || '').trim();
    rawCode = rawCode.replace(/\\"/g, '"').replace(/^["']|["']$/g, '');

    if (!rawCode) {
      return res.status(400).json({ success: false, message: 'Barcode / QR code tidak boleh kosong.' });
    }

    const qtyRequested = Math.max(1, parseInt(req.body.qty || 1, 10));
    const namaPengambil = (req.body.nama_pengambil || 'Petugas Gudang').trim();
    const catatan = (req.body.catatan || 'Scan Barcode / QR Karung SPK').trim();

    let matchedItem = null;
    let matchedSpk = null;

    // 1. Direct ITEM-{id}
    const mItem = rawCode.match(/ITEM-(\d+)/i);
    if (mItem) {
      const itemId = parseInt(mItem[1], 10);
      const [it] = await query(
        `SELECT si.*, s.no_spk, s.tenant_id, s.tahap_saat_ini
         FROM spk_items si
         JOIN spks s ON s.id = si.spk_id
         WHERE si.id = ? AND s.tenant_id = ? LIMIT 1`,
        [itemId, tenantId]
      );
      if (it) {
        matchedItem = it;
        matchedSpk = { id: it.spk_id, no_spk: it.no_spk, tenant_id: it.tenant_id, tahap_saat_ini: it.tahap_saat_ini };
      }
    }

    // 2. JSON embedded
    if (!matchedItem && rawCode.includes('{') && rawCode.includes('}')) {
      const jsonMatch = rawCode.match(/\{.*?\}/s);
      if (jsonMatch) {
        try {
          const j = JSON.parse(jsonMatch[0]);
          if (j.item_id) {
            const [it] = await query(
              `SELECT si.*, s.no_spk, s.tenant_id, s.tahap_saat_ini
               FROM spk_items si
               JOIN spks s ON s.id = si.spk_id
               WHERE si.id = ? AND s.tenant_id = ? LIMIT 1`,
              [parseInt(j.item_id, 10), tenantId]
            );
            if (it) {
              matchedItem = it;
              matchedSpk = { id: it.spk_id, no_spk: it.no_spk, tenant_id: it.tenant_id, tahap_saat_ini: it.tahap_saat_ini };
            }
          }
          if (!matchedItem && (j.spk_id || j.no_spk || j.no_produksi)) {
            let spkQuery = 'SELECT * FROM spks WHERE tenant_id = ? AND ';
            const spkParams = [tenantId];
            if (j.spk_id) { spkQuery += 'id = ?'; spkParams.push(parseInt(j.spk_id, 10)); }
            else if (j.no_spk) { spkQuery += 'no_spk = ?'; spkParams.push(String(j.no_spk).trim()); }
            else { spkQuery += 'no_produksi = ?'; spkParams.push(String(j.no_produksi).trim()); }

            const [foundSpk] = await query(`${spkQuery} LIMIT 1`, spkParams);
            if (foundSpk) {
              matchedSpk = foundSpk;
              const targetSku = j.sku ? String(j.sku).trim() : null;
              if (targetSku) {
                const [it] = await query(
                  `SELECT * FROM spk_items WHERE spk_id = ? AND (sku = ? OR sku LIKE ?) LIMIT 1`,
                  [foundSpk.id, targetSku, `%${targetSku}%`]
                );
                if (it) matchedItem = it;
              }
              if (!matchedItem) {
                const spkItems = await query(`SELECT * FROM spk_items WHERE spk_id = ? ORDER BY id ASC`, [foundSpk.id]);
                for (const it of spkItems) {
                  const [pickRow] = await query(`SELECT COALESCE(SUM(qty_diambil), 0) as tot FROM spk_item_pickups WHERE spk_item_id = ?`, [it.id]);
                  const sisa = Math.max(0, parseInt(it.quantity || 1, 10) - parseInt(pickRow?.tot || 0, 10));
                  if (sisa > 0) {
                    matchedItem = it;
                    break;
                  }
                }
                if (!matchedItem && spkItems.length > 0) matchedItem = spkItems[0];
              }
            }
          }
        } catch (_) {}
      }
    }

    // 3. Direct No SPK / No Produksi
    if (!matchedItem) {
      let spkNoSearch = rawCode.replace(/^[#]/, '').trim();
      const mSpk = rawCode.match(/(SPK-[A-Z0-9\-]+|PROD-[A-Z0-9\-]+)/i);
      if (mSpk) spkNoSearch = mSpk[1].trim();

      const [foundSpk] = await query(
        `SELECT * FROM spks 
         WHERE tenant_id = ? AND (no_spk = ? OR no_produksi = ? OR no_spk LIKE ?) LIMIT 1`,
        [tenantId, spkNoSearch, spkNoSearch, `%${spkNoSearch}%`]
      );

      if (foundSpk) {
        matchedSpk = foundSpk;
        const spkItems = await query(`SELECT * FROM spk_items WHERE spk_id = ? ORDER BY id ASC`, [foundSpk.id]);
        for (const it of spkItems) {
          const [pickRow] = await query(`SELECT COALESCE(SUM(qty_diambil), 0) as tot FROM spk_item_pickups WHERE spk_item_id = ?`, [it.id]);
          const sisa = Math.max(0, parseInt(it.quantity || 1, 10) - parseInt(pickRow?.tot || 0, 10));
          if (sisa > 0) {
            matchedItem = it;
            break;
          }
        }
        if (!matchedItem && spkItems.length > 0) matchedItem = spkItems[0];
      }
    }

    // 4. SKU match across active SPKs
    if (!matchedItem) {
      const [it] = await query(
        `SELECT si.*, s.no_spk, s.tenant_id, s.tahap_saat_ini
         FROM spk_items si
         JOIN spks s ON s.id = si.spk_id
         WHERE s.tenant_id = ? AND s.tahap_saat_ini != 'Selesai (Finished Good)'
           AND (si.sku = ? OR si.sku LIKE ?)
         ORDER BY s.id DESC LIMIT 1`,
        [tenantId, rawCode, `%${rawCode}%`]
      );
      if (it) {
        matchedItem = it;
        matchedSpk = { id: it.spk_id, no_spk: it.no_spk, tenant_id: it.tenant_id, tahap_saat_ini: it.tahap_saat_ini };
      }
    }

    if (!matchedItem || !matchedSpk) {
      return res.status(404).json({
        success: false,
        error_type: 'not_found',
        message: `Barcode / QR '${rawCode}' tidak ditemukan pada daftar SPK aktif.`
      });
    }

    // Cek sisa kuota
    const [pickRow] = await query(
      `SELECT COALESCE(SUM(qty_diambil), 0) as tot FROM spk_item_pickups WHERE spk_item_id = ?`,
      [matchedItem.id]
    );
    const curTaken = parseInt(pickRow?.tot || 0, 10);
    const targetQty = parseInt(matchedItem.quantity || 1, 10);
    const sisaKuota = Math.max(0, targetQty - curTaken);

    if (sisaKuota <= 0) {
      return res.status(422).json({
        success: false,
        error_type: 'quota_exceeded',
        message: `SPK #${matchedSpk.no_spk}: Kuota item "${matchedItem.nama_produk}" (${matchedItem.ukuran || 'All Size'}) sudah LENGKAP (${targetQty}/${targetQty} pcs).`,
        spkNo: matchedSpk.no_spk,
        item: matchedItem
      });
    }

    const qtyToTake = Math.min(qtyRequested, sisaKuota);
    const [adminUser] = await query(`SELECT id FROM users WHERE tenant_id = ? LIMIT 1`, [tenantId]);
    const pemberiId = adminUser?.id || 1;

    const insRes = await query(
      `INSERT INTO spk_item_pickups (spk_item_id, qty_diambil, tanggal_ambil, nama_pengambil, pemberi_id, catatan, created_at, updated_at)
       VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())`,
      [matchedItem.id, qtyToTake, namaPengambil, pemberiId, catatan]
    );

    let masterProductId = matchedItem.master_product_id;
    if (!masterProductId && matchedItem.sku) {
      const [mp] = await query(`SELECT id FROM master_products WHERE tenant_id = ? AND sku = ? LIMIT 1`, [tenantId, matchedItem.sku]);
      if (mp) masterProductId = mp.id;
    }

    let balanceAfter = 0;
    if (masterProductId) {
      try {
        await query(`UPDATE master_products SET stock = stock + ?, updated_at = NOW() WHERE id = ?`, [qtyToTake, masterProductId]);
        const [fresh] = await query(`SELECT stock FROM master_products WHERE id = ?`, [masterProductId]);
        balanceAfter = fresh ? fresh.stock : 0;

        const refText = `Scan SPK: #${matchedSpk.no_spk} (${matchedItem.nama_produk} - ${matchedItem.ukuran || 'All Size'})`;
        await query(
          `INSERT INTO stock_movements (tenant_id, master_product_id, user_id, type, quantity, reference, balance_after, created_at, updated_at)
           VALUES (?, ?, ?, 'in', ?, ?, ?, NOW(), NOW())`,
          [tenantId, masterProductId, pemberiId, qtyToTake, refText, balanceAfter]
        );

        await query(
          `UPDATE marketplace_products SET stock = ?, sync_stock = 1, updated_at = NOW() WHERE master_product_id = ?`,
          [balanceAfter, masterProductId]
        );
      } catch (e) {
        console.warn('[Node-API] Restock from scan SPK warning:', e.message);
      }
    }

    const allItems = await query(
      `SELECT si.quantity, COALESCE(SUM(sip.qty_diambil), 0) as qty_diambil
       FROM spk_items si
       LEFT JOIN spk_item_pickups sip ON sip.spk_item_id = si.id
       WHERE si.spk_id = ?
       GROUP BY si.id`,
      [matchedSpk.id]
    );

    const allComplete = allItems.every(it => parseInt(it.qty_diambil, 10) >= parseInt(it.quantity, 10));
    if (allComplete) {
      await query(`UPDATE spks SET tahap_saat_ini = 'Selesai (Finished Good)', updated_at = NOW() WHERE id = ?`, [matchedSpk.id]);
    }

    return res.json({
      success: true,
      message: `Berhasil menerima ${qtyToTake} pcs "${matchedItem.nama_produk}" (${matchedItem.ukuran || 'All Size'}).`,
      data: {
        pickupId: insRes.insertId,
        spkId: matchedSpk.id,
        noSpk: matchedSpk.no_spk,
        itemId: matchedItem.id,
        namaProduk: matchedItem.nama_produk,
        sku: matchedItem.sku || '-',
        ukuran: matchedItem.ukuran || 'All Size',
        qtyTaken: qtyToTake,
        sisaQty: Math.max(0, sisaKuota - qtyToTake),
        totalTarget: targetQty,
        totalTaken: curTaken + qtyToTake,
        allComplete,
        timeFormatted: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
      }
    });
  } catch (err) {
    console.error('[Node-API] Process scan SPK error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Histori Scan SPK
app.get('/api/v2/owner/scan-spk/history', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const search = (req.query.search || '').trim();
    const date = req.query.date;

    let whereSql = `WHERE s.tenant_id = ?`;
    const params = [tenantId];

    if (search) {
      whereSql += ` AND (s.no_spk LIKE ? OR si.nama_produk LIKE ? OR si.sku LIKE ? OR sip.nama_pengambil LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s, s);
    }

    if (date) {
      whereSql += ` AND DATE(sip.tanggal_ambil) = ?`;
      params.push(date);
    }

    const rows = await query(
      `SELECT sip.id, sip.qty_diambil, sip.tanggal_ambil, sip.nama_pengambil, sip.catatan,
              si.nama_produk, si.ukuran, si.sku, si.quantity as target_qty,
              s.id as spk_id, s.no_spk, s.pemesan, s.tahap_saat_ini
       FROM spk_item_pickups sip
       JOIN spk_items si ON si.id = sip.spk_item_id
       JOIN spks s ON s.id = si.spk_id
       ${whereSql}
       ORDER BY sip.id DESC
       LIMIT 100`,
      params
    );

    const [todayKpi] = await query(
      `SELECT COUNT(*) as total_scans,
              COALESCE(SUM(sip.qty_diambil), 0) as total_pcs,
              COUNT(DISTINCT s.id) as total_spks
       FROM spk_item_pickups sip
       JOIN spk_items si ON si.id = sip.spk_item_id
       JOIN spks s ON s.id = si.spk_id
       WHERE s.tenant_id = ? AND DATE(sip.tanggal_ambil) = CURDATE()`,
      [tenantId]
    );

    return res.json({
      success: true,
      kpi: {
        todayScans: parseInt(todayKpi?.total_scans || 0, 10),
        todayPcs: parseInt(todayKpi?.total_pcs || 0, 10),
        todaySpks: parseInt(todayKpi?.total_spks || 0, 10),
      },
      data: rows.map(r => ({
        id: r.id,
        spkId: r.spk_id,
        noSpk: r.no_spk,
        namaProduk: r.nama_produk,
        sku: r.sku || '-',
        ukuran: r.ukuran || 'All Size',
        qtyDiambil: parseInt(r.qty_diambil, 10),
        targetQty: parseInt(r.target_qty, 10),
        namaPengambil: r.nama_pengambil,
        catatan: r.catatan || '',
        tanggalAmbil: r.tanggal_ambil,
        dateFormatted: formatDateIndo(r.tanggal_ambil),
        timeFormatted: new Date(r.tanggal_ambil).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
      }))
    });
  } catch (err) {
    console.error('[Node-API] Scan SPK history error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── 14. TITIPAN BARANG (SUPPLIER CONSIGNMENTS) API ──────────────────────────

// Meta: Master Products & Suppliers untuk Form Input
app.get(['/api/v2/owner/supplier-consignments/meta/resources', '/api/v2/supplier-consignments/meta/resources'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const search = (req.query.search || '').trim();

    let productWhere = 'WHERE tenant_id = ? AND is_active = 1';
    const productParams = [tenantId];
    if (search) {
      productWhere += ' AND (name LIKE ? OR sku LIKE ?)';
      productParams.push(`%${search}%`, `%${search}%`);
    }

    const products = await query(
      `SELECT id, sku, name, stock, cost_price, price, unit
       FROM master_products
       ${productWhere}
       ORDER BY name ASC
       LIMIT 200`,
      productParams
    );

    const suppliers = await query(
      `SELECT id, name, phone, contact_person, address
       FROM suppliers
       WHERE tenant_id = ? AND (is_active = 1 OR is_active IS NULL)
       ORDER BY name ASC`,
      [tenantId]
    );

    // Generate Reference Number KNS-YYYYMMDD-XXXX
    const todayStr = formatDate(new Date()).replace(/-/g, '');
    const prefix = `KNS-${todayStr}-`;
    const [lastCons] = await query(
      `SELECT reference_number FROM supplier_consignments
       WHERE tenant_id = ? AND reference_number LIKE ?
       ORDER BY id DESC LIMIT 1`,
      [tenantId, `${prefix}%`]
    );

    let nextNum = '0001';
    if (lastCons && lastCons.reference_number) {
      const parts = lastCons.reference_number.split('-');
      const lastSequence = parseInt(parts[parts.length - 1], 10);
      if (!isNaN(lastSequence)) {
        nextNum = String(lastSequence + 1).padStart(4, '0');
      }
    }
    const autoRefNumber = `${prefix}${nextNum}`;

    return res.json({
      success: true,
      data: {
        products: products.map(p => ({
          id: p.id,
          sku: p.sku || 'BRG',
          name: p.name,
          stock: parseInt(p.stock || 0, 10),
          costPrice: parseFloat(p.cost_price || 0),
          price: parseFloat(p.price || 0),
          unit: p.unit || 'PCS'
        })),
        suppliers: suppliers.map(s => ({
          id: s.id,
          name: s.name,
          phone: s.phone || '',
          contactPerson: s.contact_person || '',
          address: s.address || ''
        })),
        autoRefNumber
      }
    });
  } catch (err) {
    console.error('[Node-API] Consignment resources error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── KARTU STOK GUDANG JADI (MONITORING STOK BARANG JADI) ───────────────────
app.get('/api/v2/owner/stock-card/finished-goods', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const search = (req.query.search || '').trim();
    const status = (req.query.status || 'all').trim(); // 'all', 'low', 'empty', 'ready'
    const limit = Math.min(parseInt(req.query.limit, 10) || 50, 200);
    const page = Math.max(parseInt(req.query.page, 10) || 1, 1);
    const offset = (page - 1) * limit;

    let whereSql = `WHERE mp.tenant_id = ? AND (mp.is_active = 1 OR mp.is_active IS NULL)`;
    const params = [tenantId];

    if (search) {
      whereSql += ` AND (mp.name LIKE ? OR mp.sku LIKE ? OR mp.barcode LIKE ? OR mp.sku_induk LIKE ?)`;
      params.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
    }

    if (status === 'low') {
      whereSql += ` AND mp.stock > 0 AND mp.stock < 10`;
    } else if (status === 'empty') {
      whereSql += ` AND (mp.stock <= 0 OR mp.stock IS NULL)`;
    } else if (status === 'ready') {
      whereSql += ` AND mp.stock >= 10`;
    }

    // 1. Hitung Ringkasan Metrik Monitoring Stok Gudang Jadi
    const [summaryRow] = await query(
      `SELECT 
         COUNT(*) as total_products,
         COALESCE(SUM(stock), 0) as total_stock,
         COALESCE(SUM(CASE WHEN stock > 0 AND stock < 10 THEN 1 ELSE 0 END), 0) as low_stock_count,
         COALESCE(SUM(CASE WHEN stock <= 0 OR stock IS NULL THEN 1 ELSE 0 END), 0) as empty_stock_count,
         COALESCE(SUM(CASE WHEN stock >= 10 THEN 1 ELSE 0 END), 0) as ready_stock_count,
         COALESCE(SUM(stock * cost_price), 0) as total_asset_value
       FROM master_products mp
       WHERE mp.tenant_id = ? AND (mp.is_active = 1 OR mp.is_active IS NULL)`,
      [tenantId]
    );

    // 2. Ambil Daftar Produk Sesuai Filter
    const products = await query(
      `SELECT mp.id, mp.name, mp.sku, mp.barcode, mp.stock, mp.min_stock, mp.safety_stock,
              mp.unit, mp.cost_price, mp.price, mp.image_url, mp.sub_kategori, mp.ukuran, mp.warna,
              mp.updated_at
       FROM master_products mp
       ${whereSql}
       ORDER BY 
         CASE WHEN mp.stock <= 0 THEN 1 WHEN mp.stock < 10 THEN 2 ELSE 3 END ASC,
         mp.name ASC
       LIMIT ? OFFSET ?`,
      [...params, limit, offset]
    );

    const items = products.map(p => {
      const stock = parseInt(p.stock || 0, 10);
      const minStock = parseInt(p.min_stock || 10, 10);
      const costPrice = parseFloat(p.cost_price || 0);
      const price = parseFloat(p.price || 0);
      let stockStatus = 'ready';
      if (stock <= 0) stockStatus = 'empty';
      else if (stock < 10) stockStatus = 'low';

      return {
        id: p.id,
        name: p.name || 'Produk Tanpa Nama',
        sku: p.sku || '-',
        barcode: p.barcode || null,
        stock,
        minStock,
        unit: p.unit || 'PCS',
        costPrice,
        price,
        assetValue: stock * costPrice,
        imageUrl: p.image_url || null,
        category: p.sub_kategori || null,
        size: p.ukuran || null,
        color: p.warna || null,
        status: stockStatus,
        updatedAt: p.updated_at
      };
    });

    return res.json({
      success: true,
      summary: {
        totalProducts: parseInt(summaryRow?.total_products || 0, 10),
        totalStock: parseInt(summaryRow?.total_stock || 0, 10),
        lowStockCount: parseInt(summaryRow?.low_stock_count || 0, 10),
        emptyStockCount: parseInt(summaryRow?.empty_stock_count || 0, 10),
        readyStockCount: parseInt(summaryRow?.ready_stock_count || 0, 10),
        totalAssetValue: parseFloat(summaryRow?.total_asset_value || 0)
      },
      page,
      limit,
      data: items
    });

  } catch (err) {
    console.error('[Node-API] Stock card finished goods error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ─── DETAIL RIWAYAT MUTASI KARTU STOK PRODUK GUDANG JADI ───────────────────
app.get('/api/v2/owner/stock-card/finished-goods/:productId/movements', async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const productId = parseInt(req.params.productId, 10);

    const [product] = await query(
      `SELECT id, name, sku, barcode, stock, unit, cost_price, price, image_url 
       FROM master_products 
       WHERE id = ? AND tenant_id = ? LIMIT 1`,
      [productId, tenantId]
    );

    if (!product) {
      return res.status(404).json({ success: false, message: 'Produk tidak ditemukan.' });
    }

    const movements = await query(
      `SELECT sm.id, sm.type, sm.quantity, sm.reference, sm.balance_after, sm.created_at,
              u.name as user_name
       FROM stock_movements sm
       LEFT JOIN users u ON u.id = sm.user_id
       WHERE sm.master_product_id = ? AND sm.tenant_id = ?
       ORDER BY sm.created_at DESC, sm.id DESC
       LIMIT 100`,
      [productId, tenantId]
    );

    return res.json({
      success: true,
      product: {
        id: product.id,
        name: product.name,
        sku: product.sku,
        barcode: product.barcode,
        stock: parseInt(product.stock || 0, 10),
        unit: product.unit || 'PCS',
        costPrice: parseFloat(product.cost_price || 0),
        price: parseFloat(product.price || 0),
        imageUrl: product.image_url
      },
      movements: movements.map(m => ({
        id: m.id,
        type: m.type, // 'in', 'out', 'adj'
        quantity: parseInt(m.quantity || 0, 10),
        reference: m.reference || '-',
        balanceAfter: parseInt(m.balance_after || 0, 10),
        userName: m.user_name || 'Sistem / Admin',
        createdAt: m.created_at
      }))
    });

  } catch (err) {
    console.error('[Node-API] Stock movement ledger error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Rekapitulasi Kartu Stok Konsinyasi (Stock Card)
app.get(['/api/v2/owner/supplier-consignments/reports/stock-card', '/api/v2/supplier-consignments/reports/stock-card'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const rawSupId = parseInt(req.query.supplier_id, 10);
    const supplierId = (!isNaN(rawSupId) && rawSupId > 0) ? rawSupId : null;
    const search = (req.query.search || '').trim();

    let whereSql = `WHERE sc.tenant_id = ?`;
    const params = [tenantId];
    if (supplierId) {
      whereSql += ` AND sc.supplier_id = ?`;
      params.push(supplierId);
    }
    if (search) {
      whereSql += ` AND (mp.name LIKE ? OR mp.sku LIKE ? OR s.name LIKE ?)`;
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    const rows = await query(
      `SELECT mp.id as master_product_id, mp.sku, mp.name as product_name, mp.unit,
              s.id as supplier_id, s.name as supplier_name,
              SUM(sci.qty_received) as total_received,
              SUM(sci.qty_sold) as total_sold,
              SUM(sci.qty_received - sci.qty_sold) as sisa_stok,
              AVG(sci.unit_cost_price) as avg_cost_price,
              SUM((sci.qty_received - sci.qty_sold) * sci.unit_cost_price) as total_value_remaining,
              SUM(sci.qty_sold * sci.unit_cost_price) as total_value_sold
       FROM supplier_consignment_items sci
       JOIN supplier_consignments sc ON sc.id = sci.supplier_consignment_id
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       LEFT JOIN master_products mp ON mp.id = sci.master_product_id
       ${whereSql}
       GROUP BY mp.id, s.id
       ORDER BY mp.name ASC`,
      params
    );

    let totalRemainingQty = 0;
    let totalSoldQty = 0;
    let totalRemainingValue = 0;
    let totalSoldValue = 0;

    const items = rows.map(r => {
      const remaining = Math.max(0, parseInt(r.sisa_stok || 0, 10));
      const sold = parseInt(r.total_sold || 0, 10);
      const valRemaining = parseFloat(r.total_value_remaining || 0);
      const valSold = parseFloat(r.total_value_sold || 0);
      const costPrice = parseFloat(r.avg_cost_price || 0);

      totalRemainingQty += remaining;
      totalSoldQty += sold;
      totalRemainingValue += valRemaining;
      totalSoldValue += valSold;

      return {
        masterProductId: r.master_product_id,
        sku: r.sku || 'BRG',
        productName: r.product_name || 'Produk',
        unit: r.unit || 'PCS',
        supplierId: r.supplier_id,
        supplierName: r.supplier_name || 'Supplier',
        totalReceived: parseInt(r.total_received || 0, 10),
        totalSold: sold,
        sisaStok: remaining,
        costPrice: costPrice,
        avgCostPrice: costPrice,
        totalValueRemaining: valRemaining,
        totalValueSold: valSold
      };
    });

    return res.json({
      success: true,
      summary: {
        totalProducts: items.length,
        totalRemainingQty,
        totalSoldQty,
        totalRemainingValue,
        totalSoldValue
      },
      data: items
    });
  } catch (err) {
    console.error('[Node-API] Stock card report error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// 15. RIWAYAT SETORAN PEMBAYARAN SUPPLIER (SETTLEMENTS)
app.get(['/api/v2/owner/supplier-consignments/settlements', '/api/v2/supplier-consignments/settlements'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const rawSupId = parseInt(req.query.supplier_id, 10);
    const supplierId = (!isNaN(rawSupId) && rawSupId > 0) ? rawSupId : null;
    const search = (req.query.search || '').trim();
    const dateFrom = req.query.date_from;
    const dateTo = req.query.date_to;
    const paymentMethod = (req.query.payment_method || '').trim();

    let whereSql = `WHERE scs.tenant_id = ?`;
    const params = [tenantId];

    if (supplierId) {
      whereSql += ` AND scs.supplier_id = ?`;
      params.push(supplierId);
    }
    if (search) {
      whereSql += ` AND (scs.settlement_number LIKE ? OR scs.reference_number LIKE ? OR scs.notes LIKE ? OR s.name LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s, s);
    }
    if (dateFrom) {
      whereSql += ` AND DATE(scs.settlement_date) >= ?`;
      params.push(dateFrom);
    }
    if (dateTo) {
      whereSql += ` AND DATE(scs.settlement_date) <= ?`;
      params.push(dateTo);
    }
    if (paymentMethod) {
      whereSql += ` AND scs.payment_method = ?`;
      params.push(paymentMethod);
    }

    const settlements = await query(
      `SELECT scs.*, s.name as supplier_name, s.phone as supplier_phone,
              ba.account_name, ba.account_number, ba.bank_name,
              u.name as creator_name
       FROM supplier_consignment_settlements scs
       LEFT JOIN suppliers s ON s.id = scs.supplier_id
       LEFT JOIN bank_accounts ba ON ba.id = scs.bank_account_id
       LEFT JOIN users u ON u.id = scs.created_by
       ${whereSql}
       ORDER BY scs.settlement_date DESC, scs.id DESC
       LIMIT 100`,
      params
    );

    const settlementIds = settlements.map(s => s.id);
    let itemsBySettlement = {};
    if (settlementIds.length > 0) {
      const allItems = await query(
        `SELECT scsi.*, mp.sku, mp.name as product_name
         FROM supplier_consignment_settlement_items scsi
         LEFT JOIN master_products mp ON mp.id = scsi.master_product_id
         WHERE scsi.settlement_id IN (?)`,
        [settlementIds]
      );
      for (const item of allItems) {
        if (!itemsBySettlement[item.settlement_id]) itemsBySettlement[item.settlement_id] = [];
        itemsBySettlement[item.settlement_id].push({
          id: item.id,
          masterProductId: item.master_product_id,
          productName: item.product_name || 'Produk',
          sku: item.sku || 'BRG',
          qtySettled: parseInt(item.qty_settled || 0, 10),
          unitCostPrice: parseFloat(item.unit_cost_price || 0),
          subtotal: parseFloat(item.subtotal || 0)
        });
      }
    }

    // Aggregate KPI
    const [agg] = await query(
      `SELECT COUNT(DISTINCT scs.id) as total_transaksi,
              COALESCE(SUM(scs.total_qty_settled), 0) as total_qty,
              COALESCE(SUM(scs.total_amount_paid), 0) as total_amount
       FROM supplier_consignment_settlements scs
       LEFT JOIN suppliers s ON s.id = scs.supplier_id
       ${whereSql}`,
      params
    );

    return res.json({
      success: true,
      kpi: {
        totalTransaksi: parseInt(agg?.total_transaksi || 0, 10),
        totalQtySettled: parseInt(agg?.total_qty || 0, 10),
        totalAmountPaid: parseFloat(agg?.total_amount || 0)
      },
      data: settlements.map(s => ({
        id: s.id,
        settlementNumber: s.settlement_number,
        settlementDate: extractDateStr(s.settlement_date),
        settlementDateFormatted: formatIndoDate(s.settlement_date),
        supplierId: s.supplier_id,
        supplierName: s.supplier_name || 'Supplier',
        supplierPhone: s.supplier_phone || '',
        totalQtySettled: parseInt(s.total_qty_settled || 0, 10),
        totalAmountPaid: parseFloat(s.total_amount_paid || 0),
        paymentMethod: s.payment_method || 'transfer',
        bankAccount: s.bank_name ? `${s.bank_name} - ${s.account_number} (${s.account_name})` : null,
        referenceNumber: s.reference_number || '-',
        status: s.status || 'approved',
        notes: s.notes || '',
        creatorName: s.creator_name || 'Admin',
        items: itemsBySettlement[s.id] || []
      }))
    });
  } catch (err) {
    console.error('[Node-API] Supplier settlements list error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// 16. RIWAYAT PENJUALAN KONSINYASI (SALES HISTORY / OUTFLOW)
app.get(['/api/v2/owner/supplier-consignments/sales-history', '/api/v2/supplier-consignments/sales-history'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const rawSupId = parseInt(req.query.supplier_id, 10);
    const supplierId = (!isNaN(rawSupId) && rawSupId > 0) ? rawSupId : null;
    const search = (req.query.search || '').trim();
    const dateFrom = req.query.date_from;
    const dateTo = req.query.date_to;

    let whereSql = `WHERE scd.tenant_id = ?`;
    const params = [tenantId];

    if (supplierId) {
      whereSql += ` AND sc.supplier_id = ?`;
      params.push(supplierId);
    }
    if (search) {
      whereSql += ` AND (o.invoice_number LIKE ? OR o.order_marketplace_id LIKE ? OR mp.name LIKE ? OR mp.sku LIKE ? OR s.name LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s, s, s);
    }
    if (dateFrom) {
      whereSql += ` AND DATE(scd.created_at) >= ?`;
      params.push(dateFrom);
    }
    if (dateTo) {
      whereSql += ` AND DATE(scd.created_at) <= ?`;
      params.push(dateTo);
    }

    const rows = await query(
      `SELECT scd.id, scd.quantity as qty, scd.created_at,
              o.id as order_id, o.invoice_number, o.order_marketplace_id, o.order_status,
              sci.unit_cost_price, sci.unit_selling_price,
              mp.id as master_product_id, mp.name as product_name, mp.sku, mp.unit,
              sc.reference_number as consignment_ref,
              s.id as supplier_id, s.name as supplier_name
       FROM supplier_consignment_deductions scd
       JOIN supplier_consignment_items sci ON sci.id = scd.supplier_consignment_item_id
       JOIN supplier_consignments sc ON sc.id = sci.supplier_consignment_id
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       LEFT JOIN master_products mp ON mp.id = sci.master_product_id
       LEFT JOIN orders o ON o.id = scd.order_id
       ${whereSql}
       ORDER BY scd.created_at DESC
       LIMIT 100`,
      params
    );

    // KPI Sales
    const [salesKpi] = await query(
      `SELECT COUNT(DISTINCT scd.id) as total_trx,
              COALESCE(SUM(scd.quantity), 0) as total_qty,
              COALESCE(SUM(scd.quantity * sci.unit_cost_price), 0) as total_cost_value
       FROM supplier_consignment_deductions scd
       JOIN supplier_consignment_items sci ON sci.id = scd.supplier_consignment_item_id
       JOIN supplier_consignments sc ON sc.id = sci.supplier_consignment_id
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       LEFT JOIN master_products mp ON mp.id = sci.master_product_id
       LEFT JOIN orders o ON o.id = scd.order_id
       ${whereSql}`,
      params
    );

    return res.json({
      success: true,
      kpi: {
        totalTransaksi: parseInt(salesKpi?.total_trx || 0, 10),
        totalQtySold: parseInt(salesKpi?.total_qty || 0, 10),
        totalCostValue: parseFloat(salesKpi?.total_cost_value || 0)
      },
      data: rows.map(r => ({
        id: r.id,
        orderId: r.order_id,
        invoiceNumber: r.invoice_number || r.order_marketplace_id || '-',
        orderStatus: r.order_status || 'completed',
        createdAt: r.created_at,
        dateFormatted: formatIndoDate(r.created_at),
        productName: r.product_name || 'Produk',
        sku: r.sku || 'BRG',
        unit: r.unit || 'PCS',
        qty: parseInt(r.qty || 1, 10),
        unitCostPrice: parseFloat(r.unit_cost_price || 0),
        subtotalCost: parseInt(r.qty || 1, 10) * parseFloat(r.unit_cost_price || 0),
        consignmentRef: r.consignment_ref,
        supplierName: r.supplier_name
      }))
    });
  } catch (err) {
    console.error('[Node-API] Supplier sales history error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// List Transaksi Penerimaan Konsinyasi
app.get(['/api/v2/owner/supplier-consignments', '/api/v2/supplier-consignments'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const search = (req.query.search || '').trim();
    const rawSupId = parseInt(req.query.supplier_id, 10);
    const supplierId = (!isNaN(rawSupId) && rawSupId > 0) ? rawSupId : null;
    const status = (req.query.status || '').trim();
    const dateFrom = req.query.date_from;
    const dateTo = req.query.date_to;

    let whereSql = `WHERE sc.tenant_id = ?`;
    const params = [tenantId];

    if (supplierId) {
      whereSql += ` AND sc.supplier_id = ?`;
      params.push(supplierId);
    }
    if (status) {
      whereSql += ` AND sc.status = ?`;
      params.push(status);
    }
    if (search) {
      whereSql += ` AND (sc.reference_number LIKE ? OR sc.notes LIKE ? OR s.name LIKE ?)`;
      const s = `%${search}%`;
      params.push(s, s, s);
    }
    if (dateFrom) {
      whereSql += ` AND DATE(sc.consignment_date) >= ?`;
      params.push(dateFrom);
    }
    if (dateTo) {
      whereSql += ` AND DATE(sc.consignment_date) <= ?`;
      params.push(dateTo);
    }

    const consignments = await query(
      `SELECT sc.*, s.name as supplier_name, s.phone as supplier_phone,
              u.name as creator_name,
              COUNT(sci.id) as total_items_count,
              COALESCE(SUM(sci.qty_sold), 0) as total_qty_sold
       FROM supplier_consignments sc
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       LEFT JOIN users u ON u.id = sc.created_by
       LEFT JOIN supplier_consignment_items sci ON sci.supplier_consignment_id = sc.id
       ${whereSql}
       GROUP BY sc.id
       ORDER BY sc.consignment_date DESC, sc.id DESC
       LIMIT 100`,
      params
    );

    // Ambil item ringkasan untuk setiap konsinyasi
    const consignmentIds = consignments.map(c => c.id);
    let itemsByConsId = {};
    let paidByConsId = {};
    if (consignmentIds.length > 0) {
      const allItems = await query(
        `SELECT sci.*, mp.sku, mp.name as product_name, mp.stock as current_product_stock
         FROM supplier_consignment_items sci
         LEFT JOIN master_products mp ON mp.id = sci.master_product_id
         WHERE sci.supplier_consignment_id IN (?)`,
        [consignmentIds]
      );
      for (const it of allItems) {
        if (!itemsByConsId[it.supplier_consignment_id]) itemsByConsId[it.supplier_consignment_id] = [];
        itemsByConsId[it.supplier_consignment_id].push({
          id: it.id,
          masterProductId: it.master_product_id,
          sku: it.sku || 'BRG',
          productName: it.product_name || 'Produk',
          qtyReceived: parseInt(it.qty_received || 0, 10),
          qtySold: parseInt(it.qty_sold || 0, 10),
          sisaStok: Math.max(0, parseInt(it.qty_received || 0, 10) - parseInt(it.qty_sold || 0, 10)),
          unitCostPrice: parseFloat(it.unit_cost_price || 0),
          unitSellingPrice: parseFloat(it.unit_selling_price || 0),
          subtotalHpp: parseFloat(it.qty_received || 0) * parseFloat(it.unit_cost_price || 0),
          notes: it.notes || ''
        });
      }

      // Ambil histori pembayaran (settlements) riil per konsinyasi
      try {
        const settlementRows = await query(
          `SELECT sci.supplier_consignment_id,
                  COALESCE(SUM(scsi.subtotal), 0) as total_paid,
                  COALESCE(SUM(scsi.qty_settled), 0) as total_qty_settled
           FROM supplier_consignment_settlement_items scsi
           JOIN supplier_consignment_items sci ON sci.id = scsi.supplier_consignment_item_id
           JOIN supplier_consignment_settlements scs ON scs.id = scsi.settlement_id
           WHERE scs.status = 'approved' AND sci.supplier_consignment_id IN (?)
           GROUP BY sci.supplier_consignment_id`,
          [consignmentIds]
        );
        for (const sr of settlementRows) {
          paidByConsId[sr.supplier_consignment_id] = {
            totalPaid: parseFloat(sr.total_paid || 0),
            totalQtySettled: parseInt(sr.total_qty_settled || 0, 10)
          };
        }
      } catch (err) {
        console.warn('[Node-API] Error querying consignment settlements:', err.message);
      }
    }

    // KPI Counters
    const [kpi] = await query(
      `SELECT COUNT(DISTINCT sc.id) as total_transaksi,
              COALESCE(SUM(sc.total_qty_received), 0) as total_qty_received,
              COALESCE(SUM(sc.total_amount_hpp), 0) as total_amount_hpp
       FROM supplier_consignments sc
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       ${whereSql}`,
      params
    );

    const [stockKpi] = await query(
      `SELECT COALESCE(SUM(sci.qty_received - sci.qty_sold), 0) as total_sisa_stok,
              COALESCE(SUM(sci.qty_sold), 0) as total_qty_sold
       FROM supplier_consignment_items sci
       JOIN supplier_consignments sc ON sc.id = sci.supplier_consignment_id
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       ${whereSql}`,
      params
    );

    let totalPaidAll = 0;
    Object.values(paidByConsId).forEach(p => { totalPaidAll += p.totalPaid; });
    const totalAmountHpp = parseFloat(kpi?.total_amount_hpp || 0);
    const totalSisaPembayaran = Math.max(0, totalAmountHpp - totalPaidAll);

    return res.json({
      success: true,
      kpi: {
        totalTransaksi: parseInt(kpi?.total_transaksi || 0, 10),
        totalQtyReceived: parseInt(kpi?.total_qty_received || 0, 10),
        totalAmountHpp: totalAmountHpp,
        totalSisaStok: Math.max(0, parseInt(stockKpi?.total_sisa_stok || 0, 10)),
        totalQtySold: parseInt(stockKpi?.total_qty_sold || 0, 10),
        totalAmountPaid: totalPaidAll,
        totalSisaPembayaran: totalSisaPembayaran,
      },
      data: consignments.map(c => {
        const paidInfo = paidByConsId[c.id] || { totalPaid: 0, totalQtySettled: 0 };
        const hpp = parseFloat(c.total_amount_hpp || 0);
        const paid = paidInfo.totalPaid;
        const sisa = Math.max(0, hpp - paid);
        let paymentStatus = 'unpaid';
        let paymentStatusLabel = 'Belum Lunas';
        if (paid >= hpp && hpp > 0) {
          paymentStatus = 'paid';
          paymentStatusLabel = 'Lunas';
        } else if (paid > 0) {
          paymentStatus = 'partial';
          paymentStatusLabel = 'Sebagian';
        }

        return {
          id: c.id,
          referenceNumber: c.reference_number,
          consignmentDate: extractDateStr(c.consignment_date),
          consignmentDateFormatted: formatIndoDate(c.consignment_date),
          supplierId: c.supplier_id,
          supplierName: c.supplier_name || 'Supplier Titipan',
          supplierPhone: c.supplier_phone || '',
          status: c.status || 'approved',
          totalQtyReceived: parseInt(c.total_qty_received || 0, 10),
          totalQtySold: parseInt(c.total_qty_sold || 0, 10),
          sisaStok: Math.max(0, parseInt(c.total_qty_received || 0, 10) - parseInt(c.total_qty_sold || 0, 10)),
          totalAmountHpp: hpp,
          totalAmountPaid: paid,
          sisaPembayaran: sisa,
          paymentStatus: paymentStatus,
          paymentStatusLabel: paymentStatusLabel,
          totalQtySettled: paidInfo.totalQtySettled,
          notes: c.notes || '',
          creatorName: c.creator_name || 'Admin',
          itemsCount: parseInt(c.total_items_count || 0, 10),
          items: itemsByConsId[c.id] || []
        };
      })
    });
  } catch (err) {
    console.error('[Node-API] Consignment list error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Tambah Transaksi Konsinyasi Baru (Create)
app.post(['/api/v2/owner/supplier-consignments', '/api/v2/supplier-consignments'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const authUser = getAuthUser(req);
    const userId = authUser?.id || 1;

    const { supplier_id, consignment_date, notes, items } = req.body;
    const cleanSupplierId = parseInt(supplier_id, 10);

    if (isNaN(cleanSupplierId) || cleanSupplierId <= 0) {
      return res.status(400).json({ success: false, message: 'Supplier wajib dipilih.' });
    }
    if (!consignment_date) {
      return res.status(400).json({ success: false, message: 'Tanggal titipan wajib diisi.' });
    }
    if (!Array.isArray(items) || items.length === 0) {
      return res.status(400).json({ success: false, message: 'Minimal 1 produk barang titipan wajib dimasukkan.' });
    }

    // Auto reference number
    const todayStr = formatDate(new Date(consignment_date) || new Date()).replace(/-/g, '');
    const prefix = `KNS-${todayStr}-`;
    const [lastCons] = await query(
      `SELECT reference_number FROM supplier_consignments
       WHERE tenant_id = ? AND reference_number LIKE ?
       ORDER BY id DESC LIMIT 1`,
      [tenantId, `${prefix}%`]
    );
    let nextNum = '0001';
    if (lastCons && lastCons.reference_number) {
      const parts = lastCons.reference_number.split('-');
      const lastSeq = parseInt(parts[parts.length - 1], 10);
      if (!isNaN(lastSeq)) nextNum = String(lastSeq + 1).padStart(4, '0');
    }
    const referenceNumber = `${prefix}${nextNum}`;

    let totalQtyReceived = 0;
    let totalAmountHpp = 0;
    for (const row of items) {
      const qty = parseInt(row.qty_received || 0, 10);
      const cost = parseFloat(row.unit_cost_price || 0);
      totalQtyReceived += qty;
      totalAmountHpp += (qty * cost);
    }

    const insertResult = await query(
      `INSERT INTO supplier_consignments
       (tenant_id, supplier_id, reference_number, consignment_date, status, total_qty_received, total_amount_hpp, notes, created_by, approved_by, approved_at, created_at, updated_at)
       VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, NOW(), NOW(), NOW())`,
      [tenantId, cleanSupplierId, referenceNumber, consignment_date, totalQtyReceived, totalAmountHpp, notes || null, userId, userId]
    );

    const consignmentId = insertResult.insertId;

    for (const row of items) {
      const prodId = parseInt(row.master_product_id, 10);
      const qty = parseInt(row.qty_received || 0, 10);
      const costPrice = parseFloat(row.unit_cost_price || 0);
      const sellingPrice = parseFloat(row.unit_selling_price || 0);

      await query(
        `INSERT INTO supplier_consignment_items
         (supplier_consignment_id, master_product_id, qty_received, qty_sold, unit_cost_price, unit_selling_price, notes, created_at, updated_at)
         VALUES (?, ?, ?, 0, ?, ?, ?, NOW(), NOW())`,
        [consignmentId, prodId, qty, costPrice, sellingPrice, row.notes || null]
      );

      // Tambah stok master_products secara otomatis
      await query(
        `UPDATE master_products
         SET stock = stock + ?,
             cost_price = CASE WHEN cost_price <= 0 THEN ? ELSE cost_price END,
             price = CASE WHEN price <= 0 THEN ? ELSE price END,
             updated_at = NOW()
         WHERE id = ? AND tenant_id = ?`,
        [qty, costPrice, sellingPrice, prodId, tenantId]
      );

      // Log mutasi kartu stok master_product_stock_logs jika tabel tersedia
      try {
        const [prodRow] = await query('SELECT stock FROM master_products WHERE id = ?', [prodId]);
        const newBalance = prodRow?.stock || 0;
        await query(
          `INSERT INTO master_product_stock_logs
           (tenant_id, master_product_id, user_id, type, qty, notes, balance_after, created_at, updated_at)
           VALUES (?, ?, ?, 'in', ?, ?, ?, NOW(), NOW())`,
          [tenantId, prodId, userId, qty, `Penerimaan Konsinyasi: ${referenceNumber}`, newBalance]
        );
      } catch (_) {}
    }

    return res.json({
      success: true,
      message: 'Transaksi titipan barang berhasil dicatat.',
      data: {
        id: consignmentId,
        referenceNumber
      }
    });
  } catch (err) {
    console.error('[Node-API] Create consignment error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Detail Transaksi Konsinyasi
app.get(['/api/v2/owner/supplier-consignments/:id', '/api/v2/supplier-consignments/:id'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const consId = parseInt(req.params.id, 10);

    if (isNaN(consId) || consId <= 0) {
      return res.status(400).json({ success: false, message: 'ID transaksi titipan barang tidak valid.' });
    }

    const [cons] = await query(
      `SELECT sc.*, s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address,
              s.contact_person as supplier_contact,
              u.name as creator_name
       FROM supplier_consignments sc
       LEFT JOIN suppliers s ON s.id = sc.supplier_id
       LEFT JOIN users u ON u.id = sc.created_by
       WHERE sc.id = ? AND sc.tenant_id = ? LIMIT 1`,
      [consId, tenantId]
    );

    if (!cons) {
      return res.status(404).json({ success: false, message: 'Transaksi penerimaan konsinyasi tidak ditemukan.' });
    }

    const items = await query(
      `SELECT sci.*, mp.sku, mp.name as product_name, mp.stock as current_product_stock, mp.unit
       FROM supplier_consignment_items sci
       LEFT JOIN master_products mp ON mp.id = sci.master_product_id
       WHERE sci.supplier_consignment_id = ?
       ORDER BY sci.id ASC`,
      [consId]
    );

    // Ambil histori pengurangan stok titipan dari order fulfillment jika ada
    let deductions = [];
    try {
      deductions = await query(
        `SELECT scd.*, o.invoice_number, o.order_marketplace_id, o.order_status
         FROM supplier_consignment_deductions scd
         LEFT JOIN orders o ON o.id = scd.order_id
         WHERE scd.supplier_consignment_item_id IN (SELECT id FROM supplier_consignment_items WHERE supplier_consignment_id = ?)
         ORDER BY scd.id DESC LIMIT 50`,
        [consId]
      );
    } catch (_) {}

    // Ambil histori setoran pembayaran (settlements) untuk transaksi konsinyasi ini
    let settlements = [];
    let totalAmountPaid = 0;
    try {
      settlements = await query(
        `SELECT scs.id as settlement_id, scs.settlement_number, scs.settlement_date,
                scs.payment_method, scs.reference_number as payment_ref,
                scsi.qty_settled, scsi.unit_cost_price, scsi.subtotal,
                mp.name as product_name, mp.sku
         FROM supplier_consignment_settlement_items scsi
         JOIN supplier_consignment_items sci ON sci.id = scsi.supplier_consignment_item_id
         JOIN supplier_consignment_settlements scs ON scs.id = scsi.settlement_id
         LEFT JOIN master_products mp ON mp.id = scsi.master_product_id
         WHERE sci.supplier_consignment_id = ? AND scs.status = 'approved'
         ORDER BY scs.settlement_date DESC, scs.id DESC`,
        [consId]
      );
      for (const st of settlements) {
        totalAmountPaid += parseFloat(st.subtotal || 0);
      }
    } catch (_) {}

    const totalHpp = parseFloat(cons.total_amount_hpp || 0);
    const sisaPembayaran = Math.max(0, totalHpp - totalAmountPaid);
    let paymentStatus = 'unpaid';
    let paymentStatusLabel = 'Belum Lunas';
    if (totalAmountPaid >= totalHpp && totalHpp > 0) {
      paymentStatus = 'paid';
      paymentStatusLabel = 'Lunas';
    } else if (totalAmountPaid > 0) {
      paymentStatus = 'partial';
      paymentStatusLabel = 'Sebagian';
    }

    return res.json({
      success: true,
      data: {
        id: cons.id,
        referenceNumber: cons.reference_number,
        consignmentDate: extractDateStr(cons.consignment_date),
        consignmentDateFormatted: formatIndoDate(cons.consignment_date),
        supplierId: cons.supplier_id,
        supplierName: cons.supplier_name || 'Supplier Titipan',
        supplierPhone: cons.supplier_phone || '',
        supplierAddress: cons.supplier_address || '',
        supplierContact: cons.supplier_contact || '',
        status: cons.status || 'approved',
        totalQtyReceived: parseInt(cons.total_qty_received || 0, 10),
        totalAmountHpp: totalHpp,
        totalAmountPaid: totalAmountPaid,
        sisaPembayaran: sisaPembayaran,
        paymentStatus: paymentStatus,
        paymentStatusLabel: paymentStatusLabel,
        notes: cons.notes || '',
        creatorName: cons.creator_name || 'Admin',
        createdAt: cons.created_at,
        items: items.map(it => ({
          id: it.id,
          masterProductId: it.master_product_id,
          sku: it.sku || 'BRG',
          productName: it.product_name || 'Produk',
          unit: it.unit || 'PCS',
          currentProductStock: parseInt(it.current_product_stock || 0, 10),
          qtyReceived: parseInt(it.qty_received || 0, 10),
          qtySold: parseInt(it.qty_sold || 0, 10),
          sisaStok: Math.max(0, parseInt(it.qty_received || 0, 10) - parseInt(it.qty_sold || 0, 10)),
          unitCostPrice: parseFloat(it.unit_cost_price || 0),
          unitSellingPrice: parseFloat(it.unit_selling_price || 0),
          subtotalHpp: parseFloat(it.qty_received || 0) * parseFloat(it.unit_cost_price || 0),
          notes: it.notes || ''
        })),
        deductions: deductions.map(d => ({
          id: d.id,
          orderId: d.order_id,
          invoiceNumber: d.invoice_number || d.order_marketplace_id || '-',
          qtyDeducted: parseInt(d.qty_deducted || 0, 10),
          notes: d.notes || '',
          createdAt: d.created_at,
          dateFormatted: formatIndoDate(d.created_at)
        })),
        settlements: settlements.map(st => ({
          id: st.settlement_id,
          settlementNumber: st.settlement_number,
          dateFormatted: formatIndoDate(st.settlement_date),
          paymentMethod: st.payment_method || 'transfer',
          paymentRef: st.payment_ref || '-',
          productName: st.product_name,
          sku: st.sku,
          qtySettled: parseInt(st.qty_settled || 0, 10),
          subtotal: parseFloat(st.subtotal || 0)
        }))
      }
    });
  } catch (err) {
    console.error('[Node-API] Consignment detail error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Update Transaksi Konsinyasi (Edit)
app.put(['/api/v2/owner/supplier-consignments/:id', '/api/v2/supplier-consignments/:id'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const authUser = getAuthUser(req);
    const userId = authUser?.id || 1;
    const consId = parseInt(req.params.id, 10);

    if (isNaN(consId) || consId <= 0) {
      return res.status(400).json({ success: false, message: 'ID transaksi tidak valid.' });
    }

    const [existing] = await query(
      `SELECT * FROM supplier_consignments WHERE id = ? AND tenant_id = ? LIMIT 1`,
      [consId, tenantId]
    );
    if (!existing) {
      return res.status(404).json({ success: false, message: 'Transaksi tidak ditemukan.' });
    }

    const { supplier_id, consignment_date, notes, items } = req.body;
    const cleanSupplierId = parseInt(supplier_id, 10);

    if (isNaN(cleanSupplierId) || cleanSupplierId <= 0 || !consignment_date || !Array.isArray(items) || items.length === 0) {
      return res.status(400).json({ success: false, message: 'Data update tidak lengkap.' });
    }

    // Revert stok lama terlebih dahulu
    const oldItems = await query(
      `SELECT master_product_id, qty_received FROM supplier_consignment_items WHERE supplier_consignment_id = ?`,
      [consId]
    );
    for (const oldIt of oldItems) {
      await query(
        `UPDATE master_products SET stock = GREATEST(0, stock - ?) WHERE id = ? AND tenant_id = ?`,
        [oldIt.qty_received, oldIt.master_product_id, tenantId]
      );
      try {
        await query(
          `DELETE FROM master_product_stock_logs
           WHERE tenant_id = ? AND notes LIKE ?`,
          [tenantId, `%${existing.reference_number}%`]
        );
      } catch (_) {}
    }

    // Hapus detail items lama
    await query(`DELETE FROM supplier_consignment_items WHERE supplier_consignment_id = ?`, [consId]);

    // Masukkan items baru & hitung ulang akumulasi
    let totalQtyReceived = 0;
    let totalAmountHpp = 0;
    for (const row of items) {
      const prodId = parseInt(row.master_product_id, 10);
      const qty = parseInt(row.qty_received || 0, 10);
      const costPrice = parseFloat(row.unit_cost_price || 0);
      const sellingPrice = parseFloat(row.unit_selling_price || 0);

      totalQtyReceived += qty;
      totalAmountHpp += (qty * costPrice);

      await query(
        `INSERT INTO supplier_consignment_items
         (supplier_consignment_id, master_product_id, qty_received, qty_sold, unit_cost_price, unit_selling_price, notes, created_at, updated_at)
         VALUES (?, ?, ?, 0, ?, ?, ?, NOW(), NOW())`,
        [consId, prodId, qty, costPrice, sellingPrice, row.notes || null]
      );

      await query(
        `UPDATE master_products
         SET stock = stock + ?,
             cost_price = CASE WHEN cost_price <= 0 THEN ? ELSE cost_price END,
             price = CASE WHEN price <= 0 THEN ? ELSE price END,
             updated_at = NOW()
         WHERE id = ? AND tenant_id = ?`,
        [qty, costPrice, sellingPrice, prodId, tenantId]
      );

      try {
        const [prodRow] = await query('SELECT stock FROM master_products WHERE id = ?', [prodId]);
        const newBalance = prodRow?.stock || 0;
        await query(
          `INSERT INTO master_product_stock_logs
           (tenant_id, master_product_id, user_id, type, qty, notes, balance_after, created_at, updated_at)
           VALUES (?, ?, ?, 'in', ?, ?, ?, NOW(), NOW())`,
          [tenantId, prodId, userId, qty, `Penerimaan Konsinyasi: ${existing.reference_number}`, newBalance]
        );
      } catch (_) {}
    }

    await query(
      `UPDATE supplier_consignments
       SET supplier_id = ?, consignment_date = ?, notes = ?,
           total_qty_received = ?, total_amount_hpp = ?, updated_at = NOW()
       WHERE id = ? AND tenant_id = ?`,
      [cleanSupplierId, consignment_date, notes || null, totalQtyReceived, totalAmountHpp, consId, tenantId]
    );

    return res.json({
      success: true,
      message: 'Transaksi titipan barang berhasil diperbarui.'
    });
  } catch (err) {
    console.error('[Node-API] Update consignment error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// Hapus Transaksi Konsinyasi (Delete)
app.delete(['/api/v2/owner/supplier-consignments/:id', '/api/v2/supplier-consignments/:id'], async (req, res) => {
  try {
    const tenantId = getTenantId(req);
    const consId = parseInt(req.params.id, 10);

    if (isNaN(consId) || consId <= 0) {
      return res.status(400).json({ success: false, message: 'ID transaksi tidak valid.' });
    }

    const [existing] = await query(
      `SELECT * FROM supplier_consignments WHERE id = ? AND tenant_id = ? LIMIT 1`,
      [consId, tenantId]
    );
    if (!existing) {
      return res.status(404).json({ success: false, message: 'Transaksi tidak ditemukan.' });
    }

    // Revert stok dari master_products
    const items = await query(
      `SELECT master_product_id, qty_received FROM supplier_consignment_items WHERE supplier_consignment_id = ?`,
      [consId]
    );
    for (const oldIt of items) {
      await query(
        `UPDATE master_products SET stock = GREATEST(0, stock - ?) WHERE id = ? AND tenant_id = ?`,
        [oldIt.qty_received, oldIt.master_product_id, tenantId]
      );
      try {
        await query(
          `DELETE FROM master_product_stock_logs
           WHERE tenant_id = ? AND notes LIKE ?`,
          [tenantId, `%${existing.reference_number}%`]
        );
      } catch (_) {}
    }

    await query(`DELETE FROM supplier_consignment_items WHERE supplier_consignment_id = ?`, [consId]);
    await query(`DELETE FROM supplier_consignments WHERE id = ? AND tenant_id = ?`, [consId, tenantId]);

    return res.json({
      success: true,
      message: 'Transaksi titipan barang berhasil dihapus dan stok telah disesuaikan kembali.'
    });
  } catch (err) {
    console.error('[Node-API] Delete consignment error:', err);
    return res.status(500).json({ success: false, message: err.message });
  }
});


// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'mobile-owner-backend', time: new Date() });
});

app.get('/api/v2/owner/health', (req, res) => {
  res.json({ status: 'ok', service: 'mobile-owner-backend', time: new Date() });
});

app.listen(PORT, () => {
  console.log(`[Node-API] Mobile Owner Backend listening on http://localhost:${PORT}`);
});
