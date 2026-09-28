import db from '../config/database.js';

/**
 * GET /api/produk
 * Fetches paginated master product list with optional keyword filtering and relationships
 */
export const getProdukList = async (req, res) => {
  try {
    const search = req.query.search ? `%${req.query.search}%` : null;
    const page = parseInt(req.query.page || '1', 10);
    const limit = parseInt(req.query.limit || '15', 10);
    const offset = (page - 1) * limit;

    let query = `
      SELECT p.id, p.name, p.sku, p.cost_price, p.price, p.stock, p.unit, p.is_active, p.image_url,
             p.ukuran, p.warna,
             c.name as category_name, b.name as brand_name
      FROM master_products p
      LEFT JOIN categories c ON p.category_id = c.id
      LEFT JOIN brands b ON p.brand_id = b.id
    `;
    let countQuery = `SELECT COUNT(*) as total FROM master_products p`;
    const params = [];
    const countParams = [];

    if (search) {
      const where = ` WHERE p.name LIKE ? OR p.sku LIKE ?`;
      query += where;
      countQuery += where;
      params.push(search, search);
      countParams.push(search, search);
    }

    query += ` ORDER BY p.name ASC LIMIT ? OFFSET ?`;
    params.push(limit, offset);

    const [rows] = await db.query(query, params);
    const [countRows] = await db.query(countQuery, countParams);
    const total = countRows[0]?.total || 0;

    return res.json({
      success: true,
      data: {
        products: {
          total,
          current_page: page,
          per_page: limit,
          data: rows.map(r => ({
            id: r.id,
            name: r.name,
            sku: r.sku,
            image_url: r.image_url,
            ukuran: r.ukuran,
            warna: r.warna,
            cost_price: r.cost_price,
            price: r.price,
            stock: r.stock,
            unit: r.unit,
            is_active: r.is_active === 1 || r.is_active === true,
            category: { name: r.category_name || '-' },
            brand: { name: r.brand_name || '-' }
          }))
        }
      }
    });
  } catch (err) {
    console.error('[ProdukController] Error fetching master products:', err);
    return res.status(500).json({ success: false, error: err.message });
  }
};
