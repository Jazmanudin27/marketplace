import React, { useState, useEffect } from 'react';
import { 
  Target, Users, Award, TrendingUp, CheckCircle2, Store, Lock, Calendar, 
  Filter, RefreshCw, ShoppingBag, Plus, Pencil, Trash2, X, Check, 
  AlertCircle, ChevronRight, ToggleLeft, ToggleRight
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { 
  fetchTargetKomisi, 
  createTargetKomisi, 
  updateTargetKomisi, 
  deleteTargetKomisi, 
  toggleTargetKomisi 
} from '../api/metricsApi';

const MONTH_NAMES = [
  'Semua Bulan',
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function TargetKomisiPage({ initialMetrics }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedMonth, setSelectedMonth] = useState('');
  const [selectedYear, setSelectedYear] = useState('');

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('create'); // 'create' | 'edit'
  const [currentTeamId, setCurrentTeamId] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [actionMessage, setActionMessage] = useState(null); // { type: 'success' | 'error', text: '' }

  // Form State
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    description: '',
    target_omset: '',
    commission_type: 'percentage',
    commission_rate: '1.5',
    reward_fixed_nominal: '0',
    target_qty: '0',
    reward_per_qty: '0',
    period_month: new Date().getMonth() + 1,
    period_year: new Date().getFullYear(),
    date_from: '',
    date_to: '',
    store_ids: [],
    is_active: true,
  });

  const loadData = async (m = selectedMonth, y = selectedYear) => {
    setLoading(true);
    const res = await fetchTargetKomisi(m || null, y || null);
    if (res && res.success) {
      setData(res);
    }
    setLoading(false);
  };

  useEffect(() => {
    loadData(selectedMonth, selectedYear);
  }, [selectedMonth, selectedYear]);

  // Helper Auto Date Range
  const calculateDateRange = (m, y) => {
    const month = parseInt(m, 10);
    const year = parseInt(y, 10);
    if (!month || !year) return { from: '', to: '' };
    const from = `${year}-${String(month).padStart(2, '0')}-01`;
    const lastDay = new Date(year, month, 0).getDate();
    const to = `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    return { from, to };
  };

  // Open Create Modal
  const handleOpenCreate = () => {
    const now = new Date();
    const m = selectedMonth ? parseInt(selectedMonth, 10) : now.getMonth() + 1;
    const y = selectedYear ? parseInt(selectedYear, 10) : now.getFullYear();
    const { from, to } = calculateDateRange(m, y);

    setFormData({
      name: '',
      code: '',
      description: '',
      target_omset: '',
      commission_type: 'percentage',
      commission_rate: '1.5',
      reward_fixed_nominal: '0',
      target_qty: '0',
      reward_per_qty: '0',
      period_month: m,
      period_year: y,
      date_from: from,
      date_to: to,
      store_ids: [],
      is_active: true,
    });
    setModalMode('create');
    setCurrentTeamId(null);
    setIsModalOpen(true);
  };

  // Open Edit Modal
  const handleOpenEdit = (team) => {
    const m = team.periodMonth || (new Date().getMonth() + 1);
    const y = team.periodYear || (new Date().getFullYear());
    const fallbackDates = calculateDateRange(m, y);

    setFormData({
      name: team.name || '',
      code: team.code || '',
      description: team.description || '',
      target_omset: team.targetMargin !== undefined ? String(team.targetMargin) : '',
      commission_type: team.commissionType || 'percentage',
      commission_rate: team.commissionRate !== undefined ? String(team.commissionRate) : '1.5',
      reward_fixed_nominal: team.rewardFixedNominal !== undefined ? String(team.rewardFixedNominal) : '0',
      target_qty: team.targetQty !== undefined ? String(team.targetQty) : '0',
      reward_per_qty: team.rewardPerQty !== undefined ? String(team.rewardPerQty) : '0',
      period_month: m,
      period_year: y,
      date_from: team.dateFrom || fallbackDates.from,
      date_to: team.dateTo || fallbackDates.to,
      store_ids: (team.stores || []).map(s => s.id),
      is_active: Boolean(team.isActive),
    });
    setModalMode('edit');
    setCurrentTeamId(team.id);
    setIsModalOpen(true);
  };

  // Handle Form Change with Month/Year Sync
  const handlePeriodMonthChange = (e) => {
    const m = parseInt(e.target.value, 10);
    const y = formData.period_year;
    const { from, to } = calculateDateRange(m, y);
    setFormData(prev => ({
      ...prev,
      period_month: m,
      date_from: from,
      date_to: to,
    }));
  };

  const handlePeriodYearChange = (e) => {
    const y = parseInt(e.target.value, 10);
    const m = formData.period_month;
    const { from, to } = calculateDateRange(m, y);
    setFormData(prev => ({
      ...prev,
      period_year: y,
      date_from: from,
      date_to: to,
    }));
  };

  // Toggle Store Selection
  const toggleStoreSelection = (storeId) => {
    setFormData(prev => {
      const exists = prev.store_ids.includes(storeId);
      return {
        ...prev,
        store_ids: exists 
          ? prev.store_ids.filter(id => id !== storeId)
          : [...prev.store_ids, storeId]
      };
    });
  };

  // Select All Stores
  const selectAllStores = () => {
    const allIds = (data?.availableStores || []).map(s => s.id);
    setFormData(prev => ({ ...prev, store_ids: allIds }));
  };

  const deselectAllStores = () => {
    setFormData(prev => ({ ...prev, store_ids: [] }));
  };

  // Submit Modal Form
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      alert('Nama Tim Marketing wajib diisi.');
      return;
    }
    if (!formData.target_omset || parseFloat(formData.target_omset) < 0) {
      alert('Target Margin (Rp) wajib diisi.');
      return;
    }

    setSubmitting(true);
    let res;
    if (modalMode === 'create') {
      res = await createTargetKomisi(formData);
    } else {
      res = await updateTargetKomisi(currentTeamId, formData);
    }
    setSubmitting(false);

    if (res && res.success) {
      setIsModalOpen(false);
      setActionMessage({ type: 'success', text: res.message || 'Data tim berhasil disimpan!' });
      setTimeout(() => setActionMessage(null), 3500);
      loadData(selectedMonth, selectedYear);
    } else {
      alert(res?.message || 'Gagal menyimpan target komisi. Periksa kembali form Anda.');
    }
  };

  // Handle Delete Team
  const handleDeleteTeam = async (team) => {
    if (!window.confirm(`Hapus Tim "${team.name}" beserta pengaturannya?`)) return;
    const res = await deleteTargetKomisi(team.id);
    if (res && res.success) {
      setActionMessage({ type: 'success', text: res.message || 'Tim berhasil dihapus!' });
      setTimeout(() => setActionMessage(null), 3500);
      loadData(selectedMonth, selectedYear);
    } else {
      alert(res?.message || 'Gagal menghapus tim.');
    }
  };

  // Handle Toggle Active
  const handleToggleTeam = async (team) => {
    const res = await toggleTargetKomisi(team.id);
    if (res && res.success) {
      loadData(selectedMonth, selectedYear);
    } else {
      alert(res?.message || 'Gagal mengubah status tim.');
    }
  };

  const summary = data?.summary || {
    totalTargetMargin: initialMetrics?.targetMonthlyMargin || 0,
    totalTargetQty: 0,
    totalActualMargin: initialMetrics?.actualMonthlyMargin || 0,
    totalActualQty: 0,
    totalReward: initialMetrics?.totalEarnedCommission || 0,
    progressPercent: initialMetrics?.targetProgressPercent || 0,
    totalTeams: 0,
    activeTeamsCount: 0,
    totalStoresLinked: 0,
    totalStoresCount: 0,
    availableYears: [new Date().getFullYear()],
  };

  const teams = data?.teams || [];
  const availableStores = data?.availableStores || [];
  const availableYears = summary.availableYears || [new Date().getFullYear()];

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* Toast Alert Message */}
      {actionMessage && (
        <div style={{
          background: actionMessage.type === 'success' ? '#10b981' : '#ef4444',
          color: '#ffffff',
          borderRadius: '12px',
          padding: '10px 14px',
          fontSize: '12px',
          fontWeight: '700',
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
        }}>
          <CheckCircle2 size={16} />
          <span>{actionMessage.text}</span>
        </div>
      )}

      {/* Filter Card Compact */}
      <div style={{
        background: '#ffffff',
        borderRadius: '16px',
        padding: '12px 14px',
        border: '1px solid #e2e8f0',
        display: 'flex',
        flexDirection: 'column',
        gap: '10px',
        boxShadow: '0 2px 5px rgba(0,0,0,0.02)'
      }}>
        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
          {/* Dropdown Bulan */}
          <div style={{ flex: 1 }}>
            <select
              value={selectedMonth}
              onChange={(e) => setSelectedMonth(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 10px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11.5px',
                fontWeight: '600',
                color: '#1e293b',
                background: '#f8fafc',
                outline: 'none'
              }}
            >
              {MONTH_NAMES.map((name, idx) => (
                <option key={idx} value={idx === 0 ? '' : idx}>
                  {name}
                </option>
              ))}
            </select>
          </div>

          {/* Dropdown Tahun */}
          <div style={{ width: '100px' }}>
            <select
              value={selectedYear}
              onChange={(e) => setSelectedYear(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 10px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11.5px',
                fontWeight: '600',
                color: '#1e293b',
                background: '#f8fafc',
                outline: 'none'
              }}
            >
              <option value="">Semua Tahun</option>
              {availableYears.map((yr) => (
                <option key={yr} value={yr}>
                  {yr}
                </option>
              ))}
            </select>
          </div>

          {/* Refresh / Reset Button */}
          {(selectedMonth || selectedYear) ? (
            <button
              type="button"
              onClick={() => { setSelectedMonth(''); setSelectedYear(''); }}
              style={{
                border: 'none',
                background: '#fee2e2',
                color: '#ef4444',
                padding: '7px 10px',
                borderRadius: '10px',
                fontSize: '11.5px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
              title="Reset Filter"
            >
              Reset
            </button>
          ) : (
            <button
              type="button"
              onClick={() => loadData()}
              style={{
                border: 'none',
                background: '#f1f5f9',
                color: '#475569',
                padding: '7px 10px',
                borderRadius: '10px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center'
              }}
              title="Refresh Data"
            >
              <RefreshCw size={14} className={loading ? 'spin-anim' : ''} />
            </button>
          )}
        </div>

        {/* Filter Status Badge (hanya muncul saat ada filter bulan/tahun aktif) */}
        {(selectedMonth || selectedYear) && (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            fontSize: '10.5px',
            padding: '6px 10px',
            borderRadius: '8px',
            background: '#eff6ff',
            color: '#1d4ed8',
            fontWeight: '600'
          }}>
            <Calendar size={13} />
            <span>
              Filter Aktif: {selectedMonth ? MONTH_NAMES[parseInt(selectedMonth)] : 'Semua Bulan'} {selectedYear || ''}
            </span>
          </div>
        )}
      </div>

      {/* KPI Grid Widgets: Tim Marketing & Toko Terhubung */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '10px'
      }}>
        {/* Widget 1: Tim Marketing */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          gap: '4px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>Tim Marketing</span>
            <Users size={16} color="#3b82f6" />
          </div>
          <div style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>
            {summary.totalTeams} <small style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Tim</small>
          </div>
          <span style={{ fontSize: '10px', color: '#059669', fontWeight: '700' }}>
            {summary.activeTeamsCount} Tim Aktif
          </span>
        </div>

        {/* Widget 2: Toko Terhubung */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          gap: '4px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>Toko Terhubung</span>
            <Store size={16} color="#10b981" />
          </div>
          <div style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>
            {summary.totalStoresLinked} <small style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Toko</small>
          </div>
          <span style={{ fontSize: '10px', color: '#64748b' }}>
            Dari {summary.totalStoresCount || summary.totalStoresLinked} Toko Terdaftar
          </span>
        </div>
      </div>

      {/* Header List Tim + Action Tambah Target */}
      <div>
        <div style={{ 
          display: 'flex', 
          justifyContent: 'space-between', 
          alignItems: 'center', 
          marginBottom: '10px' 
        }}>
          <div className="section-title-wrap" style={{ margin: 0 }}>
            <h3 className="section-title" style={{ margin: 0 }}>
              <span>👥 Daftar Tim & Target ERP</span>
            </h3>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600', marginLeft: '6px' }}>
              ({teams.length})
            </span>
          </div>

          {/* Button Tambah Target */}
          <button
            type="button"
            onClick={handleOpenCreate}
            style={{
              background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
              color: '#ffffff',
              border: 'none',
              padding: '6px 12px',
              borderRadius: '10px',
              fontSize: '11.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              gap: '5px',
              cursor: 'pointer',
              boxShadow: '0 2px 8px rgba(37,99,235,0.25)'
            }}
          >
            <Plus size={14} />
            <span>Tambah Tim</span>
          </button>
        </div>

        {loading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[1, 2, 3].map((i) => (
              <div key={i} style={{ background: '#ffffff', borderRadius: '18px', padding: '16px', border: '1px solid #e2e8f0' }}>
                <div className="skeleton-shimmer skeleton-text" style={{ width: '130px', height: '16px', marginBottom: '8px' }} />
                <div className="skeleton-shimmer skeleton-text" style={{ width: '100%', height: '12px' }} />
              </div>
            ))}
          </div>
        ) : teams.length === 0 ? (
          <div style={{ 
            background: '#ffffff', 
            borderRadius: '18px', 
            padding: '30px 20px', 
            textAlign: 'center', 
            border: '1px solid #e2e8f0', 
            display: 'flex', 
            flexDirection: 'column', 
            alignItems: 'center', 
            gap: '10px' 
          }}>
            <Target size={36} color="#cbd5e1" />
            <div style={{ color: '#64748b', fontSize: '13px', fontWeight: '600' }}>
              Belum ada Tim Marketing untuk filter ini.
            </div>
            <button
              type="button"
              onClick={handleOpenCreate}
              style={{
                background: '#2563eb',
                color: '#ffffff',
                border: 'none',
                padding: '8px 16px',
                borderRadius: '10px',
                fontSize: '12px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
            >
              + Buat Target Tim Pertama
            </button>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {teams.map((team, idx) => {
              const actMargin = team.actualMargin || 0;
              const targetMargin = team.targetMargin || 0;
              const pct = team.progressPercent || 0;
              const totRew = team.rewardEarned || 0;
              const cType = team.commissionType || 'percentage';

              let barColor = '#ef4444';
              if (pct >= 100) barColor = '#10b981';
              else if (pct >= 50) barColor = '#f59e0b';

              return (
                <div 
                  key={team.id || idx}
                  style={{
                    background: '#ffffff',
                    borderRadius: '18px',
                    padding: '16px',
                    border: '1px solid #e2e8f0',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.03)',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px'
                  }}
                >
                  {/* Top Bar: # Number, Team Name, Period Lock Badge, Status & Actions */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
                    <div style={{ flex: 1 }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap', marginBottom: '3px' }}>
                        <span style={{
                          background: '#f1f5f9',
                          color: '#475569',
                          fontSize: '11px',
                          fontWeight: '800',
                          padding: '2px 7px',
                          borderRadius: '6px',
                          border: '1px solid #e2e8f0'
                        }}>
                          #{idx + 1}
                        </span>
                        <h4 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                          {team.name}
                        </h4>
                        <span style={{
                          fontSize: '10px',
                          background: '#eff6ff',
                          color: '#1d4ed8',
                          padding: '2px 8px',
                          borderRadius: '999px',
                          fontWeight: '700',
                          border: '1px solid #bfdbfe',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '3px'
                        }}>
                          <Lock size={10} /> {team.periodLabel || 'Semua Periode'}
                        </span>
                      </div>
                      {team.description && (
                        <p style={{ fontSize: '11px', color: '#64748b', margin: 0, marginTop: '2px' }}>
                          {team.description}
                        </p>
                      )}
                    </div>

                    {/* Action Buttons: Toggle, Edit, Delete */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                      {/* Toggle Active Badge */}
                      <button
                        type="button"
                        onClick={() => handleToggleTeam(team)}
                        style={{
                          border: 'none',
                          background: team.isActive ? '#ecfdf5' : '#f1f5f9',
                          color: team.isActive ? '#059669' : '#64748b',
                          padding: '3px 8px',
                          borderRadius: '8px',
                          fontSize: '10px',
                          fontWeight: '800',
                          cursor: 'pointer'
                        }}
                        title="Klik untuk ubah status aktif/nonaktif"
                      >
                        {team.isActive ? 'Aktif' : 'Nonaktif'}
                      </button>

                      {/* Edit Button */}
                      <button
                        type="button"
                        onClick={() => handleOpenEdit(team)}
                        style={{
                          border: 'none',
                          background: '#eff6ff',
                          color: '#2563eb',
                          width: '28px',
                          height: '28px',
                          borderRadius: '8px',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          cursor: 'pointer'
                        }}
                        title="Edit Tim & Target"
                      >
                        <Pencil size={13} />
                      </button>

                      {/* Delete Button */}
                      <button
                        type="button"
                        onClick={() => handleDeleteTeam(team)}
                        style={{
                          border: 'none',
                          background: '#fee2e2',
                          color: '#ef4444',
                          width: '28px',
                          height: '28px',
                          borderRadius: '8px',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          cursor: 'pointer'
                        }}
                        title="Hapus Tim"
                      >
                        <Trash2 size={13} />
                      </button>
                    </div>
                  </div>

                  {/* Badges Toko Terhubung (Shopee / TikTok / Tokopedia) */}
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px' }}>
                    {team.stores && team.stores.length > 0 ? (
                      team.stores.map((s) => {
                        const chName = (s.channel || '').toLowerCase();
                        let badgeBg = '#f1f5f9';
                        let badgeColor = '#475569';
                        let icon = '🏪';
                        if (chName.includes('shopee')) {
                          badgeBg = '#fff1f2';
                          badgeColor = '#e11d48';
                          icon = '🛍️';
                        } else if (chName.includes('tiktok')) {
                          badgeBg = '#0f172a';
                          badgeColor = '#ffffff';
                          icon = '🎵';
                        } else if (chName.includes('tokopedia')) {
                          badgeBg = '#f0fdf4';
                          badgeColor = '#16a34a';
                          icon = '🏬';
                        }
                        return (
                          <span key={s.id} style={{ 
                            fontSize: '10px', 
                            background: badgeBg, 
                            color: badgeColor, 
                            padding: '2px 8px', 
                            borderRadius: '999px',
                            fontWeight: '600'
                          }}>
                            {icon} {s.name}
                          </span>
                        );
                      })
                    ) : (
                      <span style={{ fontSize: '10px', color: '#94a3b8', fontStyle: 'italic' }}>
                        Belum ada toko dihubungkan
                      </span>
                    )}
                  </div>

                  {/* 2-Column Key Metrics Box (Persis Tabel ERP V2) */}
                  <div style={{
                    display: 'grid',
                    gridTemplateColumns: '1fr 1fr',
                    gap: '8px',
                    background: '#f8fafc',
                    padding: '10px 12px',
                    borderRadius: '14px',
                    border: '1px solid #e2e8f0'
                  }}>
                    {/* Kolom 1: Target Margin (Rp) */}
                    <div>
                      <span style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                        Target Margin (Rp)
                      </span>
                      <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                        {formatRupiah(targetMargin)}
                      </div>
                    </div>

                    {/* Kolom 2: Skema Komisi */}
                    <div>
                      <span style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                        Skema Komisi
                      </span>
                      <div style={{ fontSize: '13px', fontWeight: '800', color: '#2563eb', marginTop: '2px' }}>
                        {cType === 'percentage' 
                          ? `${Number(team.commissionRate || 0).toFixed(2)}%`
                          : cType === 'nominal'
                          ? formatRupiah(team.rewardFixedNominal)
                          : `${formatRupiah(team.rewardPerQty)}/qty`}
                      </div>
                      <span style={{ fontSize: '9.5px', color: '#64748b' }}>
                        {cType === 'percentage' ? 'dari Margin (Rp)' : cType === 'nominal' ? 'saat Capai Target' : 'per Qty Produk'}
                      </span>
                    </div>

                    {/* Total Komisi Banner (Full Width) */}
                    <div style={{
                      gridColumn: 'span 2',
                      background: '#ecfdf5',
                      border: '1px solid #a7f3d0',
                      borderRadius: '10px',
                      padding: '8px 10px',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                      marginTop: '2px'
                    }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                        <Award size={14} color="#059669" />
                        <span style={{ fontSize: '11px', fontWeight: '800', color: '#065f46' }}>
                          Total Komisi:
                        </span>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '14px', fontWeight: '800', color: '#047857' }}>
                          {formatRupiah(totRew)}
                        </div>
                        <div style={{ fontSize: '9.5px', color: '#059669' }}>
                          {cType === 'percentage' 
                            ? `(${Number(team.commissionRate || 0).toFixed(2)}% × ${formatRupiah(actMargin)})`
                            : cType === 'nominal'
                            ? (actMargin >= targetMargin ? 'Target Tercapai' : 'Belum Capai Target')
                            : `(${team.actualQty} Qty × ${formatRupiah(team.rewardPerQty)})`}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Realisasi & Progress Margin Bar */}
                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11px', marginBottom: '4px' }}>
                      <span style={{ color: '#64748b', fontWeight: '600' }}>
                        Realisasi Margin: <strong style={{ color: '#0f172a' }}>{formatRupiah(actMargin)}</strong>
                      </span>
                      <span style={{
                        fontWeight: '800',
                        fontSize: '11px',
                        color: barColor,
                        background: '#f8fafc',
                        border: '1px solid #e2e8f0',
                        padding: '1px 7px',
                        borderRadius: '999px'
                      }}>
                        {pct}%
                      </span>
                    </div>
                    <div style={{ width: '100%', height: '8px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden' }}>
                      <div style={{ 
                        width: `${Math.min(100, pct)}%`, 
                        height: '100%', 
                        background: barColor, 
                        borderRadius: '999px',
                        transition: 'width 0.4s ease'
                      }} />
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10px', color: '#94a3b8', marginTop: '4px' }}>
                      <span>Target: {formatRupiah(targetMargin)}</span>
                      <span>Qty: {team.actualQty?.toLocaleString('id-ID')} / {team.targetQty?.toLocaleString('id-ID') || 0} pcs</span>
                    </div>
                  </div>

                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* ─── MODAL INPUT / EDIT TARGET KOMISI (FULL BOTTOM SHEET) ─── */}
      {isModalOpen && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(3px)',
          zIndex: 9999,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'flex-end',
          padding: '0'
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '520px',
            maxHeight: '90vh',
            borderTopLeftRadius: '24px',
            borderTopRightRadius: '24px',
            overflowY: 'auto',
            padding: '20px 18px',
            display: 'flex',
            flexDirection: 'column',
            gap: '16px',
            boxShadow: '0 -10px 25px rgba(0,0,0,0.2)',
            animation: 'slideUp 0.25s ease-out'
          }}>
            {/* Modal Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid #f1f5f9', paddingBottom: '12px' }}>
              <div>
                <h3 style={{ margin: 0, fontSize: '16px', fontWeight: '800', color: '#0f172a' }}>
                  {modalMode === 'create' ? 'Tambah Tim Marketing & Target' : 'Edit Tim Marketing & Target'}
                </h3>
                <span style={{ fontSize: '11px', color: '#64748b' }}>
                  Sinkron langsung dengan ERP Marketplace
                </span>
              </div>
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                style={{
                  border: 'none',
                  background: '#f1f5f9',
                  color: '#64748b',
                  width: '32px',
                  height: '32px',
                  borderRadius: '50%',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer'
                }}
              >
                <X size={18} />
              </button>
            </div>

            {/* Modal Form */}
            <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
              
              {/* Field 1: Nama Tim Marketing */}
              <div>
                <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '5px' }}>
                  Nama Tim Marketing <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Tim Ruang, Tim Hijab"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '9px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    color: '#0f172a',
                    outline: 'none',
                    boxSizing: 'border-box'
                  }}
                />
              </div>

              {/* Field 2: Periode Bulan & Tahun Bawaan */}
              <div>
                <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '5px' }}>
                  <Calendar size={12} style={{ display: 'inline', marginRight: '4px' }} />
                  Bulan & Tahun Target
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr', gap: '8px' }}>
                  <select
                    value={formData.period_month}
                    onChange={handlePeriodMonthChange}
                    style={{
                      padding: '9px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      fontWeight: '600',
                      color: '#0f172a',
                      background: '#f8fafc',
                      outline: 'none'
                    }}
                  >
                    {MONTH_NAMES.slice(1).map((mName, i) => (
                      <option key={i + 1} value={i + 1}>{mName}</option>
                    ))}
                  </select>
                  <input
                    type="number"
                    value={formData.period_year}
                    onChange={handlePeriodYearChange}
                    placeholder="Tahun"
                    style={{
                      padding: '9px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      fontWeight: '600',
                      color: '#0f172a',
                      outline: 'none',
                      boxSizing: 'border-box'
                    }}
                  />
                </div>
              </div>

              {/* Field 3: Tanggal Dana Cair (Dari - Sampai) */}
              <div>
                <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '5px' }}>
                  <Lock size={12} style={{ display: 'inline', marginRight: '4px', color: '#059669' }} />
                  Tanggal Dana Cair (Dari – Sampai) <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                  <input
                    type="date"
                    required
                    value={formData.date_from}
                    onChange={(e) => setFormData({ ...formData, date_from: e.target.value })}
                    style={{
                      padding: '8px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '11.5px',
                      color: '#0f172a',
                      outline: 'none',
                      boxSizing: 'border-box'
                    }}
                  />
                  <input
                    type="date"
                    required
                    value={formData.date_to}
                    onChange={(e) => setFormData({ ...formData, date_to: e.target.value })}
                    style={{
                      padding: '8px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '11.5px',
                      color: '#0f172a',
                      outline: 'none',
                      boxSizing: 'border-box'
                    }}
                  />
                </div>
                <span style={{ fontSize: '10px', color: '#64748b', marginTop: '3px', display: 'block' }}>
                  Acuan tanggal pencairan (<code>completed_at</code>) pesanan tim di ERP.
                </span>
              </div>

              {/* Field 4: Target Margin (Rp) */}
              <div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '5px' }}>
                  <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155' }}>
                    Target Margin (Rp) <span style={{ color: '#ef4444' }}>*</span>
                  </label>
                  <span style={{ fontSize: '11px', color: '#0284c7', fontWeight: '700' }}>
                    {formData.target_omset ? formatRupiah(formData.target_omset) : 'Rp 0'}
                  </span>
                </div>
                <input
                  type="number"
                  min="0"
                  step="1000"
                  required
                  placeholder="Contoh: 50000000"
                  value={formData.target_omset}
                  onChange={(e) => setFormData({ ...formData, target_omset: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '9px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    fontWeight: '700',
                    color: '#0f172a',
                    outline: 'none',
                    boxSizing: 'border-box'
                  }}
                />
                <span style={{ fontSize: '10px', color: '#64748b', marginTop: '3px', display: 'block' }}>
                  Target margin rupiah (Nilai Dilepas – HPP) pesanan selesai.
                </span>
              </div>

              {/* Field 5: Tipe Skema Komisi */}
              <div>
                <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  Tipe Skema Komisi
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '6px' }}>
                  <button
                    type="button"
                    onClick={() => setFormData({ ...formData, commission_type: 'percentage' })}
                    style={{
                      border: formData.commission_type === 'percentage' ? '2px solid #2563eb' : '1px solid #cbd5e1',
                      background: formData.commission_type === 'percentage' ? '#eff6ff' : '#f8fafc',
                      color: formData.commission_type === 'percentage' ? '#1d4ed8' : '#475569',
                      padding: '8px',
                      borderRadius: '10px',
                      fontSize: '11px',
                      fontWeight: '700',
                      cursor: 'pointer',
                      textAlign: 'center'
                    }}
                  >
                    % Margin
                  </button>
                  <button
                    type="button"
                    onClick={() => setFormData({ ...formData, commission_type: 'nominal' })}
                    style={{
                      border: formData.commission_type === 'nominal' ? '2px solid #2563eb' : '1px solid #cbd5e1',
                      background: formData.commission_type === 'nominal' ? '#eff6ff' : '#f8fafc',
                      color: formData.commission_type === 'nominal' ? '#1d4ed8' : '#475569',
                      padding: '8px',
                      borderRadius: '10px',
                      fontSize: '11px',
                      fontWeight: '700',
                      cursor: 'pointer',
                      textAlign: 'center'
                    }}
                  >
                    Bonus Flat Target
                  </button>
                </div>
              </div>

              {/* Conditional Inputs based on commission_type */}
              {formData.commission_type === 'percentage' && (
                <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '12px', border: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                      Persentase Komisi (%)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      value={formData.commission_rate}
                      onChange={(e) => setFormData({ ...formData, commission_rate: e.target.value })}
                      placeholder="Contoh: 1.5"
                      style={{
                        width: '100%',
                        padding: '8px 10px',
                        borderRadius: '8px',
                        border: '1px solid #cbd5e1',
                        fontSize: '12px',
                        fontWeight: '700',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                    <span style={{ fontSize: '9.5px', color: '#64748b' }}>% dihitung dari total Margin (Rp) tercapai.</span>
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                      Bonus Tambahan Capai Target (Rp) (Opsional)
                    </label>
                    <input
                      type="number"
                      step="1000"
                      min="0"
                      value={formData.reward_fixed_nominal}
                      onChange={(e) => setFormData({ ...formData, reward_fixed_nominal: e.target.value })}
                      placeholder="0"
                      style={{
                        width: '100%',
                        padding: '8px 10px',
                        borderRadius: '8px',
                        border: '1px solid #cbd5e1',
                        fontSize: '12px',
                        fontWeight: '700',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                  </div>
                </div>
              )}

              {formData.commission_type === 'nominal' && (
                <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
                  <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                    Bonus Flat Saat Target Margin Tercapai (Rp)
                  </label>
                  <input
                    type="number"
                    step="1000"
                    min="0"
                    value={formData.reward_fixed_nominal}
                    onChange={(e) => setFormData({ ...formData, reward_fixed_nominal: e.target.value })}
                    placeholder="Contoh: 1000000"
                    style={{
                      width: '100%',
                      padding: '8px 10px',
                      borderRadius: '8px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      fontWeight: '700',
                      color: '#0f172a',
                      boxSizing: 'border-box'
                    }}
                  />
                </div>
              )}

              {/* Field 6: Pilih Toko Terhubung */}
              <div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
                  <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155' }}>
                    Pilih Toko Terhubung ({formData.store_ids.length} dipilih)
                  </label>
                  <div style={{ display: 'flex', gap: '8px' }}>
                    <button
                      type="button"
                      onClick={selectAllStores}
                      style={{ border: 'none', background: 'none', color: '#2563eb', fontSize: '10.5px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      Pilih Semua
                    </button>
                    <button
                      type="button"
                      onClick={deselectAllStores}
                      style={{ border: 'none', background: 'none', color: '#ef4444', fontSize: '10.5px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      Reset
                    </button>
                  </div>
                </div>

                <div style={{
                  maxHeight: '130px',
                  overflowY: 'auto',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '8px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '6px',
                  background: '#f8fafc'
                }}>
                  {availableStores.length === 0 ? (
                    <div style={{ fontSize: '11px', color: '#94a3b8', textAlign: 'center', padding: '10px' }}>
                      Tidak ada toko marketplace terdaftar.
                    </div>
                  ) : (
                    availableStores.map(st => {
                      const isChecked = formData.store_ids.includes(st.id);
                      return (
                        <div
                          key={st.id}
                          onClick={() => toggleStoreSelection(st.id)}
                          style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: '6px 10px',
                            borderRadius: '8px',
                            background: isChecked ? '#eff6ff' : '#ffffff',
                            border: isChecked ? '1px solid #93c5fd' : '1px solid #e2e8f0',
                            cursor: 'pointer'
                          }}
                        >
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <div style={{
                              width: '16px',
                              height: '16px',
                              borderRadius: '4px',
                              border: isChecked ? 'none' : '1px solid #cbd5e1',
                              background: isChecked ? '#2563eb' : '#ffffff',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: '#ffffff'
                            }}>
                              {isChecked && <Check size={12} strokeWidth={3} />}
                            </div>
                            <span style={{ fontSize: '12px', fontWeight: isChecked ? '700' : '500', color: '#0f172a' }}>
                              {st.name}
                            </span>
                          </div>
                          <span style={{ fontSize: '9.5px', color: '#64748b', background: '#f1f5f9', padding: '1px 6px', borderRadius: '4px' }}>
                            {st.channel}
                          </span>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>

              {/* Field 7: Catatan / Deskripsi */}
              <div>
                <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                  Catatan / Keterangan (Opsional)
                </label>
                <input
                  type="text"
                  placeholder="Catatan tambahan untuk tim ini"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '12px',
                    color: '#0f172a',
                    outline: 'none',
                    boxSizing: 'border-box'
                  }}
                />
              </div>

              {/* Field 8: Status Aktif */}
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 12px', background: '#f8fafc', borderRadius: '10px' }}>
                <span style={{ fontSize: '12px', fontWeight: '700', color: '#334155' }}>
                  Status Tim Aktif
                </span>
                <button
                  type="button"
                  onClick={() => setFormData({ ...formData, is_active: !formData.is_active })}
                  style={{
                    border: 'none',
                    background: formData.is_active ? '#ecfdf5' : '#fee2e2',
                    color: formData.is_active ? '#059669' : '#ef4444',
                    padding: '4px 10px',
                    borderRadius: '8px',
                    fontSize: '11px',
                    fontWeight: '800',
                    cursor: 'pointer'
                  }}
                >
                  {formData.is_active ? '✓ Aktif' : '✗ Nonaktif'}
                </button>
              </div>

              {/* Submit Buttons */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.6fr', gap: '8px', marginTop: '6px' }}>
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  style={{
                    border: '1px solid #cbd5e1',
                    background: '#ffffff',
                    color: '#475569',
                    padding: '10px',
                    borderRadius: '12px',
                    fontSize: '12.5px',
                    fontWeight: '700',
                    cursor: 'pointer'
                  }}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  style={{
                    border: 'none',
                    background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
                    color: '#ffffff',
                    padding: '10px',
                    borderRadius: '12px',
                    fontSize: '12.5px',
                    fontWeight: '800',
                    cursor: 'pointer',
                    boxShadow: '0 3px 10px rgba(37,99,235,0.3)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px'
                  }}
                >
                  {submitting ? (
                    <span>Menyimpan...</span>
                  ) : (
                    <span>{modalMode === 'create' ? '✓ Simpan Target Baru' : '✓ Perbarui Target'}</span>
                  )}
                </button>
              </div>

            </form>
          </div>
        </div>
      )}

    </div>
  );
}
