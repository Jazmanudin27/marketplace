import db from '../config/database.js';

/**
 * GET /api/produk
 * Fetches paginated master product list with advanced filtering and relationship data matching Laravel controller
 */
export const getProdukList = async (req, res) => {
  try {
    const {
      name,
      sku,
      search,
      is_bundle,
      is_preorder,
      link_status,
      channel_id,
      store_id,
      page: reqPage = '1',
      limit: reqLimit = '25'
    } = req.query;

    const page = parseInt(reqPage, 10);
    const limit = parseInt(reqLimit, 10);
    const offset = (page - 1) * limit;

    const whereConditions = [];
    const params = [];

    // Filter Nama
    if (name) {
      whereConditions.push(`p.name LIKE ?`);
      params.push(`%${name}%`);
    }

    // Filter SKU / Search Keyword
    const searchKeyword = sku || search;
    if (searchKeyword) {
      whereConditions.push(`(p.sku LIKE ? OR p.sku_induk LIKE ? OR p.name LIKE ?)`);
      params.push(`%${searchKeyword}%`, `%${searchKeyword}%`, `%${searchKeyword}%`);
    }

    // Filter Tipe Produk (Single / Bundle)
    if (is_bundle !== undefined && is_bundle !== '') {
      if (is_bundle === '1') {
        whereConditions.push(`p.is_bundle = 1`);
      } else if (is_bundle === '0') {
        whereConditions.push(`(p.is_bundle = 0 OR p.is_bundle IS NULL)`);
      }
    }

    // Filter Status PO (Pre-Order / Ready Stock)
    if (is_preorder !== undefined && is_preorder !== '') {
      if (is_preorder === '1') {
        whereConditions.push(`p.is_preorder = 1`);
      } else if (is_preorder === '0') {
        whereConditions.push(`(p.is_preorder = 0 OR p.is_preorder IS NULL)`);
      }
    }

    // Filter Channel / Store / Link Status
    if (channel_id) {
      whereConditions.push(`EXISTS (
        SELECT 1 FROM marketplace_products mp 
        JOIN stores s ON mp.store_id = s.id 
        WHERE mp.master_product_id = p.id AND s.channel_id = ?
      )`);
      params.push(channel_id);
    }

    if (store_id) {
      whereConditions.push(`EXISTS (
        SELECT 1 FROM marketplace_products mp 
        WHERE mp.master_product_id = p.id AND mp.store_id = ?
      )`);
      params.push(store_id);
    }

    if (link_status === 'unlinked') {
      whereConditions.push(`NOT EXISTS (
        SELECT 1 FROM marketplace_products mp 
        WHERE mp.master_product_id = p.id AND LOWER(TRIM(mp.marketplace_sku)) = LOWER(TRIM(p.sku))
      )`);
    }

    const whereClause = whereConditions.length > 0 ? ` WHERE ` + whereConditions.join(' AND ') : '';

    const query = `
      SELECT p.id, p.name, p.sku, p.sku_induk, p.cost_price, p.price, p.reseller_price, 
             p.stock, p.min_stock, p.unit, p.is_active, p.is_bundle, p.is_preorder, 
             p.preorder_days, p.image_url, p.sub_kategori, p.category_id, p.brand_id,
             c.name as category_name, b.name as brand_name
      FROM master_products p
      LEFT JOIN categories c ON p.category_id = c.id
      LEFT JOIN brands b ON p.brand_id = b.id
      ${whereClause}
      ORDER BY p.name ASC
      LIMIT ? OFFSET ?
    `;
    params.push(limit, offset);

    const countQuery = `SELECT COUNT(*) as total FROM master_products p ${whereClause}`;
    const countParams = params.slice(0, -2);

    const [rows] = await db.query(query, params);
    const [countRows] = await db.query(countQuery, countParams);
    const total = countRows[0]?.total || 0;

    // Fetch Marketplace Store Mappings for loaded products
    const productIds = rows.map(r => r.id);
    let marketplaceMap = {};

    if (productIds.length > 0) {
      const [mpRows] = await db.query(`
        SELECT mp.master_product_id, mp.store_id, s.store_name, ch.name as channel_name, ch.code as channel_code
        FROM marketplace_products mp
        JOIN stores s ON mp.store_id = s.id
        LEFT JOIN channels ch ON s.channel_id = ch.id
        WHERE mp.master_product_id IN (?)
      `, [productIds]);

      mpRows.forEach(mp => {
        if (!marketplaceMap[mp.master_product_id]) {
          marketplaceMap[mp.master_product_id] = [];
        }
        marketplaceMap[mp.master_product_id].push({
          store_id: mp.store_id,
          store_name: mp.store_name,
          channel_name: mp.channel_name,
          channel_code: mp.channel_code
        });
      });
    }

    // Quick Pill Counts
    const [[{ totalAll }]] = await db.query(`SELECT COUNT(*) as totalAll FROM master_products`);
    const [[{ singleCount }]] = await db.query(`SELECT COUNT(*) as singleCount FROM master_products WHERE is_bundle = 0 OR is_bundle IS NULL`);
    const [[{ bundleCount }]] = await db.query(`SELECT COUNT(*) as bundleCount FROM master_products WHERE is_bundle = 1`);
    const [[{ readyCount }]] = await db.query(`SELECT COUNT(*) as readyCount FROM master_products WHERE is_preorder = 0 OR is_preorder IS NULL`);
    const [[{ poCount }]] = await db.query(`SELECT COUNT(*) as poCount FROM master_products WHERE is_preorder = 1`);
    const [[{ unlinkedCount }]] = await db.query(`
      SELECT COUNT(*) as unlinkedCount FROM master_products p 
      WHERE NOT EXISTS (
        SELECT 1 FROM marketplace_products mp 
        WHERE mp.master_product_id = p.id AND LOWER(TRIM(mp.marketplace_sku)) = LOWER(TRIM(p.sku))
      )
    `);

    // Fetch Stores & Channels dropdown data
    const [stores] = await db.query(`SELECT s.id, s.store_name, c.name as channel_name FROM stores s LEFT JOIN channels c ON s.channel_id = c.id WHERE s.status = 'connected' ORDER BY s.store_name`);
    const [channels] = await db.query(`SELECT id, name, code FROM channels ORDER BY name`);

    return res.json({
      success: true,
      data: {
        products: {
          total,
          current_page: page,
          per_page: limit,
          last_page: Math.ceil(total / limit),
          data: rows.map(r => ({
            id: r.id,
            name: r.name,
            sku: r.sku,
            sku_induk: r.sku_induk,
            image_url: r.image_url,
            cost_price: r.cost_price || 0,
            price: r.price || 0,
            reseller_price: r.reseller_price || 0,
            stock: r.stock || 0,
            min_stock: r.min_stock || 5,
            unit: r.unit || 'pcs',
            is_active: r.is_active === 1 || r.is_active === true,
            is_bundle: r.is_bundle === 1 || r.is_bundle === true,
            is_preorder: r.is_preorder === 1 || r.is_preorder === true,
            preorder_days: r.preorder_days || 7,
            sub_kategori: r.sub_kategori || '',
            category: { name: r.category_name || '-' },
            brand: { name: r.brand_name || '-' },
            marketplace_stores: marketplaceMap[r.id] || []
          }))
        },
        counts: {
          total: totalAll,
          single: singleCount,
          bundle: bundleCount,
          ready: readyCount,
          po: poCount,
          unlinked: unlinkedCount
        },
        stores,
        channels
      }
    });
  } catch (err) {
    console.error('[ProdukController] Error fetching master products:', err);
    return res.status(500).json({ success: false, error: err.message });
  }
};

