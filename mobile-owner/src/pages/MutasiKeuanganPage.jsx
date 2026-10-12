import React, { useState, useEffect } from 'react';
import { 
  ArrowDownLeft, 
  ArrowUpRight, 
  ArrowLeftRight, 
  Wallet, 
  Calendar, 
  Filter, 
  Search, 
  Plus, 
  Minus, 
  RefreshCw, 
  Trash2, 
  CheckCircle2, 
  X, 
  Building2, 
  User, 
  FileText,
  TrendingUp,
  TrendingDown,
  Layers,
  ChevronDown
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { showAlert, showToast as swalToast } from '../utils/alerts';
import { 
  fetchMutasiKeuangan, 
  createMutationIncome, 
  createMutationExpense, 
  createMutationTransfer, 
  deleteMutation 
} from '../api/metricsApi';

// Helper to translate english/system categories to Indonesian
function formatCategoryLabel(raw) {
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
    'transfer internal': 'Transfer Internal',
    'transfer masuk': 'Transfer Masuk',
    'transfer keluar': 'Transfer Keluar',
  };
  if (map[clean]) return map[clean];
  return String(raw).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Format pemisah ribuan (1000000 -> 1.000.000)
function formatThousand(val) {
  if (val === null || val === undefined || val === '') return '';
  const clean = String(val).replace(/[^0-9]/g, '');
  if (!clean) return '';
  return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export default function MutasiKeuanganPage() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  // Filters
  const now = new Date();
  const curYear = now.getFullYear();
  const curMonth = String(now.getMonth() + 1).padStart(2, '0');
  const defaultDateFrom = `${curYear}-${curMonth}-01`;
  const defaultDateTo = `${curYear}-${curMonth}-${String(now.getDate()).padStart(2, '0')}`;

  const [dateFrom, setDateFrom] = useState(defaultDateFrom);
  const [dateTo, setDateTo] = useState(defaultDateTo);
  const [account, setAccount] = useState('all');
  const [direction, setDirection] = useState('all'); // 'all' | 'in' | 'out'
  const [search, setSearch] = useState('');
  const [isFilterExpanded, setIsFilterExpanded] = useState(false);

  // Modals: 'income' | 'expense' | 'transfer' | null
  const [activeModal, setActiveModal] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);

  // Form states
  const [incomeForm, setIncomeForm] = useState({
    title: '',
    category: 'Pemasukan Lain',
    payment_destination: '',
    amount: '',
    income_date: defaultDateTo,
    description: ''
  });

  const [expenseForm, setExpenseForm] = useState({
    title: '',
    category: 'Operasional',
    payment_source: '',
    employee_id: '',
    amount: '',
    expense_date: defaultDateTo,
    description: ''
  });

  const [transferForm, setTransferForm] = useState({
    source: '',
    destination: '',
    amount: '',
    transfer_date: defaultDateTo,
    description: ''
  });

  const loadData = async () => {
    setLoading(true);
    const res = await fetchMutasiKeuangan({
      dateFrom,
      dateTo,
      account,
      direction,
      search
    });
    if (res && res.success) {
      setData(res);
    }
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, [dateFrom, dateTo, account, direction]);

  useEffect(() => {
    if (data?.bankAccounts?.length > 0) {
      const firstId = String(data.bankAccounts[0].id);
      const secondId = data.bankAccounts[1] ? String(data.bankAccounts[1].id) : firstId;
      setIncomeForm(prev => (!prev.payment_destination ? { ...prev, payment_destination: firstId } : prev));
      setExpenseForm(prev => (!prev.payment_source ? { ...prev, payment_source: firstId } : prev));
      setTransferForm(prev => (!prev.source ? { ...prev, source: firstId, destination: secondId } : prev));
    }
  }, [data]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    loadData();
  };

  const showToast = (text, type = 'success') => {
    setToastMessage({ text, type });
    setTimeout(() => setToastMessage(null), 3500);
  };

  // Submit Income
  const handleSubmitIncome = async (e) => {
    e.preventDefault();
    if (!incomeForm.title || !incomeForm.amount || parseFloat(incomeForm.amount) <= 0) {
      showAlert({ icon: 'warning', title: 'Data Kurang', text: 'Judul dan nominal wajib diisi.' });
      return;
    }
    setSubmitting(true);
    const res = await createMutationIncome(incomeForm);
    setSubmitting(false);
    if (res && res.success) {
      setActiveModal(null);
      setIncomeForm({
        title: '',
        category: 'Pemasukan Lain',
        payment_destination: 'kas_besar',
        amount: '',
        income_date: defaultDateTo,
        description: ''
      });
      showToast('Pemasukan berhasil dicatat!');
      loadData();
    } else {
      showAlert({ icon: 'error', title: 'Gagal', text: res?.message || 'Gagal menyimpan pemasukan.' });
    }
  };

  // Submit Expense
  const handleSubmitExpense = async (e) => {
    e.preventDefault();
    if (!expenseForm.title || !expenseForm.amount || parseFloat(expenseForm.amount) <= 0) {
      showAlert({ icon: 'warning', title: 'Data Kurang', text: 'Judul dan nominal wajib diisi.' });
      return;
    }
    setSubmitting(true);
    const res = await createMutationExpense(expenseForm);
    setSubmitting(false);
    if (res && res.success) {
      setActiveModal(null);
      setExpenseForm({
        title: '',
        category: 'Operasional',
        payment_source: 'kas_besar',
        employee_id: '',
        amount: '',
        expense_date: defaultDateTo,
        description: ''
      });
      showToast('Pengeluaran berhasil dicatat!');
      loadData();
    } else {
      showAlert({ icon: 'error', title: 'Gagal', text: res?.message || 'Gagal menyimpan pengeluaran.' });
    }
  };

  // Submit Transfer
  const handleSubmitTransfer = async (e) => {
    e.preventDefault();
    if (!transferForm.amount || parseFloat(transferForm.amount) <= 0) {
      showAlert({ icon: 'warning', title: 'Data Kurang', text: 'Nominal transfer wajib diisi.' });
      return;
    }
    if (transferForm.source === transferForm.destination) {
      showAlert({ icon: 'warning', title: 'Akun Sama', text: 'Akun asal dan akun tujuan tidak boleh sama.' });
      return;
    }
    setSubmitting(true);
    const res = await createMutationTransfer(transferForm);
    setSubmitting(false);
    if (res && res.success) {
      setActiveModal(null);
      setTransferForm({
        source: 'kas_besar',
        destination: 'kas_kecil',
        amount: '',
        transfer_date: defaultDateTo,
        description: ''
      });
      showToast('Transfer dana berhasil dicatat!');
      loadData();
    } else {
      showAlert({ icon: 'error', title: 'Gagal', text: res?.message || 'Gagal mencatat transfer dana.' });
    }
  };

  // Handle Delete Mutation
  const handleDeleteItem = async (item) => {
    const isConfirmed = window.confirm(`Hapus transaksi ${item.reference} (${item.title || item.description})?`);
    if (!isConfirmed) return;
    const res = await deleteMutation(item.model_type, item.id);
    if (res && res.success) {
      showToast('Transaksi berhasil dihapus!');
      loadData();
    } else {
      showAlert({ icon: 'error', title: 'Gagal', text: res?.message || 'Gagal menghapus transaksi.' });
    }
  };

  const summary = data?.summary || {
    beginningBalance: 0,
    totalInflow: 0,
    totalOutflow: 0,
    netCashFlow: 0,
    endingBalance: 0,
    inflowCount: 0,
    outflowCount: 0,
    totalCount: 0
  };

  const mutations = data?.mutations || [];
  const bankAccounts = data?.bankAccounts || [];
  const incomeCategories = data?.incomeCategories || [];
  const expenseCategories = data?.expenseCategories || [];
  const employees = data?.employees || [];

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* Toast Message */}
      {toastMessage && (
        <div style={{
          background: toastMessage.type === 'success' ? '#10b981' : '#ef4444',
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
          <span>{toastMessage.text}</span>
        </div>
      )}

      {/* 3 Quick Action Action Buttons Header */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '8px' }}>
        <button
          type="button"
          onClick={() => setActiveModal('income')}
          style={{
            background: 'linear-gradient(135deg, #16a34a, #15803d)',
            color: '#ffffff',
            border: 'none',
            borderRadius: '12px',
            padding: '10px 8px',
            fontSize: '11.5px',
            fontWeight: '800',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '4px',
            cursor: 'pointer',
            boxShadow: '0 4px 10px rgba(22, 163, 74, 0.25)'
          }}
        >
          <Plus size={14} /> Pemasukan
        </button>

        <button
          type="button"
          onClick={() => setActiveModal('expense')}
          style={{
            background: 'linear-gradient(135deg, #dc2626, #b91c1c)',
            color: '#ffffff',
            border: 'none',
            borderRadius: '12px',
            padding: '10px 8px',
            fontSize: '11.5px',
            fontWeight: '800',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '4px',
            cursor: 'pointer',
            boxShadow: '0 4px 10px rgba(220, 38, 38, 0.25)'
          }}
        >
          <Minus size={14} /> Pengeluaran
        </button>

        <button
          type="button"
          onClick={() => setActiveModal('transfer')}
          style={{
            background: 'linear-gradient(135deg, #d97706, #b45309)',
            color: '#ffffff',
            border: 'none',
            borderRadius: '12px',
            padding: '10px 8px',
            fontSize: '11.5px',
            fontWeight: '800',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '4px',
            cursor: 'pointer',
            boxShadow: '0 4px 10px rgba(217, 119, 6, 0.25)'
          }}
        >
          <ArrowLeftRight size={14} /> Pindah Dana
        </button>
      </div>

      {/* Saldo Akhir Mega Card */}
      <div style={{
        background: 'linear-gradient(135deg, #0f172a, #1e293b)',
        borderRadius: '20px',
        padding: '16px 18px',
        color: '#ffffff',
        boxShadow: '0 8px 24px rgba(15, 23, 42, 0.3)',
        display: 'flex',
        flexDirection: 'column',
        gap: '10px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: '11.5px', color: '#94a3b8', fontWeight: '600' }}>
            Saldo Akhir Periode
          </span>
          <Wallet size={16} color="#38bdf8" />
        </div>

        <div style={{ fontSize: '24px', fontWeight: '800', color: '#ffffff', letterSpacing: '-0.5px' }}>
          {formatRupiah(summary.endingBalance)}
        </div>

        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: '8px',
          paddingTop: '10px',
          borderTop: '1px solid rgba(255,255,255,0.12)'
        }}>
          <div>
            <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>Saldo Awal:</span>
            <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#cbd5e1' }}>
              {formatRupiah(summary.beginningBalance)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>Arus Kas Bersih:</span>
            <div style={{ 
              fontSize: '12.5px', 
              fontWeight: '700', 
              color: summary.netCashFlow >= 0 ? '#34d399' : '#f87171' 
            }}>
              {summary.netCashFlow >= 0 ? '+' : ''}{formatRupiah(summary.netCashFlow)}
            </div>
          </div>
        </div>
      </div>

      {/* 2 Mini Summary Cards: Total Uang Masuk & Total Uang Keluar */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #bbf7d0',
          display: 'flex',
          flexDirection: 'column',
          gap: '3px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '10.5px', color: '#16a34a', fontWeight: '700' }}>Uang Masuk</span>
            <ArrowDownLeft size={15} color="#16a34a" />
          </div>
          <div style={{ fontSize: '16px', fontWeight: '800', color: '#15803d' }}>
            +{formatRupiah(summary.totalInflow)}
          </div>
          <span style={{ fontSize: '9.5px', color: '#64748b' }}>
            {summary.inflowCount} Transaksi
          </span>
        </div>

        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #fecaca',
          display: 'flex',
          flexDirection: 'column',
          gap: '3px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '10.5px', color: '#dc2626', fontWeight: '700' }}>Uang Keluar</span>
            <ArrowUpRight size={15} color="#dc2626" />
          </div>
          <div style={{ fontSize: '16px', fontWeight: '800', color: '#b91c1c' }}>
            -{formatRupiah(summary.totalOutflow)}
          </div>
          <span style={{ fontSize: '9.5px', color: '#64748b' }}>
            {summary.outflowCount} Transaksi
          </span>
        </div>
      </div>

      {/* Filter Card */}
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
        {/* Row 1: Direction Filter Segmented Tabs */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr 1fr',
          background: '#f1f5f9',
          padding: '3px',
          borderRadius: '10px',
          gap: '3px'
        }}>
          {[
            { id: 'all', label: 'Semua Mutasi' },
            { id: 'in', label: '⬇️ Masuk' },
            { id: 'out', label: '⬆️ Keluar' }
          ].map(d => (
            <button
              key={d.id}
              type="button"
              onClick={() => setDirection(d.id)}
              style={{
                border: 'none',
                background: direction === d.id ? '#ffffff' : 'transparent',
                color: direction === d.id ? '#0f172a' : '#64748b',
                fontWeight: direction === d.id ? '800' : '600',
                fontSize: '11px',
                padding: '6px 4px',
                borderRadius: '8px',
                cursor: 'pointer',
                boxShadow: direction === d.id ? '0 1px 4px rgba(0,0,0,0.08)' : 'none',
                transition: 'all 0.15s ease'
              }}
            >
              {d.label}
            </button>
          ))}
        </div>

        {/* Row 2: Search input + Toggle filter */}
        <form onSubmit={handleSearchSubmit} style={{ display: 'flex', gap: '8px' }}>
          <div style={{
            flex: 1,
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            background: '#f8fafc',
            border: '1px solid #cbd5e1',
            borderRadius: '10px',
            padding: '6px 10px'
          }}>
            <Search size={14} color="#94a3b8" />
            <input 
              type="text"
              placeholder="Cari keterangan, akun, nominal..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{
                border: 'none',
                outline: 'none',
                background: 'transparent',
                width: '100%',
                fontSize: '11.5px',
                color: '#1e293b'
              }}
            />
            {search && (
              <X size={14} color="#94a3b8" style={{ cursor: 'pointer' }} onClick={() => { setSearch(''); }} />
            )}
          </div>

          <button
            type="button"
            onClick={() => setIsFilterExpanded(!isFilterExpanded)}
            style={{
              border: '1px solid #cbd5e1',
              background: isFilterExpanded ? '#eff6ff' : '#f8fafc',
              color: isFilterExpanded ? '#2563eb' : '#475569',
              borderRadius: '10px',
              padding: '6px 10px',
              fontSize: '11px',
              fontWeight: '700',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: '4px'
            }}
          >
            <Filter size={13} />
            <span>Filter</span>
            <ChevronDown size={12} style={{ transform: isFilterExpanded ? 'rotate(180deg)' : 'none', transition: 'transform 0.2s' }} />
          </button>

          <button
            type="button"
            onClick={loadData}
            style={{
              border: '1px solid #cbd5e1',
              background: '#f8fafc',
              color: '#475569',
              borderRadius: '10px',
              padding: '6px 9px',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center'
            }}
            title="Refresh Data"
          >
            <RefreshCw size={14} className={loading ? 'spin-anim' : ''} />
          </button>
        </form>

        {/* Collapsible Date & Account Filters */}
        {isFilterExpanded && (
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            gap: '8px',
            paddingTop: '8px',
            borderTop: '1px dashed #e2e8f0'
          }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
              <div>
                <label style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '2px' }}>
                  Dari Tanggal
                </label>
                <input 
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '6px 8px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '11px',
                    outline: 'none',
                    background: '#f8fafc'
                  }}
                />
              </div>
              <div>
                <label style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '2px' }}>
                  Sampai Tanggal
                </label>
                <input 
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '6px 8px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '11px',
                    outline: 'none',
                    background: '#f8fafc'
                  }}
                />
              </div>
            </div>

            <div>
              <label style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '2px' }}>
                Akun Kas / Bank
              </label>
              <select
                value={account}
                onChange={(e) => setAccount(e.target.value)}
                style={{
                  width: '100%',
                  padding: '6px 8px',
                  borderRadius: '8px',
                  border: '1px solid #cbd5e1',
                  fontSize: '11px',
                  outline: 'none',
                  background: '#f8fafc',
                  color: '#1e293b'
                }}
              >
                <option value="all">-- Semua Akun Kas / Bank --</option>
                {bankAccounts.map(b => (
                  <option key={b.id} value={b.id}>
                    {b.bank_name} {b.account_number ? `(${b.account_number})` : ''}
                  </option>
                ))}
              </select>
            </div>
          </div>
        )}
      </div>

      {/* Mutation List Header */}
      <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
          <h3 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
            📋 Buku Mutasi Kas ({mutations.length})
          </h3>
          <span style={{ fontSize: '10.5px', color: '#64748b' }}>
            Terbaru di atas
          </span>
        </div>

        {loading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[1, 2, 3, 4].map(i => (
              <div key={i} style={{ background: '#ffffff', borderRadius: '16px', padding: '14px', border: '1px solid #e2e8f0' }}>
                <div className="skeleton-shimmer skeleton-text" style={{ width: '120px', height: '14px', marginBottom: '6px' }} />
                <div className="skeleton-shimmer skeleton-text" style={{ width: '100%', height: '12px' }} />
              </div>
            ))}
          </div>
        ) : mutations.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            padding: '30px 20px',
            textAlign: 'center',
            border: '1px solid #e2e8f0',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: '8px'
          }}>
            <Wallet size={36} color="#cbd5e1" />
            <div style={{ color: '#64748b', fontSize: '13px', fontWeight: '600' }}>
              Tidak ada data mutasi kas pada periode ini.
            </div>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {mutations.map((m, idx) => {
              const isInflow = m.inflow > 0;
              const isOutflow = m.outflow > 0;
              const isTransfer = m.type === 'transfer';

              return (
                <div
                  key={m.reference || idx}
                  style={{
                    background: '#ffffff',
                    borderRadius: '16px',
                    padding: '12px 14px',
                    border: '1px solid #e2e8f0',
                    boxShadow: '0 2px 5px rgba(0,0,0,0.02)',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '8px'
                  }}
                >
                  {/* Top Bar: Date, Ref, Badge, Delete */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                      <span style={{ fontSize: '10px', color: '#64748b', fontWeight: '600' }}>
                        {m.date_formatted}
                      </span>
                      <span style={{
                        fontSize: '9.5px',
                        background: '#f1f5f9',
                        color: '#475569',
                        padding: '1px 6px',
                        borderRadius: '6px',
                        fontWeight: '700'
                      }}>
                        {m.reference}
                      </span>
                      <span style={{
                        fontSize: '9.5px',
                        background: isInflow ? '#dcfce7' : isOutflow ? '#fee2e2' : '#fef3c7',
                        color: isInflow ? '#15803d' : isOutflow ? '#b91c1c' : '#b45309',
                        padding: '1px 7px',
                        borderRadius: '999px',
                        fontWeight: '800'
                      }}>
                        {m.type_label}
                      </span>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleDeleteItem(m)}
                      style={{
                        border: 'none',
                        background: '#fee2e2',
                        color: '#ef4444',
                        width: '24px',
                        height: '24px',
                        borderRadius: '6px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer'
                      }}
                      title="Hapus transaksi"
                    >
                      <Trash2 size={12} />
                    </button>
                  </div>

                  {/* Title & Description */}
                  <div>
                    <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                      {m.description}
                    </h4>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '3px', marginTop: '4px' }}>
                      <div style={{ display: 'flex', alignItems: 'center' }}>
                        <span style={{
                          fontSize: '10px',
                          background: '#eff6ff',
                          color: '#1d4ed8',
                          padding: '2px 7px',
                          borderRadius: '6px',
                          fontWeight: '600',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}>
                          🏦 {m.account_label}
                        </span>
                      </div>
                      <div style={{ display: 'flex', alignItems: 'center' }}>
                        <span style={{
                          fontSize: '10px',
                          color: '#64748b',
                          fontWeight: '600',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}>
                          🏷️ {formatCategoryLabel(m.category_label)}
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Nominal & Running Balance Box */}
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'baseline',
                    paddingTop: '6px',
                    borderTop: '1px dashed #f1f5f9'
                  }}>
                    <div>
                      <span style={{ fontSize: '9.5px', color: '#94a3b8' }}>Saldo Berjalan:</span>
                      <div style={{ fontSize: '11.5px', fontWeight: '700', color: '#475569' }}>
                        {formatRupiah(m.running_balance)}
                      </div>
                    </div>

                    <div style={{ textAlign: 'right' }}>
                      {isInflow && (
                        <div style={{ fontSize: '15px', fontWeight: '800', color: '#16a34a' }}>
                          +{formatRupiah(m.inflow)}
                        </div>
                      )}
                      {isOutflow && (
                        <div style={{ fontSize: '15px', fontWeight: '800', color: '#dc2626' }}>
                          -{formatRupiah(m.outflow)}
                        </div>
                      )}
                      {isTransfer && (
                        <div style={{ fontSize: '15px', fontWeight: '800', color: '#d97706' }}>
                          {formatRupiah(m.inflow || m.outflow || 0)}
                        </div>
                      )}
                    </div>
                  </div>

                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* ─── MODAL INPUT PEMASUKAN ─── */}
      {activeModal === 'income' && (
        <div style={{
          position: 'fixed',
          top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(3px)',
          zIndex: 9999,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'flex-end'
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '520px',
            maxHeight: '85vh',
            borderTopLeftRadius: '24px',
            borderTopRightRadius: '24px',
            display: 'flex',
            flexDirection: 'column',
            overflow: 'hidden'
          }}>
            <div style={{
              padding: '16px 20px',
              borderBottom: '1px solid #e2e8f0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <div style={{ background: '#dcfce7', padding: '6px', borderRadius: '10px' }}>
                  <Plus size={18} color="#16a34a" />
                </div>
                <div>
                  <h3 style={{ fontSize: '15px', fontWeight: '800', margin: 0, color: '#0f172a' }}>
                    Catat Pemasukan Kas
                  </h3>
                  <span style={{ fontSize: '11px', color: '#64748b' }}>Tambah transaksi dana masuk</span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                style={{ border: 'none', background: '#f1f5f9', width: '32px', height: '32px', borderRadius: '50%', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleSubmitIncome} style={{ padding: '16px 20px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Judul / Sumber Pemasukan *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Penerimaan Piutang, Modal Usaha, Jasa"
                  value={incomeForm.title}
                  onChange={(e) => setIncomeForm({ ...incomeForm, title: e.target.value })}
                  style={{ width: '100%', padding: '9px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '12px' }}
                />
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Nominal Pemasukan (Rp) *
                </label>
                <input
                  type="text"
                  inputMode="numeric"
                  required
                  placeholder="0"
                  value={formatThousand(incomeForm.amount)}
                  onChange={(e) => {
                    const raw = e.target.value.replace(/[^0-9]/g, '');
                    setIncomeForm({ ...incomeForm, amount: raw });
                  }}
                  style={{ 
                    width: '100%', 
                    padding: '9px 12px', 
                    borderRadius: '10px', 
                    border: '1px solid #cbd5e1', 
                    fontSize: '14px', 
                    fontWeight: '700', 
                    color: '#16a34a',
                    textAlign: 'right',
                    fontFamily: 'monospace'
                  }}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Rekening Tujuan *
                  </label>
                  <select
                    value={incomeForm.payment_destination}
                    onChange={(e) => setIncomeForm({ ...incomeForm, payment_destination: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    {bankAccounts.map(b => (
                      <option key={b.id} value={b.id}>{b.bank_name} {b.account_number ? `(${b.account_number})` : ''}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Tanggal Transaksi *
                  </label>
                  <input
                    type="date"
                    required
                    value={incomeForm.income_date}
                    onChange={(e) => setIncomeForm({ ...incomeForm, income_date: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px' }}
                  />
                </div>
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Kategori Pemasukan
                </label>
                <select
                  value={incomeForm.category}
                  onChange={(e) => setIncomeForm({ ...incomeForm, category: e.target.value })}
                  style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                >
                  <option value="Pemasukan Lain">Pemasukan Lain</option>
                  <option value="Pendapatan Jasa">Pendapatan Jasa</option>
                  <option value="Penjualan Aset">Penjualan Aset</option>
                  <option value="Bunga Bank">Bunga Bank</option>
                  {incomeCategories.map(c => (
                    <option key={c.id} value={c.name}>{c.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Keterangan Tambahan (Opsional)
                </label>
                <textarea
                  rows="2"
                  placeholder="Catatan tambahan transaksi..."
                  value={incomeForm.description}
                  onChange={(e) => setIncomeForm({ ...incomeForm, description: e.target.value })}
                  style={{ width: '100%', padding: '8px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '12px' }}
                />
              </div>

              <div style={{ display: 'flex', gap: '8px', marginTop: '6px' }}>
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  style={{ flex: 1, padding: '10px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#f1f5f9', fontWeight: '700', fontSize: '12px' }}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  style={{ flex: 2, padding: '10px', borderRadius: '10px', border: 'none', background: '#16a34a', color: '#ffffff', fontWeight: '800', fontSize: '12px' }}
                >
                  {submitting ? 'Menyimpan...' : 'Simpan Pemasukan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ─── MODAL INPUT PENGELUARAN ─── */}
      {activeModal === 'expense' && (
        <div style={{
          position: 'fixed',
          top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(3px)',
          zIndex: 9999,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'flex-end'
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '520px',
            maxHeight: '85vh',
            borderTopLeftRadius: '24px',
            borderTopRightRadius: '24px',
            display: 'flex',
            flexDirection: 'column',
            overflow: 'hidden'
          }}>
            <div style={{
              padding: '16px 20px',
              borderBottom: '1px solid #e2e8f0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <div style={{ background: '#fee2e2', padding: '6px', borderRadius: '10px' }}>
                  <Minus size={18} color="#dc2626" />
                </div>
                <div>
                  <h3 style={{ fontSize: '15px', fontWeight: '800', margin: 0, color: '#0f172a' }}>
                    Catat Pengeluaran Kas
                  </h3>
                  <span style={{ fontSize: '11px', color: '#64748b' }}>Tambah transaksi biaya / beban</span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                style={{ border: 'none', background: '#f1f5f9', width: '32px', height: '32px', borderRadius: '50%', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleSubmitExpense} style={{ padding: '16px 20px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Nama / Keperluan Biaya *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Beli Lakban, Sewa Gudang, Makan Siang"
                  value={expenseForm.title}
                  onChange={(e) => setExpenseForm({ ...expenseForm, title: e.target.value })}
                  style={{ width: '100%', padding: '9px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '12px' }}
                />
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Nominal Pengeluaran (Rp) *
                </label>
                <input
                  type="text"
                  inputMode="numeric"
                  required
                  placeholder="0"
                  value={formatThousand(expenseForm.amount)}
                  onChange={(e) => {
                    const raw = e.target.value.replace(/[^0-9]/g, '');
                    setExpenseForm({ ...expenseForm, amount: raw });
                  }}
                  style={{ 
                    width: '100%', 
                    padding: '9px 12px', 
                    borderRadius: '10px', 
                    border: '1px solid #cbd5e1', 
                    fontSize: '14px', 
                    fontWeight: '700', 
                    color: '#dc2626',
                    textAlign: 'right',
                    fontFamily: 'monospace'
                  }}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Rekening Sumber *
                  </label>
                  <select
                    value={expenseForm.payment_source}
                    onChange={(e) => setExpenseForm({ ...expenseForm, payment_source: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    {bankAccounts.map(b => (
                      <option key={b.id} value={b.id}>{b.bank_name} {b.account_number ? `(${b.account_number})` : ''}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Tanggal Transaksi *
                  </label>
                  <input
                    type="date"
                    required
                    value={expenseForm.expense_date}
                    onChange={(e) => setExpenseForm({ ...expenseForm, expense_date: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px' }}
                  />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Kategori Pengeluaran
                  </label>
                  <select
                    value={expenseForm.category}
                    onChange={(e) => setExpenseForm({ ...expenseForm, category: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    <option value="Operasional">Operasional</option>
                    <option value="Gaji / Upah">Gaji / Upah</option>
                    <option value="Packing / Lakban">Packing / Lakban</option>
                    <option value="Iklan / Ads">Iklan / Ads</option>
                    <option value="Sewa Tempat">Sewa Tempat</option>
                    <option value="Listrik / Air / Internet">Listrik / Air / Internet</option>
                    <option value="Lain Lain">Lain Lain</option>
                    {expenseCategories.map(c => (
                      <option key={c.id} value={c.name}>{formatCategoryLabel(c.name)}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    PIC / Karyawan (Opsional)
                  </label>
                  <select
                    value={expenseForm.employee_id}
                    onChange={(e) => setExpenseForm({ ...expenseForm, employee_id: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    <option value="">-- Tanpa PIC --</option>
                    {employees.map(emp => (
                      <option key={emp.id} value={emp.id}>{emp.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Keterangan Tambahan (Opsional)
                </label>
                <textarea
                  rows="2"
                  placeholder="Catatan tambahan pengeluaran..."
                  value={expenseForm.description}
                  onChange={(e) => setExpenseForm({ ...expenseForm, description: e.target.value })}
                  style={{ width: '100%', padding: '8px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '12px' }}
                />
              </div>

              <div style={{ display: 'flex', gap: '8px', marginTop: '6px' }}>
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  style={{ flex: 1, padding: '10px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#f1f5f9', fontWeight: '700', fontSize: '12px' }}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  style={{ flex: 2, padding: '10px', borderRadius: '10px', border: 'none', background: '#dc2626', color: '#ffffff', fontWeight: '800', fontSize: '12px' }}
                >
                  {submitting ? 'Menyimpan...' : 'Simpan Pengeluaran'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ─── MODAL INPUT PINDAH DANA (TRANSFER) ─── */}
      {activeModal === 'transfer' && (
        <div style={{
          position: 'fixed',
          top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(3px)',
          zIndex: 9999,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'flex-end'
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '520px',
            maxHeight: '85vh',
            borderTopLeftRadius: '24px',
            borderTopRightRadius: '24px',
            display: 'flex',
            flexDirection: 'column',
            overflow: 'hidden'
          }}>
            <div style={{
              padding: '16px 20px',
              borderBottom: '1px solid #e2e8f0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <div style={{ background: '#fef3c7', padding: '6px', borderRadius: '10px' }}>
                  <ArrowLeftRight size={18} color="#d97706" />
                </div>
                <div>
                  <h3 style={{ fontSize: '15px', fontWeight: '800', margin: 0, color: '#0f172a' }}>
                    Transfer / Pindah Dana
                  </h3>
                  <span style={{ fontSize: '11px', color: '#64748b' }}>Antar rekening kas & bank</span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setActiveModal(null)}
                style={{ border: 'none', background: '#f1f5f9', width: '32px', height: '32px', borderRadius: '50%', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleSubmitTransfer} style={{ padding: '16px 20px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Nominal Transfer (Rp) *
                </label>
                <input
                  type="text"
                  inputMode="numeric"
                  required
                  placeholder="0"
                  value={formatThousand(transferForm.amount)}
                  onChange={(e) => {
                    const raw = e.target.value.replace(/[^0-9]/g, '');
                    setTransferForm({ ...transferForm, amount: raw });
                  }}
                  style={{ 
                    width: '100%', 
                    padding: '9px 12px', 
                    borderRadius: '10px', 
                    border: '1px solid #cbd5e1', 
                    fontSize: '14px', 
                    fontWeight: '700', 
                    color: '#d97706',
                    textAlign: 'right',
                    fontFamily: 'monospace'
                  }}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Dari Rekening Asal *
                  </label>
                  <select
                    value={transferForm.source}
                    onChange={(e) => setTransferForm({ ...transferForm, source: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    {bankAccounts.map(b => (
                      <option key={b.id} value={b.id}>{b.bank_name} {b.account_number ? `(${b.account_number})` : ''}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                    Ke Rekening Tujuan *
                  </label>
                  <select
                    value={transferForm.destination}
                    onChange={(e) => setTransferForm({ ...transferForm, destination: e.target.value })}
                    style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px', background: '#ffffff' }}
                  >
                    {bankAccounts.map(b => (
                      <option key={b.id} value={b.id}>{b.bank_name} {b.account_number ? `(${b.account_number})` : ''}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Tanggal Transfer *
                </label>
                <input
                  type="date"
                  required
                  value={transferForm.transfer_date}
                  onChange={(e) => setTransferForm({ ...transferForm, transfer_date: e.target.value })}
                  style={{ width: '100%', padding: '9px 10px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '11.5px' }}
                />
              </div>

              <div>
                <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                  Keterangan (Opsional)
                </label>
                <textarea
                  rows="2"
                  placeholder="Catatan pindah dana..."
                  value={transferForm.description}
                  onChange={(e) => setTransferForm({ ...transferForm, description: e.target.value })}
                  style={{ width: '100%', padding: '8px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '12px' }}
                />
              </div>

              <div style={{ display: 'flex', gap: '8px', marginTop: '6px' }}>
                <button
                  type="button"
                  onClick={() => setActiveModal(null)}
                  style={{ flex: 1, padding: '10px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#f1f5f9', fontWeight: '700', fontSize: '12px' }}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  style={{ flex: 2, padding: '10px', borderRadius: '10px', border: 'none', background: '#d97706', color: '#ffffff', fontWeight: '800', fontSize: '12px' }}
                >
                  {submitting ? 'Menyimpan...' : 'Simpan Transfer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
