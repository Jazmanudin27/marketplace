import React, { useEffect, useState } from 'react';
import { fetchDashboardData } from '../services/api';
import { 
  User, 
  UserX, 
  Users, 
  Sparkles, 
  RotateCw, 
  ListFilter,
  CheckCircle,
  Clock,
  ShoppingCart
} from 'lucide-react';

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedMonth, setSelectedMonth] = useState('September');
  const [selectedYear, setSelectedYear] = useState('2026');

  const loadData = async () => {
    setLoading(true);
    const res = await fetchDashboardData();
    setData(res);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  return (
    <div>
      {/* Page Title: Dashboard (Exact Match) */}
      <h2 className="v2-page-title-simple">Dashboard</h2>

      {/* Top 4 Stat Cards Gradient (Exact Match to Screenshot) */}
      <div className="row g-3 mb-4">
        {/* Card 1: Blue (Aktif) */}
        <div className="col-xl-3 col-sm-6">
          <div className="stat-card-gradient stat-card-blue">
            <div className="d-flex align-items-center justify-content-between">
              <span className="stat-badge-pill">
                <span className="stat-badge-dot"></span> Aktif
              </span>
              <div className="stat-icon-topright">
                <User size={16} />
              </div>
            </div>
            <div className="stat-number-box">
              <span className="stat-number-large">
                {data?.totalOrdersCount ?? 158}
              </span>
              <span className="stat-number-unit">Pesanan</span>
            </div>
            <User className="stat-watermark-icon" />
          </div>
        </div>

        {/* Card 2: Orange (Perlu Diproses / Siap Kirim) */}
        <div className="col-xl-3 col-sm-6">
          <div className="stat-card-gradient stat-card-orange">
            <div className="d-flex align-items-center justify-content-between">
              <span className="stat-badge-pill">
                <span className="stat-badge-dot"></span> Perlu Diproses
              </span>
              <div className="stat-icon-topright">
                <Clock size={16} />
              </div>
            </div>
            <div className="stat-number-box">
              <span className="stat-number-large">
                {data?.pendingOrdersCount ?? 0}
              </span>
              <span className="stat-number-unit">Siap Packing</span>
            </div>
            <Clock className="stat-watermark-icon" />
          </div>
        </div>

        {/* Card 3: Green (Selesai) */}
        <div className="col-xl-3 col-sm-6">
          <div className="stat-card-gradient stat-card-green">
            <div className="d-flex align-items-center justify-content-between">
              <span className="stat-badge-pill">
                <span className="stat-badge-dot"></span> Toko Aktif
              </span>
              <div className="stat-icon-topright">
                <Users size={16} />
              </div>
            </div>
            <div className="stat-number-box">
              <span className="stat-number-large">
                {data?.totalStoresCount ?? 57}
              </span>
              <span className="stat-number-unit">Toko</span>
            </div>
            <Users className="stat-watermark-icon" />
          </div>
        </div>

        {/* Card 4: Rose/Red (Omset) */}
        <div className="col-xl-3 col-sm-6">
          <div className="stat-card-gradient stat-card-rose">
            <div className="d-flex align-items-center justify-content-between">
              <span className="stat-badge-pill">
                <span className="stat-badge-dot"></span> Total Omset
              </span>
              <div className="stat-icon-topright">
                <Sparkles size={16} />
              </div>
            </div>
            <div className="stat-number-box">
              <span className="stat-number-large" style={{ fontSize: '1.45rem' }}>
                Rp {new Intl.NumberFormat('id-ID').format(data?.shopeeOmset || data?.totalOmset || 630000)}
              </span>
            </div>
            <Sparkles className="stat-watermark-icon" />
          </div>
        </div>
      </div>

      {/* Rekap Section Container (Exact Match to Screenshot) */}
      <div className="v2-card p-3">
        {/* Title Centered */}
        <div className="text-center mb-3">
          <h6 className="fw-bold text-dark m-0" style={{ letterSpacing: '0.8px', fontSize: '0.9rem' }}>
            REKAP PERFORMA TOKO & PESANAN MARKETPLACE
          </h6>
        </div>

        {/* Filter Controls: Month, Year, Refresh (Exact Match) */}
        <div className="d-flex align-items-center gap-2 mb-3">
          <select 
            className="form-select form-select-sm" 
            style={{ width: '140px', fontSize: '0.78rem' }}
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(e.target.value)}
          >
            <option value="Januari">Januari</option>
            <option value="Februari">Februari</option>
            <option value="Maret">Maret</option>
            <option value="April">April</option>
            <option value="Mei">Mei</option>
            <option value="Juni">Juni</option>
            <option value="Juli">Juli</option>
            <option value="Agustus">Agustus</option>
            <option value="September">September</option>
            <option value="Oktober">Oktober</option>
            <option value="November">November</option>
            <option value="Desember">Desember</option>
          </select>

          <select 
            className="form-select form-select-sm" 
            style={{ width: '100px', fontSize: '0.78rem' }}
            value={selectedYear}
            onChange={(e) => setSelectedYear(e.target.value)}
          >
            <option value="2025">2025</option>
            <option value="2026">2026</option>
            <option value="2027">2027</option>
          </select>

          <button 
            type="button" 
            onClick={loadData} 
            className="btn btn-sm btn-outline-secondary py-1 px-2 rounded-2" 
            title="Refresh Data"
          >
            <RotateCw size={13} className={loading ? 'spin' : ''} />
          </button>
        </div>

        {/* Multi-level Hierarchical Table (Exact Match to Screenshot Table Structure) */}
        <div className="table-responsive">
          <table className="v2-table-rekap">
            <thead>
              {/* Header Level 1 */}
              <tr>
                <th colSpan="3" style={{ borderBottom: '1px solid #cbd5e1' }}>Data Toko</th>
                <th colSpan="3" style={{ borderBottom: '1px solid #cbd5e1' }}>Jumlah Pesanan</th>
                <th colSpan="3" style={{ borderBottom: '1px solid #cbd5e1' }}>Omset Penjualan</th>
                <th rowSpan="2" style={{ width: '70px' }}>Detail</th>
              </tr>
              {/* Header Level 2 */}
              <tr>
                <th style={{ width: '45px' }}>No</th>
                <th>Nama Toko</th>
                <th>Channel</th>
                <th>Perlu Kirim</th>
                <th>Selesai</th>
                <th>Total Order</th>
                <th>Gross</th>
                <th>Potongan</th>
                <th>Net Omset</th>
              </tr>
            </thead>
            <tbody>
              {/* Row 1: Shopee */}
              <tr>
                <td>1</td>
                <td className="text-start fw-bold text-dark ps-3">Shopee Toko A-1</td>
                <td><span className="badge bg-danger">Shopee</span></td>
                <td>{data?.pendingOrdersCount ?? 2}</td>
                <td>2</td>
                <td className="fw-bold">{data?.shopeeOrdersCount ?? 4}</td>
                <td>Rp 650.000</td>
                <td>Rp 20.000</td>
                <td className="fw-bold text-success">Rp 630.000</td>
                <td>
                  <button className="btn-action-icon-blue" title="Rincian Pesanan">
                    <ListFilter size={13} />
                  </button>
                </td>
              </tr>

              {/* Row 2: TikTok Shop */}
              <tr>
                <td>2</td>
                <td className="text-start fw-bold text-dark ps-3">Ruang Seragam TikTok</td>
                <td><span className="badge bg-dark">TikTok</span></td>
                <td>0</td>
                <td>{data?.tiktokOrdersCount ?? 0}</td>
                <td className="fw-bold">{data?.tiktokOrdersCount ?? 0}</td>
                <td>Rp 0</td>
                <td>Rp 0</td>
                <td className="fw-bold text-muted">Rp 0</td>
                <td>
                  <button className="btn-action-icon-blue" title="Rincian Pesanan">
                    <ListFilter size={13} />
                  </button>
                </td>
              </tr>

              {/* Row 3: Lazada */}
              <tr>
                <td>3</td>
                <td className="text-start fw-bold text-dark ps-3">Artanita Lazada Store</td>
                <td><span className="badge bg-primary">Lazada</span></td>
                <td>0</td>
                <td>{data?.lazadaOrdersCount ?? 0}</td>
                <td className="fw-bold">{data?.lazadaOrdersCount ?? 0}</td>
                <td>Rp 0</td>
                <td>Rp 0</td>
                <td className="fw-bold text-muted">Rp 0</td>
                <td>
                  <button className="btn-action-icon-blue" title="Rincian Pesanan">
                    <ListFilter size={13} />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
