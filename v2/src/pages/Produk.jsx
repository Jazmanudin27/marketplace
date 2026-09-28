import React, { useEffect, useState } from 'react';
import { fetchProdukData } from '../services/api';
import { Box, Plus, Search, RotateCw, Edit, Trash2 } from 'lucide-react';

const Produk = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  const loadData = async (query = '') => {
    setLoading(true);
    const res = await fetchProdukData({ search: query });
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

  return (
    <div>
      {/* Page Header Compact (Exact Match to Image 2) */}
      <div className="v2-page-header align-items-start">
        <div>
          <h1 className="v2-page-title d-flex align-items-center gap-2">
            <Box size={18} className="text-primary" /> Data Master Produk & Barang
          </h1>
          <p className="v2-page-subtitle">Total {data?.products?.total || 0} produk terdaftar dalam sistem V2</p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <button className="btn-v2-primary">
            <Plus size={14} /> Tambah Produk Baru
          </button>
        </div>
      </div>

      {/* Filter Bar Compact */}
      <div className="v2-card mb-3 p-2">
        <form onSubmit={handleSearchSubmit} className="row g-2 align-items-center">
          <div className="col-md-7">
            <div className="position-relative">
              <Search size={14} className="position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" />
              <input
                type="text"
                className="form-control form-control-sm ps-4"
                placeholder="Cari berdasarkan nama produk, SKU, atau barcode..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
          </div>
          <div className="col-md-3">
            <select className="form-select form-select-sm">
              <option value="">Semua Status</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <div className="col-md-2 d-flex gap-1 justify-content-end">
            <button type="submit" className="btn btn-sm btn-v2-primary py-1 px-3">Filter</button>
            <button type="button" onClick={() => { setSearch(''); loadData(''); }} className="btn btn-sm btn-v2-secondary py-1 px-2" title="Refresh">
              <RotateCw size={14} />
            </button>
          </div>
        </form>
      </div>

      {/* Data Table Compact (Matching Reference Image 2) */}
      <div className="v2-card">
        <div className="v2-table-responsive">
          <table className="v2-table">
            <thead>
              <tr>
                <th className="text-center" style={{ width: '50px' }}>NO</th>
                <th>NAMA PRODUK & SKU</th>
                <th>KATEGORI / BRAND</th>
                <th className="text-end">HARGA MODAL (HPP)</th>
                <th className="text-end">HARGA JUAL</th>
                <th className="text-center">STOK</th>
                <th className="text-center">STATUS</th>
                <th className="text-center" style={{ width: '80px' }}>AKSI</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan="8" className="text-center py-4 text-muted">
                    <RotateCw size={18} className="spin me-2" /> Memuat data produk...
                  </td>
                </tr>
              ) : (
                data?.products?.data?.map((prod, idx) => (
                  <tr key={prod.id}>
                    <td className="text-center text-muted fw-bold">{idx + 1}</td>
                    <td>
                      <div className="fw-bold text-dark">{prod.name}</div>
                      <div className="text-muted" style={{ fontSize: '0.725rem' }}>SKU: {prod.sku || '-'}</div>
                    </td>
                    <td>
                      <div className="fw-semibold text-body">{prod.category?.name || '-'}</div>
                      <div className="text-muted" style={{ fontSize: '0.725rem' }}>{prod.brand?.name || '-'}</div>
                    </td>
                    <td className="text-end text-muted">Rp {new Intl.NumberFormat('id-ID').format(prod.cost_price || 0)}</td>
                    <td className="text-end fw-bold text-primary">Rp {new Intl.NumberFormat('id-ID').format(prod.price || 0)}</td>
                    <td className="text-center">
                      <span className={`badge ${prod.stock <= 5 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle'}`} style={{ fontSize: '0.68rem' }}>
                        {prod.stock} {prod.unit || 'pcs'}
                      </span>
                    </td>
                    <td className="text-center">
                      <span className="text-success fw-semibold" style={{ fontSize: '0.75rem' }}>
                        {prod.is_active ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="text-center">
                      <div className="d-flex align-items-center justify-content-center gap-1">
                        <button className="btn-action-icon btn-action-edit" title="Edit">
                          <Edit size={12} />
                        </button>
                        <button className="btn-action-icon btn-action-delete" title="Hapus">
                          <Trash2 size={12} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default Produk;
