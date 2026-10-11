import React, { useState, useEffect, useRef } from 'react';
import { 
  ScanLine, Search, Package, Plus, Minus, Check, RefreshCw, 
  ArrowDownRight, ArrowUpRight, Hammer, AlertCircle, CheckCircle2, 
  Store, History, Layers
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { 
  searchScanKemasan, 
  adjustScanKemasanStock, 
  requestScanKemasanProduction 
} from '../api/metricsApi';

export default function ScanKemasanPage() {
  const [codeInput, setCodeInput] = useState('');
  const [activeCode, setActiveCode] = useState('');
  const [matchedProduct, setMatchedProduct] = useState(null);
  const [suggestions, setSuggestions] = useState([]);
  const [recentMovements, setRecentMovements] = useState([]);
  const [marketplaceListings, setMarketplaceListings] = useState([]);
  const [quickProducts, setQuickProducts] = useState([]);
  const [isSearching, setIsSearching] = useState(false);

  // Operation Tab: 'adjust' | 'produce'
  const [opTab, setOpTab] = useState('adjust');

  // Adjust Form State
  const [adjustType, setAdjustType] = useState('in'); // 'in' | 'out' | 'adj'
  const [adjustQty, setAdjustQty] = useState(1);
  const [adjustRef, setAdjustRef] = useState('');
  const [isSubmittingAdjust, setIsSubmittingAdjust] = useState(false);

  // Production Form State
  const [produceQty, setProduceQty] = useState(50);
  const [produceNotes, setProduceNotes] = useState('');
  const [isSubmittingProduce, setIsSubmittingProduce] = useState(false);

  const [toast, setToast] = useState(null);
  const inputRef = useRef(null);

  const showToast = (text, type = 'success') => {
    setToast({ text, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Initial load: fetch suggestions / quick products
  useEffect(() => {
    (async () => {
      setIsSearching(true);
      const res = await searchScanKemasan('');
      if (res?.success && res.products) {
        setQuickProducts(res.products);
      }
      setIsSearching(false);
    })();
  }, []);

  const handleSearch = async (codeToSearch) => {
    const code = (codeToSearch !== undefined ? codeToSearch : codeInput).trim();
    if (!code) return;
    setActiveCode(code);
    setIsSearching(true);

    const res = await searchScanKemasan(code);
    setIsSearching(false);

    if (res?.success) {
      if (res.matched) {
        setMatchedProduct(res.matched);
        setSuggestions([]);
        setRecentMovements(res.recentMovements || []);
        setMarketplaceListings(res.marketplaceListings || []);
        setAdjustQty(1);
        setAdjustRef(`Scan Kemasan (${code})`);
      } else {
        setMatchedProduct(null);
        setSuggestions(res.suggestions || []);
        setRecentMovements([]);
        setMarketplaceListings([]);
        if ((res.suggestions || []).length === 0) {
          showToast(`Produk dengan SKU / Barcode "${code}" tidak ditemukan.`, 'error');
        }
      }
    } else {
      showToast('Gagal memindai produk.', 'error');
    }
  };

  const handleSelectProduct = (prod) => {
    setCodeInput(prod.sku || prod.barcode || '');
    handleSearch(prod.sku || prod.barcode || '');
  };

  const handleExecuteAdjust = async () => {
    if (!matchedProduct) return;
    setIsSubmittingAdjust(true);

    const res = await adjustScanKemasanStock({
      product_id: matchedProduct.id,
      type: adjustType,
      quantity: adjustQty,
      reference: adjustRef || `Scan Kemasan: ${adjustType.toUpperCase()}`
    });

    setIsSubmittingAdjust(false);
    if (res?.success) {
      showToast(res.message);
      // Update local product stock
      setMatchedProduct(prev => ({
        ...prev,
        stock: res.newStock !== undefined ? res.newStock : prev.stock
      }));
      // Refresh details
      handleSearch(matchedProduct.sku);
    } else {
      showToast(res?.message || 'Gagal update stok.', 'error');
    }
  };

  const handleExecuteProduce = async () => {
    if (!matchedProduct) return;
    setIsSubmittingProduce(true);

    const res = await requestScanKemasanProduction({
      product_id: matchedProduct.id,
      quantity: produceQty,
      notes: produceNotes
    });

    setIsSubmittingProduce(false);
    if (res?.success) {
      showToast(res.message);
      setProduceNotes('');
      setProduceQty(50);
    } else {
      showToast(res?.message || 'Gagal kirim order produksi.', 'error');
    }
  };

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

      {/* Viewfinder / Input Box */}
      <div style={{
        background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
        padding: '16px', marginBottom: '14px', boxShadow: '0 2px 10px rgba(0,0,0,0.03)'
      }}>
        <div style={{
          border: '2px dashed #cbd5e1', borderRadius: '14px', padding: '18px 12px',
          background: 'linear-gradient(180deg, #f8fafc, #f1f5f9)', textAlign: 'center',
          marginBottom: '14px'
        }}>
          <div style={{
            width: '46px', height: '46px', borderRadius: '12px', background: '#e0f2fe',
            color: '#0284c7', display: 'flex', alignItems: 'center', justifyContent: 'center',
            margin: '0 auto 8px'
          }}>
            <ScanLine size={24} />
          </div>
          <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
            Pemindai Barcode Kemasan
          </div>
          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
            Tembak barcode kemasan atau ketik SKU produk
          </div>
        </div>

        {/* Input & Search Button */}
        <form onSubmit={(e) => { e.preventDefault(); handleSearch(); }} style={{ display: 'flex', gap: '8px' }}>
          <div style={{
            flex: 1, display: 'flex', alignItems: 'center', gap: '8px',
            background: '#f8fafc', border: '1px solid #cbd5e1', borderRadius: '12px',
            padding: '8px 12px'
          }}>
            <Search size={16} color="#94a3b8" />
            <input
              ref={inputRef}
              type="text"
              placeholder="Scan barcode / ketik SKU..."
              value={codeInput}
              onChange={(e) => setCodeInput(e.target.value)}
              style={{
                border: 'none', outline: 'none', background: 'transparent',
                width: '100%', fontSize: '13px', fontWeight: '600', color: '#0f172a'
              }}
            />
          </div>
          <button
            type="submit"
            disabled={isSearching}
            style={{
              padding: '0 16px', borderRadius: '12px', border: 'none',
              background: 'linear-gradient(135deg, #0284c7, #0369a1)',
              color: '#fff', fontSize: '12.5px', fontWeight: '700',
              cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px'
            }}
          >
            {isSearching ? <RefreshCw size={14} className="spin-animate" /> : 'Cari'}
          </button>
        </form>
      </div>

      {/* Suggestions if multi-match */}
      {suggestions.length > 0 && !matchedProduct && (
        <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '16px', padding: '12px', marginBottom: '14px' }}>
          <div style={{ fontSize: '11.5px', fontWeight: '700', color: '#64748b', marginBottom: '8px' }}>
            Pilih Produk yang Cocok ({suggestions.length}):
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
            {suggestions.map(s => (
              <div
                key={s.id}
                onClick={() => handleSelectProduct(s)}
                style={{
                  padding: '9px 12px', borderRadius: '10px', background: '#f8fafc',
                  border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between',
                  alignItems: 'center', cursor: 'pointer'
                }}
              >
                <div>
                  <div style={{ fontSize: '12px', fontWeight: '700', color: '#0f172a' }}>{s.name}</div>
                  <div style={{ fontSize: '10.5px', color: '#64748b' }}>SKU: {s.sku}</div>
                </div>
                <span style={{ fontSize: '11px', fontWeight: '700', color: '#0284c7' }}>
                  Stok: {s.stock} pcs
                </span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Matched Product Card */}
      {matchedProduct ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {/* Main Info */}
          <div style={{
            background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
            padding: '14px', boxShadow: '0 2px 8px rgba(0,0,0,0.03)'
          }}>
            <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start' }}>
              {matchedProduct.image_url ? (
                <img
                  src={matchedProduct.image_url}
                  alt=""
                  style={{ width: '60px', height: '60px', borderRadius: '12px', objectFit: 'cover', border: '1px solid #e2e8f0' }}
                />
              ) : (
                <div style={{
                  width: '60px', height: '60px', borderRadius: '12px', background: '#f1f5f9',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', border: '1px solid #e2e8f0'
                }}>
                  <Package size={26} color="#94a3b8" />
                </div>
              )}
              <div style={{ flex: 1, minWidth: 0 }}>
                <span style={{
                  fontSize: '10px', fontWeight: '800', background: '#e0f2fe', color: '#0369a1',
                  padding: '2px 8px', borderRadius: '999px', display: 'inline-block', marginBottom: '4px'
                }}>
                  SKU: {matchedProduct.sku}
                </span>
                <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', lineHeight: '1.3' }}>
                  {matchedProduct.name}
                </div>
                {matchedProduct.barcode && (
                  <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '2px' }}>
                    Barcode: <strong style={{ color: '#334155' }}>{matchedProduct.barcode}</strong>
                  </div>
                )}
              </div>
            </div>

            {/* Quick Numbers Bar */}
            <div style={{
              display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px',
              marginTop: '12px', paddingTop: '12px', borderTop: '1px solid #f1f5f9', textAlign: 'center'
            }}>
              <div style={{ background: '#f8fafc', padding: '8px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                <div style={{ fontSize: '10px', color: '#64748b' }}>Stok Gudang</div>
                <div style={{ fontSize: '16px', fontWeight: '800', color: matchedProduct.stock <= (matchedProduct.min_stock || 5) ? '#dc2626' : '#059669' }}>
                  {matchedProduct.stock} pcs
                </div>
              </div>
              <div style={{ background: '#f8fafc', padding: '8px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                <div style={{ fontSize: '10px', color: '#64748b' }}>Harga HPP</div>
                <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
                  {formatRupiah(matchedProduct.cost_price)}
                </div>
              </div>
              <div style={{ background: '#f8fafc', padding: '8px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                <div style={{ fontSize: '10px', color: '#64748b' }}>Harga Jual</div>
                <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0284c7' }}>
                  {formatRupiah(matchedProduct.price)}
                </div>
              </div>
            </div>

            {/* Marketplace Sync info */}
            {marketplaceListings.length > 0 && (
              <div style={{ marginTop: '10px', paddingTop: '8px', borderTop: '1px dashed #e2e8f0' }}>
                <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginBottom: '6px' }}>
                  <Store size={12} /> Terhubung di {marketplaceListings.length} Listing Marketplace:
                </div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
                  {marketplaceListings.map((ml, idx) => (
                    <span key={idx} style={{
                      fontSize: '10px', background: '#f1f5f9', color: '#334155',
                      padding: '3px 7px', borderRadius: '6px', border: '1px solid #e2e8f0'
                    }}>
                      {ml.store_name} ({ml.stock} pcs)
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Quick Operation Tabs */}
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
            <div style={{ display: 'flex', gap: '8px', marginBottom: '14px' }}>
              <button
                type="button"
                onClick={() => setOpTab('adjust')}
                style={{
                  flex: 1, padding: '9px', borderRadius: '10px', fontSize: '12px', fontWeight: '700',
                  border: opTab === 'adjust' ? 'none' : '1px solid #e2e8f0',
                  background: opTab === 'adjust' ? 'linear-gradient(135deg, #0284c7, #0369a1)' : '#f8fafc',
                  color: opTab === 'adjust' ? '#fff' : '#475569', cursor: 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px'
                }}
              >
                <Layers size={14} /> Update Stok Fisik
              </button>

              <button
                type="button"
                onClick={() => setOpTab('produce')}
                style={{
                  flex: 1, padding: '9px', borderRadius: '10px', fontSize: '12px', fontWeight: '700',
                  border: opTab === 'produce' ? 'none' : '1px solid #e2e8f0',
                  background: opTab === 'produce' ? 'linear-gradient(135deg, #8b5cf6, #7c3aed)' : '#f8fafc',
                  color: opTab === 'produce' ? '#fff' : '#475569', cursor: 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px'
                }}
              >
                <Hammer size={14} /> Order Produksi
              </button>
            </div>

            {/* TAB 1: ADJUST STOCK */}
            {opTab === 'adjust' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                {/* Mode Select */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '6px' }}>
                  {[
                    { id: 'in', label: '+ Tambah', icon: ArrowUpRight, color: '#059669', bg: '#ecfdf5' },
                    { id: 'out', label: '- Kurang', icon: ArrowDownRight, color: '#dc2626', bg: '#fef2f2' },
                    { id: 'adj', label: '= Setel Fisik', icon: RefreshCw, color: '#0284c7', bg: '#f0f9ff' },
                  ].map(m => {
                    const active = adjustType === m.id;
                    const Icon = m.icon;
                    return (
                      <button
                        key={m.id}
                        type="button"
                        onClick={() => setAdjustType(m.id)}
                        style={{
                          padding: '8px 4px', borderRadius: '10px', fontSize: '11px', fontWeight: '700',
                          border: active ? `2px solid ${m.color}` : '1px solid #e2e8f0',
                          background: active ? m.bg : '#fff', color: active ? m.color : '#64748b',
                          cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '4px'
                        }}
                      >
                        <Icon size={12} /> {m.label}
                      </button>
                    );
                  })}
                </div>

                {/* Qty Stepper */}
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#64748b', display: 'block', marginBottom: '4px' }}>
                    {adjustType === 'adj' ? 'Jumlah Stok Fisik Riil:' : 'Jumlah Kuantitas:'}
                  </label>
                  <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                    <button
                      type="button"
                      onClick={() => setAdjustQty(q => Math.max(1, q - 1))}
                      style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      -
                    </button>
                    <input
                      type="number"
                      value={adjustQty}
                      onChange={(e) => setAdjustQty(parseInt(e.target.value || '1', 10))}
                      style={{
                        flex: 1, height: '38px', textAlign: 'center', borderRadius: '10px',
                        border: '1px solid #cbd5e1', fontSize: '15px', fontWeight: '800', color: '#0f172a'
                      }}
                    />
                    <button
                      type="button"
                      onClick={() => setAdjustQty(q => q + 1)}
                      style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      +
                    </button>
                  </div>
                  {/* Quick Preset Buttons */}
                  <div style={{ display: 'flex', gap: '6px', marginTop: '6px' }}>
                    {[1, 5, 10, 20, 50].map(n => (
                      <button
                        key={n}
                        type="button"
                        onClick={() => setAdjustQty(n)}
                        style={{
                          flex: 1, padding: '4px 0', borderRadius: '6px', border: '1px solid #e2e8f0',
                          background: adjustQty === n ? '#e0f2fe' : '#f8fafc',
                          color: adjustQty === n ? '#0369a1' : '#64748b',
                          fontSize: '10.5px', fontWeight: '700', cursor: 'pointer'
                        }}
                      >
                        +{n}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Referensi / Catatan */}
                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#64748b', display: 'block', marginBottom: '4px' }}>
                    Referensi / Keterangan:
                  </label>
                  <input
                    type="text"
                    placeholder="Contoh: Scan kemasan fisik / retur supplier"
                    value={adjustRef}
                    onChange={(e) => setAdjustRef(e.target.value)}
                    style={{
                      width: '100%', padding: '8px 10px', borderRadius: '10px',
                      border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none'
                    }}
                  />
                </div>

                {/* Submit Adjust Button */}
                <button
                  type="button"
                  onClick={handleExecuteAdjust}
                  disabled={isSubmittingAdjust}
                  style={{
                    marginTop: '4px', padding: '11px', borderRadius: '12px', border: 'none',
                    background: isSubmittingAdjust ? '#94a3b8' : 'linear-gradient(135deg, #0284c7, #0369a1)',
                    color: '#fff', fontSize: '13px', fontWeight: '800', cursor: isSubmittingAdjust ? 'not-allowed' : 'pointer',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
                    boxShadow: '0 2px 8px rgba(2,132,199,0.3)'
                  }}
                >
                  {isSubmittingAdjust ? (
                    <>
                      <RefreshCw size={15} className="spin-animate" /> Menyimpan...
                    </>
                  ) : (
                    <>
                      <Check size={16} /> Simpan Penyesuaian Stok
                    </>
                  )}
                </button>
              </div>
            )}

            {/* TAB 2: REQUEST PRODUKSI */}
            {opTab === 'produce' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                <div style={{ background: '#f5f3ff', border: '1px solid #ddd6fe', borderRadius: '10px', padding: '8px 12px', fontSize: '11px', color: '#6d28d9' }}>
                  <Hammer size={13} style={{ display: 'inline', marginRight: '4px' }} />
                  Order ini akan masuk ke antrian produksi/konveksi untuk diproses SPK.
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#64748b', display: 'block', marginBottom: '4px' }}>
                    Jumlah Target Produksi (Pcs):
                  </label>
                  <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                    <button
                      type="button"
                      onClick={() => setProduceQty(q => Math.max(1, q - 10))}
                      style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      -
                    </button>
                    <input
                      type="number"
                      value={produceQty}
                      onChange={(e) => setProduceQty(parseInt(e.target.value || '1', 10))}
                      style={{
                        flex: 1, height: '38px', textAlign: 'center', borderRadius: '10px',
                        border: '1px solid #cbd5e1', fontSize: '15px', fontWeight: '800', color: '#0f172a'
                      }}
                    />
                    <button
                      type="button"
                      onClick={() => setProduceQty(q => q + 10)}
                      style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                    >
                      +
                    </button>
                  </div>
                  {/* Preset Produksi */}
                  <div style={{ display: 'flex', gap: '6px', marginTop: '6px' }}>
                    {[20, 50, 100, 200, 500].map(n => (
                      <button
                        key={n}
                        type="button"
                        onClick={() => setProduceQty(n)}
                        style={{
                          flex: 1, padding: '4px 0', borderRadius: '6px', border: '1px solid #e2e8f0',
                          background: produceQty === n ? '#ede9fe' : '#f8fafc',
                          color: produceQty === n ? '#6d28d9' : '#64748b',
                          fontSize: '10.5px', fontWeight: '700', cursor: 'pointer'
                        }}
                      >
                        {n}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label style={{ fontSize: '11px', fontWeight: '700', color: '#64748b', display: 'block', marginBottom: '4px' }}>
                    Catatan Khusus Produksi (Opsional):
                  </label>
                  <textarea
                    rows={2}
                    placeholder="Contoh: Jahitan rapi, prioritaskan variasi warna hitam..."
                    value={produceNotes}
                    onChange={(e) => setProduceNotes(e.target.value)}
                    style={{
                      width: '100%', padding: '8px 10px', borderRadius: '10px',
                      border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none',
                      resize: 'none', fontFamily: 'inherit'
                    }}
                  />
                </div>

                <button
                  type="button"
                  onClick={handleExecuteProduce}
                  disabled={isSubmittingProduce}
                  style={{
                    marginTop: '4px', padding: '11px', borderRadius: '12px', border: 'none',
                    background: isSubmittingProduce ? '#94a3b8' : 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
                    color: '#fff', fontSize: '13px', fontWeight: '800', cursor: isSubmittingProduce ? 'not-allowed' : 'pointer',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
                    boxShadow: '0 2px 8px rgba(139,92,246,0.3)'
                  }}
                >
                  {isSubmittingProduce ? (
                    <>
                      <RefreshCw size={15} className="spin-animate" /> Mengirim...
                    </>
                  ) : (
                    <>
                      <Hammer size={16} /> Kirim Permintaan Produksi
                    </>
                  )}
                </button>
              </div>
            )}
          </div>

          {/* Riwayat Mutasi Stok Terakhir */}
          {recentMovements.length > 0 && (
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '10px' }}>
                <History size={14} color="#64748b" /> Riwayat Mutasi Stok Produk Ini:
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                {recentMovements.map((m, idx) => {
                  const isIn = m.type === 'in' || m.quantity > 0;
                  return (
                    <div key={idx} style={{
                      padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                      border: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between',
                      alignItems: 'center', fontSize: '11px'
                    }}>
                      <div>
                        <div style={{ fontWeight: '700', color: '#0f172a' }}>{m.reference || m.type}</div>
                        <div style={{ color: '#94a3b8', fontSize: '10px' }}>{m.dateFormatted}</div>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <span style={{ fontWeight: '800', color: isIn ? '#059669' : '#dc2626' }}>
                          {isIn ? '+' : ''}{m.quantity} pcs
                        </span>
                        <div style={{ fontSize: '9.5px', color: '#64748b' }}>Sisa: {m.balanceAfter}</div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>
      ) : (
        /* Quick Products suggestion grid if no product scanned yet */
        quickProducts.length > 0 && (
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
            <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
              📦 Produk Cepat / Sering Dicari:
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
              {quickProducts.map(p => (
                <div
                  key={p.id}
                  onClick={() => handleSelectProduct(p)}
                  style={{
                    padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                    border: '1px solid #f1f5f9', display: 'flex', gap: '10px', alignItems: 'center',
                    cursor: 'pointer'
                  }}
                >
                  <div style={{
                    width: '32px', height: '32px', borderRadius: '8px', background: '#e2e8f0',
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                  }}>
                    <Package size={15} color="#64748b" />
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: '11.5px', fontWeight: '700', color: '#0f172a', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                      {p.name}
                    </div>
                    <div style={{ fontSize: '10px', color: '#64748b' }}>SKU: {p.sku}</div>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <span style={{ fontSize: '11px', fontWeight: '800', color: p.stock <= (p.min_stock || 5) ? '#dc2626' : '#059669' }}>
                      {p.stock} pcs
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )
      )}
    </div>
  );
}
