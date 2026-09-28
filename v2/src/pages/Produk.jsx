import React, { useEffect, useState } from 'react';
import { fetchProdukData } from '../services/api';
import { Box, Plus, Search, RotateCw, Edit, UploadCloud, Trash2, Filter, X, CheckSquare, Layers, Store, Link2, Tag, Barcode, Boxes, Clock, Bolt, CheckCircle2 } from 'lucide-react';

const Produk = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  // Filters State
  const [nameFilter, setNameFilter] = useState('');
  const [skuFilter, setSkuFilter] = useState('');
  const [isBundle, setIsBundle] = useState('');
  const [isPreorder, setIsPreorder] = useState('');
  const [channelId, setChannelId] = useState('');
  const [storeId, setStoreId] = useState('');
  const [linkStatus, setLinkStatus] = useState('');
  const [activePill, setActivePill] = useState('all');

  const [selectedProducts, setSelectedProducts] = useState([]);

  const loadData = async (overrideParams = {}) => {
    setLoading(true);
    const params = {
      name: nameFilter,
      sku: skuFilter,
      is_bundle: isBundle,
      is_preorder: isPreorder,
      channel_id: channelId,
      store_id: storeId,
      link_status: linkStatus,
      ...overrideParams
    };

    const res = await fetchProdukData(params);
    setData(res);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleFilterSubmit = (e) => {
    e.preventDefault();
    loadData();
  };

  const handlePillClick = (pillType, paramObj) => {
    setActivePill(pillType);
    if (pillType === 'all') {
      setIsBundle('');
      setIsPreorder('');
      setLinkStatus('');
      loadData({ is_bundle: '', is_preorder: '', link_status: '' });
    } else {
      setIsBundle(paramObj.is_bundle !== undefined ? paramObj.is_bundle : isBundle);
      setIsPreorder(paramObj.is_preorder !== undefined ? paramObj.is_preorder : isPreorder);
      setLinkStatus(paramObj.link_status !== undefined ? paramObj.link_status : linkStatus);
      loadData(paramObj);
    }
  };

  const handleReset = () => {
    setNameFilter('');
    setSkuFilter('');
    setIsBundle('');
    setIsPreorder('');
    setChannelId('');
    setStoreId('');
    setLinkStatus('');
    setActivePill('all');
    loadData({ name: '', sku: '', is_bundle: '', is_preorder: '', channel_id: '', store_id: '', link_status: '' });
  };

  const toggleSelectAll = (e) => {
    if (e.target.checked) {
      const allIds = data?.products?.data?.map(p => p.id) || [];
      setSelectedProducts(allIds);
    } else {
      setSelectedProducts([]);
    }
  };

  const toggleSelectProduct = (id) => {
    if (selectedProducts.includes(id)) {
      setSelectedProducts(selectedProducts.filter(item => item !== id));
    } else {
      setSelectedProducts([...selectedProducts, id]);
    }
  };

  const counts = data?.counts || { total: 0, single: 0, bundle: 0, ready: 0, po: 0, unlinked: 0 };
  const productsList = data?.products?.data || [];
  const storesList = data?.stores || [];
  const channelsList = data?.channels || [];

  return (
    <div className="container-fluid px-0">
      {/* Page Header matching Laravel Top Bar */}
      <div className="card border shadow-sm mb-3">
        <div className="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2.5 px-3">
          <div>
            <h6 className="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
              <Box size={18} className="text-info" /> Daftar Master Produk
            </h6>
            <small className="text-muted d-block" style={{ fontSize: '0.72rem' }}>
              Kelola produk, harga, stok, dan koneksi marketplace
            </small>
          </div>
          <div className="d-flex align-items-center gap-2">
            <button type="button" onClick={() => loadData()} className="btn btn-outline-secondary btn-sm px-2.5 rounded-3" title="Refresh Data">
              <RotateCw size={14} />
            </button>
            <button className="btn btn-primary btn-sm px-3 rounded-3 shadow-sm fw-semibold" style={{ fontSize: '0.78rem' }}>
              <Plus size={14} className="me-1" /> Tambah Produk
            </button>
          </div>
        </div>

        <div className="card-body p-3">
          {/* Quick Filter Pills (Exact Match to Laravel Screenshot) */}
          <div className="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom">
            <span className="fw-bold small text-muted me-1" style={{ fontSize: '0.75rem' }}>
              <Filter size={12} className="text-primary me-1" /> Filter Cepat:
            </span>

            {/* Semua Produk */}
            <button
              type="button"
              onClick={() => handlePillClick('all', { is_bundle: '', is_preorder: '', link_status: '' })}
              className={`btn btn-xs rounded-pill px-3 py-1 fw-bold ${activePill === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`}
              style={{ fontSize: '0.72rem' }}
            >
              🌐 Semua Produk <span className="badge bg-white text-dark ms-1">{counts.total}</span>
            </button>

            <span className="text-muted opacity-25">|</span>

            {/* Single */}
            <button
              type="button"
              onClick={() => handlePillClick('single', { is_bundle: '0' })}
              className="btn btn-xs rounded-pill px-3 py-1 fw-bold"
              style={{
                fontSize: '0.72rem',
                backgroundColor: activePill === 'single' ? '#0284c7' : 'transparent',
                borderColor: '#0284c7',
                color: activePill === 'single' ? '#fff' : '#0284c7'
              }}
            >
              📦 Single <span className="badge bg-white text-dark ms-1">{counts.single}</span>
            </button>

            {/* Bundle / Set */}
            <button
              type="button"
              onClick={() => handlePillClick('bundle', { is_bundle: '1' })}
              className="btn btn-xs rounded-pill px-3 py-1 fw-bold"
              style={{
                fontSize: '0.72rem',
                backgroundColor: activePill === 'bundle' ? '#ec4899' : 'transparent',
                borderColor: '#ec4899',
                color: activePill === 'bundle' ? '#fff' : '#ec4899'
              }}
            >
              🍱 Bundle / Set <span className="badge bg-white text-dark ms-1">{counts.bundle}</span>
            </button>

            <span className="text-muted opacity-25">|</span>

            {/* Ready Stock */}
            <button
              type="button"
              onClick={() => handlePillClick('ready', { is_preorder: '0' })}
              className="btn btn-xs rounded-pill px-3 py-1 fw-bold"
              style={{
                fontSize: '0.72rem',
                backgroundColor: activePill === 'ready' ? '#16a34a' : 'transparent',
                borderColor: '#16a34a',
                color: activePill === 'ready' ? '#fff' : '#16a34a'
              }}
            >
              ⚡ Ready Stock <span className="badge bg-white text-dark ms-1">{counts.ready}</span>
            </button>

            {/* Pre-Order (PO) */}
            <button
              type="button"
              onClick={() => handlePillClick('po', { is_preorder: '1' })}
              className="btn btn-xs rounded-pill px-3 py-1 fw-bold"
              style={{
                fontSize: '0.72rem',
                backgroundColor: activePill === 'po' ? '#8b5cf6' : 'transparent',
                borderColor: '#8b5cf6',
                color: activePill === 'po' ? '#fff' : '#8b5cf6'
              }}
            >
              ⏳ Pre-Order (PO) <span className="badge bg-white text-dark ms-1">{counts.po}</span>
            </button>

            <span className="text-muted opacity-25">|</span>

            {/* Belum Terhubung */}
            <button
              type="button"
              onClick={() => handlePillClick('unlinked', { link_status: 'unlinked' })}
              className="btn btn-xs rounded-pill px-3 py-1 fw-bold"
              style={{
                fontSize: '0.72rem',
                backgroundColor: activePill === 'unlinked' ? '#475569' : 'transparent',
                borderColor: '#475569',
                color: activePill === 'unlinked' ? '#fff' : '#475569'
              }}
            >
              🔗 Belum Terhubung <span className="badge bg-white text-dark ms-1">{counts.unlinked}</span>
            </button>
          </div>

          {/* Advanced Multi-Field Filter Form matching Laravel */}
          <form onSubmit={handleFilterSubmit}>
            <div className="row g-2 align-items-end">
              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Tag size={12} className="text-muted me-1" />Nama Barang
                </label>
                <input
                  type="text"
                  className="form-control form-control-sm"
                  placeholder="Cari nama barang..."
                  value={nameFilter}
                  onChange={(e) => setNameFilter(e.target.value)}
                />
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Barcode size={12} className="text-muted me-1" />SKU
                </label>
                <input
                  type="text"
                  className="form-control form-control-sm"
                  placeholder="Cari SKU..."
                  value={skuFilter}
                  onChange={(e) => setSkuFilter(e.target.value)}
                />
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Boxes size={12} className="text-muted me-1" />Tipe Produk
                </label>
                <select className="form-select form-select-sm" value={isBundle} onChange={(e) => setIsBundle(e.target.value)}>
                  <option value="">-- Semua Tipe --</option>
                  <option value="0">Single</option>
                  <option value="1">Bundle / Set</option>
                </select>
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Clock size={12} className="text-muted me-1" />Status PO
                </label>
                <select className="form-select form-select-sm" value={isPreorder} onChange={(e) => setIsPreorder(e.target.value)}>
                  <option value="">-- Semua Status PO --</option>
                  <option value="0">Ready Stock (Non-PO)</option>
                  <option value="1">Pre-Order (PO)</option>
                </select>
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Layers size={12} className="text-muted me-1" />Channel
                </label>
                <select className="form-select form-select-sm" value={channelId} onChange={(e) => setChannelId(e.target.value)}>
                  <option value="">-- Semua Channel --</option>
                  {channelsList.map(ch => <option key={ch.id} value={ch.id}>{ch.name}</option>)}
                </select>
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Store size={12} className="text-muted me-1" />Akun / Toko
                </label>
                <select className="form-select form-select-sm" value={storeId} onChange={(e) => setStoreId(e.target.value)}>
                  <option value="">-- Semua Toko --</option>
                  {storesList.map(s => <option key={s.id} value={s.id}>{s.store_name} ({s.channel_name})</option>)}
                </select>
              </div>

              <div className="col-md-2">
                <label className="form-label form-label-sm fw-semibold mb-1" style={{ fontSize: '0.72rem' }}>
                  <Link2 size={12} className="text-muted me-1" />Tautan Toko
                </label>
                <select className="form-select form-select-sm" value={linkStatus} onChange={(e) => setLinkStatus(e.target.value)}>
                  <option value="">-- Semua Status --</option>
                  <option value="unlinked">Belum Ditautkan (0 Toko)</option>
                  <option value="partial">Ditautkan Sebagian Toko</option>
                  <option value="all">Ditautkan Semua Toko</option>
                </select>
              </div>

              <div className="col-md-auto d-flex gap-1">
                <button type="submit" className="btn btn-primary btn-sm px-3 fw-semibold">
                  <Search size={12} className="me-1" />Terapkan
                </button>
                <button type="button" onClick={handleReset} className="btn btn-secondary btn-sm px-2" title="Reset">
                  <X size={14} />
                </button>
              </div>

              <div className="col-md ms-auto text-end align-self-center">
                <small className="text-muted" style={{ fontSize: '0.75rem' }}>
                  Menampilkan <strong className="text-dark">{data?.products?.total || 0}</strong> produk
                </small>
              </div>
            </div>
          </form>

          {/* Master Product Table matching Laravel structure */}
          <div className="table-responsive rounded border mt-3">
            <table className="table table-sm table-striped table-bordered align-middle mb-0" style={{ fontSize: '0.78rem' }}>
              <thead className="bg-light">
                <tr>
                  <th className="text-center" style={{ width: '40px' }}>
                    <input type="checkbox" className="form-check-input" onChange={toggleSelectAll} checked={selectedProducts.length > 0 && selectedProducts.length === productsList.length} />
                  </th>
                  <th style={{ minWidth: '150px' }}>SKU VARIASI</th>
                  <th style={{ minWidth: '320px' }}>NAMA BARANG</th>
                  <th className="text-end" style={{ minWidth: '160px' }}>HARGA (NORMAL / DROPSHIP)</th>
                  <th className="text-center" style={{ minWidth: '80px' }}>STOK</th>
                  <th className="text-center" style={{ minWidth: '110px' }}>STATUS & PO</th>
                  <th style={{ minWidth: '140px' }}>MARKETPLACE</th>
                  <th className="text-center" style={{ width: '85px' }}>AKSI</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="8" className="text-center py-5 text-muted">
                      <RotateCw size={20} className="spin me-2 text-primary" /> Memuat data master produk...
                    </td>
                  </tr>
                ) : productsList.length > 0 ? (
                  productsList.map((prod) => {
                    const isLowStock = prod.stock <= prod.min_stock;
                    return (
                      <tr key={prod.id} style={{ borderLeft: `4px solid ${prod.is_preorder ? '#8b5cf6' : '#22c55e'}` }}>
                        <td className="text-center">
                          <input
                            type="checkbox"
                            className="form-check-input"
                            checked={selectedProducts.includes(prod.id)}
                            onChange={() => toggleSelectProduct(prod.id)}
                          />
                        </td>
                        <td>
                          <code className="text-primary font-monospace" style={{ fontSize: '0.75rem' }}>{prod.sku}</code>
                        </td>
                        <td>
                          <div className="d-flex align-items-center gap-2">
                            {prod.image_url ? (
                              <img src={prod.image_url} alt={prod.name} className="rounded border" style={{ width: '40px', height: '40px', objectFit: 'cover' }} />
                            ) : (
                              <div className="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style={{ width: '40px', height: '40px', flexShrink: 0 }}>
                                <Box size={16} />
                              </div>
                            )}
                            <div>
                              <div className="fw-bold text-dark text-decoration-none" style={{ fontSize: '0.8rem' }}>
                                {prod.name}
                              </div>
                              <div className="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                {prod.is_bundle ? (
                                  <span className="badge text-white px-2 py-0.5 fw-bold" style={{ backgroundColor: '#ec4899', fontSize: '0.68rem' }} title="Produk Bundling / Paket">
                                    <Boxes size={10} className="me-1" />BUNDLE / SET
                                  </span>
                                ) : (
                                  <span className="badge px-2 py-0.5 fw-bold" style={{ backgroundColor: '#e0f2fe', color: '#0369a1', border: '1px solid #bae6fd', fontSize: '0.68rem' }}>
                                    <Box size={10} className="me-1" />Single
                                  </span>
                                )}

                                {prod.is_preorder ? (
                                  <span className="badge text-white px-2 py-0.5 fw-bold" style={{ backgroundColor: '#8b5cf6', fontSize: '0.68rem' }}>
                                    <Clock size={10} className="me-1" />PO ({prod.preorder_days} Hari)
                                  </span>
                                ) : (
                                  <span className="badge px-2 py-0.5 fw-bold" style={{ backgroundColor: '#dcfce7', color: '#15803d', border: '1px solid #86efac', fontSize: '0.68rem' }}>
                                    <Bolt size={10} className="me-1" />Ready Stock
                                  </span>
                                )}
                                {prod.sub_kategori && (
                                  <small className="text-muted ms-1" style={{ fontSize: '0.68rem' }}>{prod.sub_kategori}</small>
                                )}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="text-end">
                          <div className="lh-sm small" style={{ fontSize: '0.73rem' }}>
                            <div>
                              <span className="text-muted">HPP:</span> <span className="font-monospace text-muted">{prod.cost_price > 0 ? `Rp ${new Intl.NumberFormat('id-ID').format(prod.cost_price)}` : '—'}</span>
                            </div>
                            <div className="mt-0.5">
                              <span className="text-muted">Normal:</span> <strong className="font-monospace text-primary">Rp {new Intl.NumberFormat('id-ID').format(prod.price || 0)}</strong>
                            </div>
                            <div className="mt-0.5">
                              <span className="text-muted">Dropship:</span> <strong className="font-monospace text-success">{prod.reseller_price > 0 ? `Rp ${new Intl.NumberFormat('id-ID').format(prod.reseller_price)}` : '—'}</strong>
                            </div>
                          </div>
                        </td>
                        <td className="text-center">
                          <span
                            className="badge text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold font-monospace"
                            style={{ width: '22px', height: '22px', backgroundColor: isLowStock ? '#ef4444' : '#10b981', fontSize: '0.7rem' }}
                          >
                            {prod.stock}
                          </span>
                          {isLowStock && (
                            <div className="text-danger mt-1 fw-bold text-uppercase" style={{ fontSize: '0.62rem' }}>stok rendah</div>
                          )}
                        </td>
                        <td className="text-center">
                          <div className="d-flex flex-column align-items-center gap-1">
                            <span className={`badge ${prod.is_active ? 'bg-success' : 'bg-secondary'}`} style={{ fontSize: '0.68rem' }}>
                              {prod.is_active ? 'Aktif' : 'Nonaktif'}
                            </span>
                            {prod.is_preorder ? (
                              <span className="badge text-white px-2 py-0.5" style={{ backgroundColor: '#8b5cf6', fontSize: '0.65rem' }}>
                                <Clock size={9} className="me-1" />PO ({prod.preorder_days} Hari)
                              </span>
                            ) : (
                              <span className="badge px-2 py-0.5" style={{ backgroundColor: '#dcfce7', color: '#15803d', border: '1px solid #86efac', fontSize: '0.65rem' }}>
                                <CheckCircle2 size={9} className="me-1" />Ready
                              </span>
                            )}
                          </div>
                        </td>
                        <td>
                          {prod.marketplace_stores && prod.marketplace_stores.length > 0 ? (
                            <div className="d-flex flex-wrap gap-1">
                              {prod.marketplace_stores.map((st, i) => (
                                <span key={i} className="badge text-white fw-bold px-2 py-1" style={{ backgroundColor: '#f97316', fontSize: '0.65rem', borderRadius: '4px' }}>
                                  <Store size={10} className="me-1" />{st.store_name.toUpperCase()}
                                </span>
                              ))}
                            </div>
                          ) : (
                            <span className="badge bg-secondary text-white rounded-pill" style={{ fontSize: '0.65rem' }}>
                              Belum Terhubung
                            </span>
                          )}
                        </td>
                        <td className="text-center">
                          <div className="d-flex align-items-center justify-content-center gap-1">
                            <button className="btn btn-warning btn-sm p-1 text-dark" style={{ width: '26px', height: '26px' }} title="Edit Produk">
                              <Edit size={12} />
                            </button>
                            <button className="btn btn-primary btn-sm p-1" style={{ width: '26px', height: '26px' }} title="Publish ke Marketplace">
                              <UploadCloud size={12} />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan="8" className="text-center py-5 text-muted">
                      <div className="py-3">
                        <Box size={32} className="d-block mx-auto text-secondary mb-2 opacity-50" />
                        <p className="mb-1 fw-bold text-dark" style={{ fontSize: '0.88rem' }}>Belum ada data produk terdaftar</p>
                        <p className="text-muted small mb-0">Silakan tambahkan produk baru atau ubah kata kunci pencarian Anda.</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Produk;


