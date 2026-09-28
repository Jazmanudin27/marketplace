import React, { useEffect, useState } from 'react';
import { fetchProdukData } from '../services/api';
import { Box, Plus, Search, RotateCw, Edit, Trash2, Boxes, CheckCircle2, AlertTriangle, Clock, Filter, X } from 'lucide-react';

const Produk = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [brandId, setBrandId] = useState('');
  const [status, setStatus] = useState('');

  const loadData = async (query = search) => {
    setLoading(true);
    const res = await fetchProdukData({ search: query, category_id: categoryId, brand_id: brandId, status });
    setData(res);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    loadData(search);
  };

  const handleReset = () => {
    setSearch('');
    setCategoryId('');
    setBrandId('');
    setStatus('');
    loadData('');
  };

  const totalCount = data?.products?.total || data?.products?.data?.length || 0;
  const activeCount = data?.stats?.active || data?.products?.data?.filter(p => p.is_active).length || 0;
  const lowStockCount = data?.stats?.low_stock || data?.products?.data?.filter(p => p.stock <= (p.min_stock || 5)).length || 0;
  const preorderCount = data?.stats?.preorder || data?.products?.data?.filter(p => p.is_preorder).length || 0;

  return (
    <div>
      {/* Page Header Compact */}
      <div className="v2-page-header align-items-center mb-3">
        <div>
          <h1 className="v2-page-title d-flex align-items-center gap-2" style={{ fontSize: '1.15rem', fontWeight: '700' }}>
            <Box size={20} className="text-primary" /> Data Master Produk & Barang
          </h1>
          <p className="v2-page-subtitle mb-0">Kelola katalog produk, SKU, kategori, brand, harga HPP, dan stok barang</p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <button onClick={() => loadData()} className="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Data">
            <RotateCw size={14} />
          </button>
          <button className="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm">
            <Plus size={14} className="me-1" /> Tambah Produk Baru
          </button>
        </div>
      </div>

      {/* Metric Summary Cards */}
      <div className="row g-2 mb-3">
        <div className="col-6 col-md-3">
          <div className="v2-stat-widget">
            <div className="v2-stat-icon-wrapper blue">
              <Boxes size={18} />
            </div>
            <div className="v2-stat-info">
              <span className="v2-stat-num">{totalCount}</span>
              <span className="v2-stat-lbl">Total Produk</span>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="v2-stat-widget">
            <div className="v2-stat-icon-wrapper green">
              <CheckCircle2 size={18} />
            </div>
            <div className="v2-stat-info">
              <span className="v2-stat-num">{activeCount}</span>
              <span className="v2-stat-lbl">Produk Aktif</span>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="v2-stat-widget">
            <div className="v2-stat-icon-wrapper amber">
              <AlertTriangle size={18} />
            </div>
            <div className="v2-stat-info">
              <span className="v2-stat-num">{lowStockCount}</span>
              <span className="v2-stat-lbl">Stok Menipis / Out</span>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="v2-stat-widget">
            <div className="v2-stat-icon-wrapper purple">
              <Clock size={18} />
            </div>
            <div className="v2-stat-info">
              <span className="v2-stat-num">{preorderCount}</span>
              <span className="v2-stat-lbl">Pre-Order System</span>
            </div>
          </div>
        </div>
      </div>

      {/* Filter Bar Compact */}
      <div className="v2-card mb-3">
        <div className="v2-card-body p-2.5">
          <form onSubmit={handleSearchSubmit} className="row g-2 align-items-center">
            <div className="col-md-4">
              <div className="position-relative">
                <Search size={14} className="position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted" />
                <input
                  type="text"
                  className="form-control form-control-sm ps-4"
                  placeholder="Cari nama produk, SKU, barcode..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-md-2.5 col-6">
              <select className="form-select form-select-sm" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
                <option value="">Semua Kategori</option>
                {data?.categories?.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
            <div className="col-md-2.5 col-6">
              <select className="form-select form-select-sm" value={brandId} onChange={(e) => setBrandId(e.target.value)}>
                <option value="">Semua Brand</option>
                {data?.brands?.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
              </select>
            </div>
            <div className="col-md-1.5 col-6">
              <select className="form-select form-select-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
                <option value="">Status</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
              </select>
            </div>
            <div className="col-md-1.5 col-6 d-flex gap-1 justify-content-end">
              <button type="submit" className="btn btn-sm btn-v2-primary py-1 px-3 w-100">
                <Filter size={12} className="me-1" /> Filter
              </button>
              <button type="button" onClick={handleReset} className="btn btn-sm btn-v2-secondary py-1 px-2" title="Reset Filter">
                <X size={14} />
              </button>
            </div>
          </form>
        </div>
      </div>

      {/* Data Table Compact */}
      <div className="v2-card shadow-sm border">
        <div className="v2-card-body p-0">
          <div className="v2-table-responsive">
            <table className="v2-table align-middle">
              <thead>
                <tr>
                  <th className="text-center" style={{ width: '45px' }}>NO</th>
                  <th>INFORMASI PRODUK & SKU</th>
                  <th style={{ minWidth: '130px' }}>KATEGORI & BRAND</th>
                  <th className="text-end" style={{ minWidth: '120px' }}>HPP (MODAL)</th>
                  <th className="text-end" style={{ minWidth: '120px' }}>HARGA JUAL</th>
                  <th className="text-center" style={{ minWidth: '90px' }}>STOK</th>
                  <th className="text-center" style={{ minWidth: '85px' }}>STATUS</th>
                  <th className="text-center" style={{ width: '80px' }}>AKSI</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="8" className="text-center py-5 text-muted">
                      <RotateCw size={20} className="spin me-2 text-primary" /> Memuat data produk...
                    </td>
                  </tr>
                ) : data?.products?.data?.length > 0 ? (
                  data.products.data.map((prod, idx) => (
                    <tr key={prod.id}>
                      <td className="text-center text-muted fw-bold" style={{ fontSize: '0.75rem' }}>{idx + 1}</td>
                      <td>
                        <div className="d-flex align-items-center gap-2.5">
                          {prod.image ? (
                            <img src={`/storage/${prod.image}`} className="rounded border" style={{ width: '34px', height: '34px', objectFit: 'cover' }} alt={prod.name} />
                          ) : (
                            <div className="product-avatar-icon">
                              <Box size={16} />
                            </div>
                          )}
                          <div className="overflow-hidden">
                            <div className="fw-bold text-dark text-truncate" style={{ maxWidth: '380px', fontSize: '0.8rem' }} title={prod.name}>
                              {prod.name}
                            </div>
                            <div className="d-flex align-items-center gap-1 mt-0.5">
                              <span className="sku-badge">{prod.sku || '-'}</span>
                              {prod.is_preorder && (
                                <span className="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style={{ fontSize: '0.62rem' }}>PO</span>
                              )}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="mb-0.5">
                          <span className="badge bg-light text-dark border fw-medium" style={{ fontSize: '0.7rem' }}>
                            {prod.category?.name || 'Tanpa Kategori'}
                          </span>
                        </div>
                        <div className="text-muted" style={{ fontSize: '0.7rem' }}>
                          {prod.brand?.name || '-'}
                        </div>
                      </td>
                      <td className="text-end">
                        <span className="text-muted" style={{ fontSize: '0.72rem' }}>Rp</span>
                        <span className="fw-medium text-secondary ms-1" style={{ fontSize: '0.78rem' }}>{new Intl.NumberFormat('id-ID').format(prod.cost_price || 0)}</span>
                      </td>
                      <td className="text-end">
                        <span className="text-primary fw-bold" style={{ fontSize: '0.82rem' }}>Rp {new Intl.NumberFormat('id-ID').format(prod.price || 0)}</span>
                      </td>
                      <td className="text-center">
                        <span className={`badge ${prod.stock <= 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : prod.stock <= (prod.min_stock || 5) ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle'} px-2 py-1`} style={{ fontSize: '0.68rem' }}>
                          {prod.stock} {prod.unit || 'pcs'}
                        </span>
                      </td>
                      <td className="text-center">
                        <span className={`badge ${prod.is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'} px-2 py-0.5`} style={{ fontSize: '0.7rem' }}>
                          <span className={`status-dot ${prod.is_active ? 'active' : 'inactive'}`}></span>
                          {prod.is_active ? 'Aktif' : 'Nonaktif'}
                        </span>
                      </td>
                      <td className="text-center">
                        <div className="d-flex align-items-center justify-content-center gap-1">
                          <button className="btn-action-icon btn-action-edit" title="Edit Produk">
                            <Edit size={12} />
                          </button>
                          <button className="btn-action-icon btn-action-delete" title="Hapus Produk">
                            <Trash2 size={12} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
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

