import React, { useEffect, useState } from 'react';
import { fetchDashboardData } from '../services/api';
import { Grid, DollarSign, ShoppingCart, Package, Store, Eye, RefreshCw, ArrowUpRight } from 'lucide-react';

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const loadData = async () => {
    setLoading(true);
    const res = await fetchDashboardData();
    setData(res);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <RefreshCw size={24} className="spin mb-2" />
        <div>Memuat data dashboard...</div>
      </div>
    );
  }

  return (
    <div>
      {/* Page Header Compact */}
      <div className="v2-page-header">
        <div>
          <h1 className="v2-page-title d-flex align-items-center gap-2">
            <Grid size={18} className="text-primary" /> Dashboard Overview V2 (React JS)
          </h1>
          <p className="v2-page-subtitle">Aplikasi V2 berdiri sendiri sebagai Single Page Application (SPA) berbasis API yang ultra cepat.</p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <a href="/dashboard" className="btn-v2-secondary">
            <ArrowUpRight size={14} /> Mode ERP V1
          </a>
          <button className="btn-v2-primary" onClick={loadData}>
            <RefreshCw size={14} /> Refresh Data
          </button>
        </div>
      </div>

      {/* Stat Cards Grid Compact */}
      <div className="row g-2 mb-3">
        <div className="col-xl-3 col-sm-6">
          <div className="v2-card p-3 d-flex align-items-center justify-content-between">
            <div>
              <div className="text-muted fw-bold" style={{ fontSize: '0.68rem' }}>TOTAL OMSET BULAN INI</div>
              <div className="fw-bold text-primary" style={{ fontSize: '1.25rem' }}>
                Rp {new Intl.NumberFormat('id-ID').format(data?.totalOmset || 0)}
              </div>
              <small className="text-success fw-semibold" style={{ fontSize: '0.68rem' }}>+12.5% vs bulan lalu</small>
            </div>
            <div className="rounded p-2 bg-primary bg-opacity-10 text-primary">
              <DollarSign size={20} />
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="v2-card p-3 d-flex align-items-center justify-content-between">
            <div>
              <div className="text-muted fw-bold" style={{ fontSize: '0.68rem' }}>TOTAL PESANAN</div>
              <div className="fw-bold text-dark" style={{ fontSize: '1.25rem' }}>
                {new Intl.NumberFormat('id-ID').format(data?.totalOrdersCount || 0)} Order
              </div>
              <small className="text-success fw-semibold" style={{ fontSize: '0.68rem' }}>+8.2% minggu ini</small>
            </div>
            <div className="rounded p-2 bg-success bg-opacity-10 text-success">
              <ShoppingCart size={20} />
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="v2-card p-3 d-flex align-items-center justify-content-between">
            <div>
              <div className="text-muted fw-bold" style={{ fontSize: '0.68rem' }}>PERLU DIPROSES</div>
              <div className="fw-bold text-warning" style={{ fontSize: '1.25rem' }}>
                {data?.pendingOrdersCount || 0}
              </div>
              <small className="text-warning fw-semibold" style={{ fontSize: '0.68rem' }}>Siap Packing</small>
            </div>
            <div className="rounded p-2 bg-warning bg-opacity-10 text-warning">
              <Package size={20} />
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="v2-card p-3 d-flex align-items-center justify-content-between">
            <div>
              <div className="text-muted fw-bold" style={{ fontSize: '0.68rem' }}>TOKO TERHUBUNG</div>
              <div className="fw-bold text-dark" style={{ fontSize: '1.25rem' }}>
                {data?.totalStoresCount || 0} Toko
              </div>
              <small className="text-info fw-semibold" style={{ fontSize: '0.68rem' }}>Shopee, TikTok, Lazada</small>
            </div>
            <div className="rounded p-2 bg-info bg-opacity-10 text-info">
              <Store size={20} />
            </div>
          </div>
        </div>
      </div>

      {/* Recent Orders Compact Table */}
      <div className="v2-card">
        <div className="p-2 border-bottom d-flex align-items-center justify-content-between">
          <h6 className="fw-bold m-0" style={{ fontSize: '0.85rem' }}>Pesanan Terbaru</h6>
          <a href="/orders" className="text-primary text-decoration-none fw-semibold" style={{ fontSize: '0.75rem' }}>Lihat Semua &rarr;</a>
        </div>
        <div className="v2-table-responsive">
          <table className="v2-table">
            <thead>
              <tr>
                <th>CHANNEL & ORDER ID</th>
                <th>TANGGAL</th>
                <th>NAMA PEMBELI</th>
                <th className="text-end">TOTAL BELANJA</th>
                <th className="text-center">STATUS</th>
                <th className="text-center" style={{ width: '60px' }}>AKSI</th>
              </tr>
            </thead>
            <tbody>
              {data?.recentOrders?.map((order) => (
                <tr key={order.id}>
                  <td>
                    <div className="d-flex align-items-center gap-2">
                      <span className={`badge ${order.channel === 'shopee' ? 'bg-danger' : order.channel === 'tiktok' ? 'bg-dark' : 'bg-primary'}`} style={{ fontSize: '0.65rem' }}>
                        {order.channel}
                      </span>
                      <span className="fw-bold text-dark">{order.order_number}</span>
                    </div>
                  </td>
                  <td className="text-muted" style={{ fontSize: '0.75rem' }}>{order.created_at}</td>
                  <td>
                    <div className="fw-semibold">{order.buyer_name}</div>
                    <small className="text-muted" style={{ fontSize: '0.7rem' }}>{order.store?.name || '-'}</small>
                  </td>
                  <td className="text-end fw-bold text-primary">Rp {new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}</td>
                  <td className="text-center">
                    <span className="badge bg-success-subtle text-success border border-success-subtle" style={{ fontSize: '0.68rem' }}>
                      {order.status}
                    </span>
                  </td>
                  <td className="text-center">
                    <button className="btn-action-icon btn-action-edit" title="Detail">
                      <Eye size={12} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
