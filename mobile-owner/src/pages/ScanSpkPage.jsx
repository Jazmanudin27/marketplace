import React, { useState, useEffect, useRef } from 'react';
import { 
  QrCode, History, Search, Package, CheckCircle2, AlertCircle, 
  RefreshCw, Check, Clock, Calendar, Sparkles, Volume2, VolumeX, 
  User, ArrowUpRight, Zap
} from 'lucide-react';
import { processScanSpk, fetchScanSpkHistory } from '../api/metricsApi';

// Beep audio synthesizer using Web Audio API for hardware scanner feel
function playBeep(success = true) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    if (success) {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime); // A5 note
      osc.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.12);
      gain.gain.setValueAtTime(0.25, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
      osc.start();
      osc.stop(ctx.currentTime + 0.12);
    } else {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
      osc.start();
      osc.stop(ctx.currentTime + 0.25);
    }
  } catch (_) {}

  // Mobile vibration feedback
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    navigator.vibrate(success ? [60] : [100, 50, 100]);
  }
}

export default function ScanSpkPage() {
  // Main Navigation Tabs: 'scanner' | 'history'
  const [activeTab, setActiveTab] = useState('scanner');

  // Scanner State
  const [barcodeInput, setBarcodeInput] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [scanQty, setScanQty] = useState(1);
  const [receiverName, setReceiverName] = useState(() => {
    try {
      const u = JSON.parse(localStorage.getItem('owner_user') || '{}');
      return u.name || 'Petugas Gudang';
    } catch {
      return 'Petugas Gudang';
    }
  });

  // Recent Session Scans (In-memory stack)
  const [lastScanResult, setLastScanResult] = useState(null);
  const [sessionScans, setSessionScans] = useState([]);
  const [soundEnabled, setSoundEnabled] = useState(true);

  // History Tab State
  const [historyList, setHistoryList] = useState([]);
  const [historyKpi, setHistoryKpi] = useState({ todayScans: 0, todayPcs: 0, todaySpks: 0 });
  const [historySearch, setHistorySearch] = useState('');
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);

  const [toast, setToast] = useState(null);
  const inputRef = useRef(null);

  const showToast = (text, type = 'success') => {
    setToast({ text, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Re-focus input whenever scanner tab is active
  useEffect(() => {
    if (activeTab === 'scanner') {
      const t = setTimeout(() => {
        if (inputRef.current) inputRef.current.focus();
      }, 100);
      return () => clearTimeout(t);
    }
  }, [activeTab]);

  // Load history when tab is clicked
  useEffect(() => {
    if (activeTab === 'history') {
      loadHistory();
    }
  }, [activeTab]);

  const loadHistory = async (search = historySearch) => {
    setIsLoadingHistory(true);
    const res = await fetchScanSpkHistory({ search });
    setIsLoadingHistory(false);
    if (res?.success) {
      setHistoryList(res.data || []);
      setHistoryKpi(res.kpi || { todayScans: 0, todayPcs: 0, todaySpks: 0 });
    }
  };

  const handleScanSubmit = async (e) => {
    if (e) e.preventDefault();
    const code = barcodeInput.trim();
    if (!code || isProcessing) return;

    setIsProcessing(true);
    const res = await processScanSpk({
      code,
      qty: scanQty,
      nama_pengambil: receiverName,
      catatan: 'Scan Barcode Karung SPK Mobile'
    });

    setIsProcessing(false);
    setBarcodeInput('');

    if (res?.success && res.data) {
      if (soundEnabled) playBeep(true);
      setLastScanResult({
        success: true,
        data: res.data,
        message: res.message
      });
      setSessionScans(prev => [res.data, ...prev.slice(0, 19)]);
      showToast(res.message);
    } else {
      if (soundEnabled) playBeep(false);
      setLastScanResult({
        success: false,
        code,
        message: res?.message || 'Kode barcode SPK tidak dikenali / kuota sudah penuh.'
      });
      showToast(res?.message || 'Gagal scan SPK.', 'error');
    }

    // Keep focus ready for next scan gun trigger immediately
    setTimeout(() => {
      if (inputRef.current) inputRef.current.focus();
    }, 50);
  };

  const sessionTotalPcs = sessionScans.reduce((acc, cur) => acc + (cur.qtyTaken || 0), 0);

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', paddingBottom: '90px' }}>
      {/* Toast */}
      {toast && (
        <div style={{
          position: 'fixed', top: '16px', left: '16px', right: '16px', zIndex: 99999,
          background: toast.type === 'error' ? '#ef4444' : '#10b981',
          color: '#fff', padding: '12px 16px', borderRadius: '12px', fontSize: '12.5px',
          fontWeight: '600', boxShadow: '0 8px 24px rgba(0,0,0,0.18)',
          display: 'flex', alignItems: 'center', gap: '8px'
        }}>
          {toast.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle2 size={18} />}
          <span>{toast.text}</span>
        </div>
      )}

      {/* ── 2 TAB UTAMA: SCANNER vs HISTORI ── */}
      <div style={{
        display: 'flex', background: '#e2e8f0', borderRadius: '14px', padding: '4px',
        marginBottom: '14px'
      }}>
        <button
          type="button"
          onClick={() => setActiveTab('scanner')}
          style={{
            flex: 1, padding: '9px 12px', borderRadius: '11px', border: 'none',
            fontSize: '12.5px', fontWeight: '800', cursor: 'pointer',
            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
            background: activeTab === 'scanner' ? '#fff' : 'transparent',
            color: activeTab === 'scanner' ? '#7c3aed' : '#64748b',
            boxShadow: activeTab === 'scanner' ? '0 2px 6px rgba(0,0,0,0.06)' : 'none'
          }}
        >
          <QrCode size={16} /> SCANNER
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('history')}
          style={{
            flex: 1, padding: '9px 12px', borderRadius: '11px', border: 'none',
            fontSize: '12.5px', fontWeight: '800', cursor: 'pointer',
            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
            background: activeTab === 'history' ? '#fff' : 'transparent',
            color: activeTab === 'history' ? '#7c3aed' : '#64748b',
            boxShadow: activeTab === 'history' ? '0 2px 6px rgba(0,0,0,0.06)' : 'none'
          }}
        >
          <History size={16} /> HISTORI
          {historyKpi.todayPcs > 0 && (
            <span style={{
              fontSize: '10px', background: '#ede9fe', color: '#6d28d9',
              padding: '1px 6px', borderRadius: '999px', fontWeight: '800'
            }}>
              {historyKpi.todayPcs} pcs
            </span>
          )}
        </button>
      </div>

      {/* ════════════════════ TAB 1: SCANNER ════════════════════ */}
      {activeTab === 'scanner' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {/* Main Continuous Scanner Card */}
          <div style={{
            background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
            padding: '16px', boxShadow: '0 2px 10px rgba(0,0,0,0.03)'
          }}>
            {/* Viewfinder simulation */}
            <div style={{
              border: '2px dashed #cbd5e1', borderRadius: '14px', padding: '16px 12px',
              background: 'linear-gradient(180deg, #faf5ff, #f3e8ff)', textAlign: 'center',
              marginBottom: '14px', position: 'relative'
            }}>
              {/* Sound Toggle */}
              <button
                type="button"
                onClick={() => setSoundEnabled(!soundEnabled)}
                title={soundEnabled ? 'Suara Beep Aktif' : 'Suara Beep Mati'}
                style={{
                  position: 'absolute', top: '10px', right: '10px',
                  background: soundEnabled ? '#ede9fe' : '#f1f5f9',
                  border: 'none', color: soundEnabled ? '#7c3aed' : '#94a3b8',
                  width: '30px', height: '30px', borderRadius: '999px',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer'
                }}
              >
                {soundEnabled ? <Volume2 size={16} /> : <VolumeX size={16} />}
              </button>

              <div style={{
                width: '46px', height: '46px', borderRadius: '12px', background: '#ede9fe',
                color: '#7c3aed', display: 'flex', alignItems: 'center', justifyContent: 'center',
                margin: '0 auto 8px'
              }}>
                <Zap size={24} />
              </div>

              <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                Siap Tembak Barcode / QR SPK
              </div>
              <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                Otomatis membaca SPK & item, barang langsung masuk stok gudang
              </div>
            </div>

            {/* Input Form with Autofocus for Laser Gun */}
            <form onSubmit={handleScanSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <div style={{
                display: 'flex', alignItems: 'center', gap: '8px',
                background: '#f8fafc', border: '2px solid #7c3aed', borderRadius: '14px',
                padding: '10px 14px', boxShadow: '0 0 0 3px rgba(124, 58, 237, 0.1)'
              }}>
                <QrCode size={18} color="#7c3aed" />
                <input
                  ref={inputRef}
                  type="text"
                  placeholder="Fokus kursor di sini & tembak barcode..."
                  value={barcodeInput}
                  onChange={(e) => setBarcodeInput(e.target.value)}
                  disabled={isProcessing}
                  autoComplete="off"
                  autoFocus
                  style={{
                    border: 'none', outline: 'none', background: 'transparent',
                    width: '100%', fontSize: '14px', fontWeight: '700', color: '#0f172a'
                  }}
                />
                {isProcessing && <RefreshCw size={16} className="spin-animate" color="#7c3aed" />}
              </div>

              {/* Quantity Preset Bar & Receiver */}
              <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                <div style={{ flex: 1, display: 'flex', gap: '4px', alignItems: 'center' }}>
                  <span style={{ fontSize: '11px', fontWeight: '700', color: '#64748b' }}>Qty:</span>
                  {[1, 5, 10, 20].map(n => (
                    <button
                      key={n}
                      type="button"
                      onClick={() => { setScanQty(n); if (inputRef.current) inputRef.current.focus(); }}
                      style={{
                        padding: '4px 8px', borderRadius: '6px', border: scanQty === n ? 'none' : '1px solid #e2e8f0',
                        background: scanQty === n ? '#7c3aed' : '#fff', color: scanQty === n ? '#fff' : '#64748b',
                        fontSize: '11px', fontWeight: '800', cursor: 'pointer'
                      }}
                    >
                      {n}
                    </button>
                  ))}
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '11px', color: '#64748b' }}>
                  <User size={13} />
                  <input
                    type="text"
                    value={receiverName}
                    onChange={(e) => setReceiverName(e.target.value)}
                    placeholder="Penerima..."
                    style={{
                      width: '100px', border: '1px solid #e2e8f0', borderRadius: '6px',
                      padding: '3px 6px', fontSize: '10.5px', background: '#fff'
                    }}
                  />
                </div>
              </div>
            </form>
          </div>

          {/* ── CARD HASIL SCAN TERAKHIR (REAL-TIME FEEDBACK) ── */}
          {lastScanResult && (
            <div style={{
              background: lastScanResult.success ? '#ecfdf5' : '#fef2f2',
              border: `1px solid ${lastScanResult.success ? '#a7f3d0' : '#fecaca'}`,
              borderRadius: '16px', padding: '14px', animation: 'scaleIn 0.2s ease'
            }}>
              {lastScanResult.success ? (
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '6px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <CheckCircle2 size={18} color="#059669" />
                      <span style={{ fontSize: '13px', fontWeight: '800', color: '#065f46' }}>
                        Scan Berhasil Diterima!
                      </span>
                    </div>
                    <span style={{ fontSize: '10px', color: '#059669', fontWeight: '700' }}>
                      {lastScanResult.data.timeFormatted}
                    </span>
                  </div>

                  <div style={{ background: '#fff', borderRadius: '12px', padding: '10px 12px', border: '1px solid #d1fae5' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                      <div>
                        <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                          {lastScanResult.data.namaProduk}
                        </div>
                        <div style={{ fontSize: '11px', color: '#64748b', marginTop: '1px' }}>
                          SPK: <strong style={{ color: '#7c3aed' }}>#{lastScanResult.data.noSpk}</strong> • Ukuran: <strong style={{ color: '#0f172a' }}>{lastScanResult.data.ukuran}</strong>
                        </div>
                      </div>
                      <span style={{
                        fontSize: '13px', fontWeight: '800', color: '#059669',
                        background: '#d1fae5', padding: '2px 8px', borderRadius: '6px'
                      }}>
                        +{lastScanResult.data.qtyTaken} pcs
                      </span>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '8px', paddingTop: '6px', borderTop: '1px dashed #e2e8f0', fontSize: '10.5px', color: '#64748b' }}>
                      <span>Sisa Kuota SPK: <strong style={{ color: lastScanResult.data.sisaQty === 0 ? '#059669' : '#dc2626' }}>{lastScanResult.data.sisaQty} pcs</strong></span>
                      <span>Total Diterima: <strong>{lastScanResult.data.totalTaken} / {lastScanResult.data.totalTarget} pcs</strong></span>
                    </div>
                  </div>
                </div>
              ) : (
                <div style={{ display: 'flex', gap: '10px', alignItems: 'flex-start' }}>
                  <AlertCircle size={20} color="#dc2626" style={{ marginTop: '2px' }} />
                  <div>
                    <div style={{ fontSize: '13px', fontWeight: '800', color: '#991b1b' }}>
                      Scan Gagal!
                    </div>
                    <div style={{ fontSize: '11.5px', color: '#b91c1c', marginTop: '2px' }}>
                      {lastScanResult.message}
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Running Session Counter & Quick List */}
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>
                Hasil Scan Sesi Ini ({sessionScans.length} item):
              </div>
              <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#7c3aed', background: '#ede9fe', padding: '2px 8px', borderRadius: '999px' }}>
                Total: {sessionTotalPcs} pcs
              </span>
            </div>

            {sessionScans.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '24px 10px', color: '#94a3b8', fontSize: '12px' }}>
                Belum ada barang yang discan pada sesi ini.
              </div>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                {sessionScans.map((s, idx) => (
                  <div
                    key={idx}
                    style={{
                      padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                      border: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between',
                      alignItems: 'center', fontSize: '11px'
                    }}
                  >
                    <div>
                      <div style={{ fontWeight: '700', color: '#0f172a' }}>
                        {s.namaProduk} ({s.ukuran})
                      </div>
                      <div style={{ fontSize: '10px', color: '#64748b' }}>
                        SPK #{s.noSpk} • {s.timeFormatted}
                      </div>
                    </div>
                    <span style={{ fontWeight: '800', color: '#059669', fontSize: '12px' }}>
                      +{s.qtyTaken} pcs
                    </span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* ════════════════════ TAB 2: HISTORI SCAN ════════════════════ */}
      {activeTab === 'history' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {/* Summary KPI Cards */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px' }}>
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '10px', textAlign: 'center' }}>
              <div style={{ fontSize: '10px', color: '#64748b' }}>Scan Hari Ini</div>
              <div style={{ fontSize: '16px', fontWeight: '800', color: '#7c3aed' }}>
                {historyKpi.todayScans}
              </div>
            </div>
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '10px', textAlign: 'center' }}>
              <div style={{ fontSize: '10px', color: '#64748b' }}>Total Pcs Masuk</div>
              <div style={{ fontSize: '16px', fontWeight: '800', color: '#059669' }}>
                {historyKpi.todayPcs} pcs
              </div>
            </div>
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '10px', textAlign: 'center' }}>
              <div style={{ fontSize: '10px', color: '#64748b' }}>SPK Terbaca</div>
              <div style={{ fontSize: '16px', fontWeight: '800', color: '#0284c7' }}>
                {historyKpi.todaySpks} SPK
              </div>
            </div>
          </div>

          {/* Search History */}
          <div style={{
            display: 'flex', alignItems: 'center', gap: '8px', background: '#fff',
            border: '1px solid #e2e8f0', borderRadius: '12px', padding: '8px 12px'
          }}>
            <Search size={15} color="#94a3b8" />
            <input
              type="text"
              placeholder="Cari No. SPK, produk, atau nama penerima..."
              value={historySearch}
              onChange={(e) => {
                setHistorySearch(e.target.value);
                loadHistory(e.target.value);
              }}
              style={{ border: 'none', outline: 'none', background: 'transparent', width: '100%', fontSize: '12px' }}
            />
          </div>

          {/* List Histori */}
          {isLoadingHistory ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#64748b', fontSize: '12px' }}>
              <RefreshCw size={18} className="spin-animate" style={{ margin: '0 auto 8px' }} />
              Memuat data histori...
            </div>
          ) : historyList.length === 0 ? (
            <div style={{
              background: '#fff', border: '1px solid #e2e8f0', borderRadius: '16px',
              padding: '30px 20px', textAlign: 'center', color: '#94a3b8', fontSize: '12.5px'
            }}>
              <Package size={28} style={{ margin: '0 auto 8px' }} />
              Tidak ada data riwayat penerimaan barang SPK.
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {historyList.map(item => (
                <div
                  key={item.id}
                  style={{
                    background: '#fff', border: '1px solid #e2e8f0', borderRadius: '14px',
                    padding: '12px 14px', boxShadow: '0 1px 4px rgba(0,0,0,0.02)'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
                    <div>
                      <span style={{ fontSize: '11px', fontWeight: '800', color: '#7c3aed' }}>
                        #{item.noSpk}
                      </span>
                      <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a', marginTop: '2px' }}>
                        {item.namaProduk}
                      </div>
                      <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '1px' }}>
                        Ukuran: <strong style={{ color: '#0f172a' }}>{item.ukuran}</strong> • SKU: {item.sku}
                      </div>
                    </div>

                    <div style={{ textAlign: 'right' }}>
                      <span style={{
                        fontSize: '12px', fontWeight: '800', color: '#059669',
                        background: '#ecfdf5', padding: '2px 8px', borderRadius: '6px'
                      }}>
                        +{item.qtyDiambil} pcs
                      </span>
                      <div style={{ fontSize: '10px', color: '#94a3b8', marginTop: '3px' }}>
                        {item.timeFormatted}
                      </div>
                    </div>
                  </div>

                  <div style={{
                    marginTop: '8px', paddingTop: '6px', borderTop: '1px dashed #f1f5f9',
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '10px', color: '#64748b'
                  }}>
                    <span>Penerima: <strong style={{ color: '#334155' }}>{item.namaPengambil}</strong></span>
                    <span>{item.dateFormatted}</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
