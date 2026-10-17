import React, { useState, useEffect, useRef } from 'react';
import { 
  ScanLine, Search, Package, Plus, Minus, Check, RefreshCw, 
  AlertCircle, CheckCircle2, Store, History, Camera, CameraOff, 
  SwitchCamera, Volume2, VolumeX, Send, ArrowRight, X, ArrowLeftRight,
  ShieldCheck, AlertTriangle, Truck, User, Info, CheckCheck
} from 'lucide-react';
import { Html5Qrcode } from 'html5-qrcode';
import { 
  lookupOnlineOrderForPacking, 
  completeOnlineOrderPacking,
  searchSubstituteProducts,
  substituteOrderItem 
} from '../api/metricsApi';

// Synthesizer Audio Web (Zero asset dependency)
function playTone(type = 'success') {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    if (ctx.state === 'suspended') ctx.resume();

    if (type === 'success') {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.12);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
      osc.start();
      osc.stop(ctx.currentTime + 0.12);
    } else if (type === 'fanfare') {
      const notes = [523.25, 659.25, 783.99, 1046.50];
      notes.forEach((freq, idx) => {
        setTimeout(() => {
          try {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, ctx.currentTime);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
          } catch (_) {}
        }, idx * 120);
      });
    } else {
      // buzzer
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
      osc.start();
      osc.stop(ctx.currentTime + 0.25);
    }
  } catch (_) {}

  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    if (type === 'success') navigator.vibrate(60);
    else if (type === 'fanfare') navigator.vibrate([80, 50, 80, 50, 120]);
    else navigator.vibrate([120, 60, 120]);
  }
}

export default function ScanKemasanPage() {
  // Mode: 'scan_order' (Step 1) | 'pack_items' (Step 2)
  const [orderInput, setOrderInput] = useState('');
  const [isSearchingOrder, setIsSearchingOrder] = useState(false);
  const [currentOrder, setCurrentOrder] = useState(null);

  // Packing tracking
  const [scannedItems, setScannedItems] = useState({}); // { [order_item_id]: qty }
  const [autoShip, setAutoShip] = useState(false);
  const [isSubmittingPack, setIsSubmittingPack] = useState(false);

  // Item Barcode Scan (Step 2)
  const [itemInput, setItemInput] = useState('');

  // Phone Camera Scanner State
  const [isCameraActive, setIsCameraActive] = useState(false);
  const [cameraLoading, setCameraLoading] = useState(false);
  const [cameraFacing, setCameraFacing] = useState('environment'); // 'environment' | 'user'
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [cameraTarget, setCameraTarget] = useState('order'); // 'order' | 'item'

  // Substitusi Item Modal State
  const [substituteItemTarget, setSubstituteItemTarget] = useState(null);
  const [substituteQuery, setSubstituteQuery] = useState('');
  const [substituteResults, setSubstituteResults] = useState([]);
  const [substituteLoading, setSubstituteLoading] = useState(false);
  const [substituteNote, setSubstituteNote] = useState('');
  const [isSubmittingSubstitute, setIsSubmittingSubstitute] = useState(false);

  const qrCodeScannerRef = useRef(null);
  const lastScannedTextRef = useRef('');
  const lastScanTimestampRef = useRef(0);
  const orderInputRef = useRef(null);
  const itemInputRef = useRef(null);
  const isAutoSubmittingRef = useRef(false);
  const currentOrderRef = useRef(null);

  useEffect(() => {
    currentOrderRef.current = currentOrder;
  }, [currentOrder]);

  const [toast, setToast] = useState(null);
  const showToast = (text, type = 'success') => {
    setToast({ text, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Auto start camera on page mount or mode transition
  useEffect(() => {
    const handleVisibility = () => {
      if (document.hidden) stopCamera();
      else if (!isCameraActive) {
        startCamera(currentOrder ? 'item' : 'order');
      }
    };
    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('pagehide', stopCamera);
    window.addEventListener('beforeunload', stopCamera);

    // Otomatis nyalakan kamera saat masuk ke halaman
    const timer = setTimeout(() => {
      startCamera(currentOrder ? 'item' : 'order');
    }, 400);

    return () => {
      clearTimeout(timer);
      document.removeEventListener('visibilitychange', handleVisibility);
      window.removeEventListener('pagehide', stopCamera);
      window.removeEventListener('beforeunload', stopCamera);
      stopCamera();
    };
  }, []);

  // Saat berpindah antara Step 1 (Scan Resi) dan Step 2 (Scan Item), sesuaikan target kamera
  useEffect(() => {
    if (isCameraActive) {
      setCameraTarget(currentOrder ? 'item' : 'order');
    }
  }, [currentOrder]);

  // -------------------------------------------------------------
  // Camera Barcode Scanner
  // -------------------------------------------------------------
  const startCamera = async (target = 'order') => {
    setCameraTarget(target);
    setCameraLoading(true);
    setIsCameraActive(true);

    try {
      if (qrCodeScannerRef.current) {
        try {
          await qrCodeScannerRef.current.stop();
        } catch (_) {}
      }

      await new Promise((r) => setTimeout(r, 200));

      const scanner = new Html5Qrcode('mobile-camera-viewfinder');
      qrCodeScannerRef.current = scanner;

      const config = {
        fps: 15,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const vw = Number(viewfinderWidth) || 300;
          const vh = Number(viewfinderHeight) || 240;
          const minEdge = Math.min(vw, vh);
          const boxSize = Math.max(Math.floor(minEdge * 0.75), 180);
          return {
            width: Math.max(Math.min(boxSize, vw - 20), 140),
            height: Math.max(Math.min(Math.floor(boxSize * 0.65), vh - 20), 110)
          };
        },
        aspectRatio: 1.0,
      };

      await scanner.start(
        { facingMode: cameraFacing },
        config,
        (decodedText) => {
          handleCameraScanSuccess(decodedText, target);
        },
        () => {}
      );
      setCameraLoading(false);
    } catch (err) {
      console.warn('Camera scanner error:', err);
      setCameraLoading(false);
      showToast('Kamera HP belum aktif: ' + (err.message || 'Izin kamera diperlukan.'), 'error');
    }
  };

  const stopCamera = async () => {
    if (qrCodeScannerRef.current) {
      try {
        await qrCodeScannerRef.current.stop();
        qrCodeScannerRef.current.clear();
      } catch (_) {}
      qrCodeScannerRef.current = null;
    }

    try {
      const videoElem = document.querySelector('#mobile-camera-viewfinder video');
      if (videoElem && videoElem.srcObject) {
        const stream = videoElem.srcObject;
        if (typeof stream.getTracks === 'function') {
          stream.getTracks().forEach(track => {
            try { track.stop(); } catch (_) {}
          });
        }
        videoElem.srcObject = null;
      }
    } catch (_) {}

    setIsCameraActive(false);
    setCameraLoading(false);
  };

  const switchCameraFacing = async () => {
    const nextFacing = cameraFacing === 'environment' ? 'user' : 'environment';
    setCameraFacing(nextFacing);
    await stopCamera();
    setTimeout(() => startCamera(cameraTarget), 250);
  };

  const handleCameraScanSuccess = (decodedText, target) => {
    if (isSubmittingPack || isAutoSubmittingRef.current) return;

    const now = Date.now();
    const clean = decodedText ? decodedText.trim() : '';
    if (!clean) return;

    // Debounce scan 1.2 detik agar tidak dobel scan sekaligus
    if (clean === lastScannedTextRef.current && now - lastScanTimestampRef.current < 1200) {
      return;
    }
    lastScannedTextRef.current = clean;
    lastScanTimestampRef.current = now;

    // Cek target aktif via ref agar tidak terpengaruh stale closure
    const activeOrder = currentOrderRef.current || currentOrder;
    if (!activeOrder) {
      setOrderInput(clean);
      handleFetchOrder(clean);
    } else {
      setItemInput('');
      processItemScan(clean);
    }
  };

  // -------------------------------------------------------------
  // Step 1: Lookup Online Order
  // -------------------------------------------------------------
  const handleFetchOrder = async (searchCode) => {
    const code = (searchCode || orderInput).trim();
    if (!code) {
      showToast('Ketik atau scan nomor resi / invoice / Order ID.', 'error');
      return;
    }

    setIsSearchingOrder(true);
    isAutoSubmittingRef.current = false;
    try {
      const res = await lookupOnlineOrderForPacking(code);
      if (res && res.success && res.order) {
        if (soundEnabled) playTone('success');
        setCurrentOrder(res.order);
        currentOrderRef.current = res.order;

        // Inisialisasi progress packing
        const initScanned = {};
        (res.order.items || []).forEach(it => {
          initScanned[it.id] = it.packed_quantity || 0;
        });
        setScannedItems(initScanned);
        showToast(`Pesanan ${res.order.invoice_number} dimuat! Siap scan barcode produk.`, 'success');

        // Kamera otomatis beralih ke scan item produk
        setCameraTarget('item');

        // Fokus ke input scan item untuk scanner hardware
        setTimeout(() => {
          if (itemInputRef.current) {
            itemInputRef.current.focus();
          }
        }, 200);
      } else {
        if (soundEnabled) playTone('error');
        showToast(res.message || 'Pesanan online tidak ditemukan atau belum Siap Kirim.', 'error');
      }
    } catch (err) {
      if (soundEnabled) playTone('error');
      showToast('Gagal memuat pesanan online: ' + err.message, 'error');
    } finally {
      setIsSearchingOrder(false);
    }
  };

  // -------------------------------------------------------------
  // Step 2: Scan Item Barcode / SKU
  // -------------------------------------------------------------
  const processItemScan = (code) => {
    const order = currentOrderRef.current || currentOrder;
    if (!order || !order.items || order.items.length === 0) return;
    if (isSubmittingPack || isAutoSubmittingRef.current) return;

    const rawBarcode = (code || itemInput).trim();
    if (!rawBarcode) return;

    setItemInput('');
    const cleanCode = rawBarcode.toLowerCase();

    // 1. Cari Item yang Cocok
    let matchedItem = null;

    // A. Match persis SKU atau Barcode
    for (const it of order.items) {
      const itSku = (it.sku || '').trim().toLowerCase();
      const itBarcode = (it.barcode || '').trim().toLowerCase();
      if ((itSku && itSku === cleanCode) || (itBarcode && itBarcode === cleanCode)) {
        matchedItem = it;
        break;
      }
    }

    // B. Match parsial jika belum ketemu
    if (!matchedItem) {
      for (const it of order.items) {
        const itSku = (it.sku || '').trim().toLowerCase();
        const itBarcode = (it.barcode || '').trim().toLowerCase();
        if ((itSku && (cleanCode.includes(itSku) || itSku.includes(cleanCode))) ||
            (itBarcode && (cleanCode.includes(itBarcode) || itBarcode.includes(cleanCode)))) {
          matchedItem = it;
          break;
        }
      }
    }

    if (!matchedItem) {
      if (soundEnabled) playTone('error');
      showToast(`Item '${rawBarcode}' TIDAK ADA dalam pesanan ini!`, 'error');
      return;
    }

    // Cek batas kuantitas
    const currentQty = scannedItems[matchedItem.id] || 0;
    if (currentQty >= matchedItem.quantity) {
      if (soundEnabled) playTone('error');
      showToast(`Item '${matchedItem.name}' sudah lengkap (${matchedItem.quantity} pcs)!`, 'error');
      return;
    }

    // Tambah 1 pcs
    const nextQty = currentQty + 1;
    const newScanned = { ...scannedItems, [matchedItem.id]: nextQty };
    setScannedItems(newScanned);

    // Cek apakah dengan penambahan ini semua produk pesanan sudah 100% komplit
    const allDone = checkAllCompleted(order.items, newScanned);
    if (allDone) {
      if (soundEnabled) playTone('fanfare');
      showToast(`Semua produk pesanan ${order.invoice_number} lengkap! Menyimpan otomatis...`, 'success');
      // Langsung simpan otomatis dan kembali ke scan resi
      handleSubmitCompletePack(newScanned, order);
    } else {
      if (soundEnabled) playTone('success');
    }
  };

  // Helper check all items complete
  const checkAllCompleted = (items, scannedMap) => {
    if (!items || items.length === 0) return false;
    return items.every(it => (scannedMap[it.id] || 0) >= it.quantity);
  };

  // Manual Adjust Qty (+ / -)
  const handleAdjustItemQty = (itemId, delta, maxQty) => {
    const order = currentOrderRef.current || currentOrder;
    if (!order || isSubmittingPack || isAutoSubmittingRef.current) return;

    const cur = scannedItems[itemId] || 0;
    const next = Math.max(0, Math.min(maxQty, cur + delta));
    const updated = { ...scannedItems, [itemId]: next };
    setScannedItems(updated);

    if (delta > 0 && checkAllCompleted(order.items, updated)) {
      if (soundEnabled) playTone('fanfare');
      showToast(`Semua produk pesanan ${order.invoice_number} lengkap! Menyimpan otomatis...`, 'success');
      handleSubmitCompletePack(updated, order);
    }
  };

  // Tandai Semua Selesai (Bypass Barcode)
  const handleMarkAllComplete = () => {
    const order = currentOrderRef.current || currentOrder;
    if (!order || !order.items || isSubmittingPack || isAutoSubmittingRef.current) return;

    const all = {};
    order.items.forEach(it => {
      all[it.id] = it.quantity;
    });
    setScannedItems(all);
    if (soundEnabled) playTone('fanfare');
    showToast(`Semua item lengkap! Menyimpan kemasan otomatis...`, 'success');
    handleSubmitCompletePack(all, order);
  };

  // Reset Scanner State & Kembali ke Mode Scan Resi
  const resetToScanResi = (keepCamera = true, prevOrder = null) => {
    isAutoSubmittingRef.current = false;
    setCurrentOrder(null);
    currentOrderRef.current = null;
    setScannedItems({});
    setOrderInput('');
    setItemInput('');
    setCameraTarget('order');

    // Mencegah scan ulang resi yang sama jika kamera masih mengarah ke paket yang baru selesai
    if (prevOrder) {
      lastScannedTextRef.current = (prevOrder.tracking_number || prevOrder.invoice_number || '').trim();
      lastScanTimestampRef.current = Date.now();
    } else {
      lastScannedTextRef.current = '';
      lastScanTimestampRef.current = 0;
    }

    if (!keepCamera) {
      stopCamera();
    } else if (!isCameraActive) {
      startCamera('order');
    }

    // Auto focus ke input resi agar siap ditembak scanner hardware
    setTimeout(() => {
      if (orderInputRef.current) {
        orderInputRef.current.focus();
      }
    }, 200);
  };

  const handleResetScanner = () => {
    resetToScanResi(true);
  };

  // -------------------------------------------------------------
  // Step 3: Complete Packing & Submit to API
  // -------------------------------------------------------------
  const handleSubmitCompletePack = async (overrideScanned = null, targetOrder = null) => {
    const order = targetOrder || currentOrderRef.current || currentOrder;
    if (!order || isSubmittingPack || isAutoSubmittingRef.current) return;

    const itemsToVerify = overrideScanned || scannedItems;
    const isComplete = checkAllCompleted(order.items, itemsToVerify);
    if (!isComplete && !overrideScanned) {
      const confirmPartial = window.confirm(
        'Perhatian: Belum semua item di-scan lengkap. Apakah Anda yakin ingin menyelesaikan kemasan sekarang?'
      );
      if (!confirmPartial) return;
    }

    isAutoSubmittingRef.current = true;
    setIsSubmittingPack(true);

    try {
      const res = await completeOnlineOrderPacking({
        order_id: order.id,
        auto_ship: autoShip ? 1 : 0,
        packed_items: itemsToVerify
      });

      if (res && res.success) {
        if (soundEnabled) playTone('fanfare');
        showToast(res.message || `Pesanan ${order.invoice_number} selesai disimpan! Siap scan resi berikutnya.`, 'success');

        // Jeda sejenak agar user melihat notifikasi sukses, lalu otomatis refresh ganti ke scan resi
        setTimeout(() => {
          resetToScanResi(true, order);
        }, 700);
      } else {
        isAutoSubmittingRef.current = false;
        if (soundEnabled) playTone('error');
        showToast(res?.message || 'Gagal menyimpan kemasan.', 'error');
      }
    } catch (err) {
      isAutoSubmittingRef.current = false;
      if (soundEnabled) playTone('error');
      showToast('Error: ' + err.message, 'error');
    } finally {
      setIsSubmittingPack(false);
    }
  };

  // -------------------------------------------------------------
  // Substitusi Produk Modal
  // -------------------------------------------------------------
  const handleOpenSubstituteModal = (item) => {
    setSubstituteItemTarget(item);
    setSubstituteQuery('');
    setSubstituteResults([]);
    setSubstituteNote('');
    fetchSubstituteList('');
  };

  const fetchSubstituteList = async (q) => {
    setSubstituteLoading(true);
    try {
      const res = await searchSubstituteProducts(q);
      if (res && res.success) {
        setSubstituteResults(res.products || []);
      }
    } catch (_) {}
    setSubstituteLoading(false);
  };

  const handleApplySubstitute = async (newProduct) => {
    if (!substituteItemTarget) return;

    setIsSubmittingSubstitute(true);
    try {
      const res = await substituteOrderItem({
        order_item_id: substituteItemTarget.id,
        new_master_product_id: newProduct.id,
        note: substituteNote || 'Ditukar via Mobile Scan Kemasan'
      });

      if (res && res.success) {
        if (soundEnabled) playTone('success');
        showToast(res.message, 'success');

        // Update item di memory
        setCurrentOrder(prev => {
          if (!prev) return prev;
          const updatedItems = prev.items.map(it => {
            if (it.id === substituteItemTarget.id) {
              return {
                ...it,
                sku: newProduct.sku,
                barcode: newProduct.barcode,
                name: newProduct.name,
                is_substituted: true,
                original_sku: it.original_sku || it.sku,
                original_product_name: it.original_product_name || it.name,
                gudang_stock: newProduct.stock
              };
            }
            return it;
          });
          return { ...prev, items: updatedItems };
        });

        setSubstituteItemTarget(null);
      } else {
        showToast(res.message || 'Gagal menukar produk.', 'error');
      }
    } catch (err) {
      showToast('Gagal: ' + err.message, 'error');
    } finally {
      setIsSubmittingSubstitute(false);
    }
  };

  // Hitung persentase progress
  const totalRequired = currentOrder?.items?.reduce((acc, it) => acc + it.quantity, 0) || 0;
  const totalScanned = currentOrder?.items?.reduce((acc, it) => acc + (scannedItems[it.id] || 0), 0) || 0;
  const progressPercent = totalRequired > 0 ? Math.round((totalScanned / totalRequired) * 100) : 0;
  const isAllReady = progressPercent >= 100 && totalRequired > 0;

  // Channel badge colors
  const getChannelBadge = (code) => {
    const c = (code || '').toLowerCase();
    if (c === 'shopee') return { bg: '#ee4d2d', text: '#fff', label: 'Shopee' };
    if (c === 'tiktok') return { bg: '#000000', text: '#25f4ee', label: 'TikTok Shop' };
    if (c === 'tokopedia') return { bg: '#03ac0e', text: '#fff', label: 'Tokopedia' };
    if (c === 'lazada') return { bg: '#0f146d', text: '#fff', label: 'Lazada' };
    return { bg: '#475569', text: '#fff', label: 'Marketplace' };
  };

  return (
    <div style={{ padding: '14px 16px 120px 16px', maxWidth: '640px', margin: '0 auto' }}>
      {/* Toast Alert */}
      {toast && (
        <div style={{
          position: 'fixed', top: '16px', left: '50%', transform: 'translateX(-50%)',
          zIndex: 9999, background: toast.type === 'error' ? '#ef4444' : '#10b981',
          color: '#fff', padding: '10px 18px', borderRadius: '12px', fontSize: '13px',
          fontWeight: '700', boxShadow: '0 8px 24px rgba(0,0,0,0.2)',
          display: 'flex', alignItems: 'center', gap: '8px', maxWidth: '90%'
        }}>
          {toast.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle2 size={18} />}
          <span>{toast.text}</span>
        </div>
      )}

      {/* Header Station */}
      <div style={{
        background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
        borderRadius: '18px', padding: '16px 18px', color: '#fff',
        boxShadow: '0 4px 20px rgba(15, 23, 42, 0.25)', marginBottom: '14px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{
              width: '40px', height: '40px', borderRadius: '12px',
              background: 'linear-gradient(135deg, #06b6d4, #0891b2)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              boxShadow: '0 2px 8px rgba(6,182,212,0.4)'
            }}>
              <ScanLine size={22} color="#fff" />
            </div>
            <div>
              <div style={{ fontSize: '16px', fontWeight: '800', letterSpacing: '-0.2px' }}>
                Scan Online
              </div>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <button
              type="button"
              onClick={() => setSoundEnabled(!soundEnabled)}
              style={{
                background: soundEnabled ? 'rgba(16, 185, 129, 0.2)' : 'rgba(255, 255, 255, 0.1)',
                border: '1px solid ' + (soundEnabled ? '#10b981' : 'rgba(255, 255, 255, 0.2)'),
                color: soundEnabled ? '#10b981' : '#cbd5e1',
                padding: '6px 10px', borderRadius: '10px', fontSize: '11px',
                fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '4px'
              }}
            >
              {soundEnabled ? <Volume2 size={14} /> : <VolumeX size={14} />}
              <span>{soundEnabled ? 'Suara' : 'Mute'}</span>
            </button>
            {currentOrder && (
              <button
                type="button"
                onClick={handleResetScanner}
                style={{
                  background: 'rgba(239, 68, 68, 0.2)', border: '1px solid #ef4444',
                  color: '#ef4444', padding: '6px 10px', borderRadius: '10px',
                  fontSize: '11px', fontWeight: '700', cursor: 'pointer'
                }}
              >
                Ganti Resi
              </button>
            )}
          </div>
        </div>

        {/* Info pembeda Penjualan Offline */}
        <div style={{
          marginTop: '12px', paddingTop: '10px', borderTop: '1px solid rgba(255,255,255,0.1)',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '11px', color: '#cbd5e1'
        }}>
          <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
            <ShieldCheck size={13} color="#06b6d4" /> Khusus Order Online Siap Kirim
          </span>
          <span style={{ color: '#94a3b8' }}>
            Offline POS di menu: <strong style={{ color: '#38bdf8' }}>Jual Offline</strong>
          </span>
        </div>
      </div>

      {/* Camera Viewfinder (Always Mounted for Instant Scanner) */}
      <div style={{
        background: '#090d16', borderRadius: '18px', padding: '12px',
        marginBottom: '14px', border: isCameraActive ? '2px solid #06b6d4' : '1px solid #334155',
        position: 'relative', boxShadow: '0 4px 16px rgba(0,0,0,0.3)'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px', color: '#fff' }}>
          <span style={{ fontSize: '12px', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <Camera size={15} color={isCameraActive ? '#06b6d4' : '#94a3b8'} />
            <span>Kamera Scanner HP</span>
            <span style={{
              background: isCameraActive ? '#0284c7' : '#475569',
              color: '#fff', fontSize: '10px', padding: '2px 7px', borderRadius: '6px', fontWeight: '800'
            }}>
              {cameraTarget === 'order' || !currentOrder ? 'SCAN RESI / INVOICE' : 'SCAN BARCODE BARANG'}
            </span>
          </span>

          <div style={{ display: 'flex', gap: '6px' }}>
            {isCameraActive && (
              <button
                type="button"
                onClick={switchCameraFacing}
                style={{
                  background: 'rgba(255,255,255,0.15)', border: 'none', color: '#fff',
                  borderRadius: '8px', padding: '5px 9px', fontSize: '11px', cursor: 'pointer',
                  display: 'flex', alignItems: 'center', gap: '4px'
                }}
              >
                <SwitchCamera size={12} /> Balik
              </button>
            )}
            <button
              type="button"
              onClick={() => isCameraActive ? stopCamera() : startCamera(currentOrder ? 'item' : 'order')}
              style={{
                background: isCameraActive ? 'rgba(239,68,68,0.85)' : 'linear-gradient(135deg, #0284c7, #0369a1)',
                border: 'none', color: '#fff', borderRadius: '8px', padding: '5px 10px',
                fontSize: '11px', fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '4px'
              }}
            >
              {isCameraActive ? <><CameraOff size={12} /> Matikan</> : <><Camera size={12} /> Buka Kamera</>}
            </button>
          </div>
        </div>

        {/* Viewfinder Video Frame */}
        <div
          id="mobile-camera-viewfinder"
          style={{
            width: '100%', borderRadius: '12px', overflow: 'hidden',
            minHeight: isCameraActive ? '220px' : '60px',
            background: '#000', display: isCameraActive ? 'block' : 'none'
          }}
        />

        {!isCameraActive && (
          <div
            onClick={() => startCamera(currentOrder ? 'item' : 'order')}
            style={{
              padding: '24px 16px', textAlign: 'center', background: '#111827',
              borderRadius: '12px', cursor: 'pointer', border: '1px dashed #475569'
            }}
          >
            <Camera size={32} color="#06b6d4" style={{ margin: '0 auto 8px auto', display: 'block' }} />
            <div style={{ color: '#f1f5f9', fontSize: '13px', fontWeight: '700' }}>
              Ketuk untuk Mengaktifkan Kamera Pemindai
            </div>
            <div style={{ color: '#94a3b8', fontSize: '11px', marginTop: '2px' }}>
              Arahkan kamera ke resi pengiriman atau barcode produk
            </div>
          </div>
        )}

        {cameraLoading && (
          <div style={{ textAlign: 'center', color: '#38bdf8', fontSize: '12px', padding: '14px' }}>
            <RefreshCw size={14} className="spin-animate" style={{ display: 'inline', marginRight: '6px' }} />
            Menghubungkan kamera HP...
          </div>
        )}
      </div>

      {/* ─── STEP 1: SCAN RESI / INVOICE PESANAN ─── */}
      {!currentOrder ? (
        <div style={{
          background: '#fff', border: '2px solid #3b82f6', borderRadius: '18px',
          padding: '16px', boxShadow: '0 4px 16px rgba(59,130,246,0.1)', marginBottom: '14px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
            <div style={{ fontSize: '13px', fontWeight: '800', color: '#1e293b', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span style={{
                background: '#3b82f6', color: '#fff', width: '22px', height: '22px',
                borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '11.5px'
              }}>1</span>
              <span>Input / Scan Resi Marketplace</span>
            </div>
          </div>

          <form onSubmit={(e) => { e.preventDefault(); handleFetchOrder(); }}>
            <div style={{
              display: 'flex', alignItems: 'center', gap: '8px',
              border: '2px solid #cbd5e1', borderRadius: '14px', padding: '8px 12px', background: '#f8fafc'
            }}>
              <ScanLine size={20} color="#64748b" />
              <input
                ref={orderInputRef}
                type="text"
                placeholder="Scan / ketik nomor resi (SPX..., J&T, No Invoice)..."
                value={orderInput}
                onChange={(e) => setOrderInput(e.target.value)}
                style={{
                  border: 'none', outline: 'none', background: 'transparent',
                  width: '100%', fontSize: '13px', fontWeight: '700', color: '#0f172a'
                }}
              />
              {orderInput && (
                <button
                  type="button"
                  onClick={() => setOrderInput('')}
                  style={{ background: 'none', border: 'none', color: '#94a3b8', cursor: 'pointer', padding: 0 }}
                >
                  <X size={16} />
                </button>
              )}
            </div>

            <button
              type="submit"
              disabled={isSearchingOrder}
              style={{
                width: '100%', marginTop: '10px', padding: '12px',
                borderRadius: '12px', border: 'none',
                background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
                color: '#fff', fontSize: '13px', fontWeight: '800',
                cursor: isSearchingOrder ? 'not-allowed' : 'pointer',
                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px',
                boxShadow: '0 4px 12px rgba(37,99,235,0.25)'
              }}
            >
              {isSearchingOrder ? (
                <>
                  <RefreshCw size={15} className="spin-animate" /> Memuat Data Pesanan...
                </>
              ) : (
                <>
                  <Search size={16} /> Cari & Buka Pengemasan
                </>
              )}
            </button>
          </form>

          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '10px', textAlign: 'center' }}>
            Tip: Arahkan barcode scanner hardware langsung ke form input ini.
          </div>
        </div>
      ) : (
        /* ─── STEP 2: VERIFIKASI BARANG PESANAN AKTIF ─── */
        <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
          {/* Summary Box Pesanan Terbuka */}
          <div style={{
            background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
            padding: '14px', boxShadow: '0 2px 10px rgba(0,0,0,0.04)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '8px' }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '4px' }}>
                  {(() => {
                    const badge = getChannelBadge(currentOrder.channel_code);
                    return (
                      <span style={{
                        background: badge.bg, color: badge.text, fontSize: '10px',
                        fontWeight: '800', padding: '2px 8px', borderRadius: '6px'
                      }}>
                        {badge.label}
                      </span>
                    );
                  })()}
                  <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#475569' }}>
                    {currentOrder.store_name}
                  </span>
                </div>
                <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a' }}>
                  {currentOrder.invoice_number}
                </div>
              </div>

              <div style={{ textAlign: 'right' }}>
                <span style={{
                  fontSize: '11px', fontWeight: '800', color: '#16a34a',
                  background: '#dcfce7', padding: '3px 8px', borderRadius: '8px'
                }}>
                  {currentOrder.packing_status?.toUpperCase() || 'PACKING'}
                </span>
                <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '3px' }}>
                  {currentOrder.courier}
                </div>
              </div>
            </div>

            <div style={{
              display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px',
              paddingTop: '8px', borderTop: '1px solid #f1f5f9', fontSize: '11px', color: '#475569'
            }}>
              <div>
                <span style={{ color: '#94a3b8' }}>Resi: </span>
                <strong style={{ color: '#0f172a' }}>{currentOrder.tracking_number || '-'}</strong>
              </div>
              <div style={{ textAlign: 'right' }}>
                <span style={{ color: '#94a3b8' }}>Pembeli: </span>
                <strong style={{ color: '#0f172a' }}>{currentOrder.buyer_name}</strong>
              </div>
            </div>
          </div>

          {/* Progress Kemasan Bar */}
          <div style={{
            background: '#fff', border: '1px solid #e2e8f0', borderRadius: '16px',
            padding: '12px 14px', boxShadow: '0 2px 8px rgba(0,0,0,0.03)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
              <span style={{ fontSize: '12px', fontWeight: '700', color: '#475569' }}>
                Kemajuan Pengemasan:
              </span>
              <span style={{
                fontSize: '13px', fontWeight: '800',
                color: isAllReady ? '#16a34a' : '#2563eb'
              }}>
                {totalScanned} / {totalRequired} pcs ({progressPercent}%)
              </span>
            </div>

            <div style={{
              width: '100%', height: '10px', background: '#e2e8f0',
              borderRadius: '999px', overflow: 'hidden'
            }}>
              <div style={{
                width: `${progressPercent}%`, height: '100%',
                background: isAllReady ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #3b82f6, #1d4ed8)',
                transition: 'width 0.3s ease'
              }} />
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '8px' }}>
              <button
                type="button"
                onClick={handleMarkAllComplete}
                style={{
                  background: 'none', border: 'none', color: '#0284c7',
                  fontSize: '11px', fontWeight: '700', cursor: 'pointer', padding: 0,
                  display: 'flex', alignItems: 'center', gap: '3px'
                }}
              >
                <CheckCheck size={14} /> Tandai Semua Lengkap
              </button>

              <label style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '11px', fontWeight: '700', color: '#334155', cursor: 'pointer' }}>
                <input
                  type="checkbox"
                  checked={autoShip}
                  onChange={(e) => setAutoShip(e.target.checked)}
                />
                <span>Auto-Ship Marketplace</span>
              </label>
            </div>
          </div>

          {/* Input Scan Barcode Item / SKU */}
          <div style={{
            background: '#fff', border: '2px solid #06b6d4', borderRadius: '16px',
            padding: '12px 14px', boxShadow: '0 2px 10px rgba(6,182,212,0.1)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
              <span style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span style={{
                  background: '#06b6d4', color: '#fff', width: '20px', height: '20px',
                  borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '11px'
                }}>2</span>
                Tembak Barcode Barang (Kamera / Manual)
              </span>

              <span style={{
                background: isCameraActive ? '#dcfce7' : '#f1f5f9',
                color: isCameraActive ? '#166534' : '#64748b',
                padding: '3px 8px', borderRadius: '8px', fontSize: '10px', fontWeight: '800',
                display: 'flex', alignItems: 'center', gap: '4px'
              }}>
                <span style={{
                  width: '6px', height: '6px', borderRadius: '50%',
                  background: isCameraActive ? '#22c55e' : '#94a3b8'
                }} />
                {isCameraActive ? 'Kamera Aktif' : 'Kamera Mati'}
              </span>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); processItemScan(); }}>
              <div style={{
                display: 'flex', alignItems: 'center', gap: '8px',
                border: '1.5px solid #cbd5e1', borderRadius: '12px', padding: '6px 10px', background: '#f8fafc'
              }}>
                <ScanLine size={18} color="#0891b2" />
                <input
                  ref={itemInputRef}
                  type="text"
                  placeholder="Scan barcode item / ketik SKU lalu Enter..."
                  value={itemInput}
                  onChange={(e) => setItemInput(e.target.value)}
                  style={{
                    border: 'none', outline: 'none', background: 'transparent',
                    width: '100%', fontSize: '12.5px', fontWeight: '700', color: '#0f172a'
                  }}
                />
                <button
                  type="submit"
                  style={{
                    padding: '6px 12px', borderRadius: '8px', border: 'none',
                    background: '#06b6d4', color: '#fff', fontSize: '11px',
                    fontWeight: '700', cursor: 'pointer'
                  }}
                >
                  Scan
                </button>
              </div>
            </form>
          </div>

          {/* Checklist Item Pesanan */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
              Daftar Produk Pesanan ({currentOrder.items?.length || 0} SKU):
            </div>

            {currentOrder.items?.map((item) => {
              const scannedQty = scannedItems[item.id] || 0;
              const isItemComplete = scannedQty >= item.quantity;
              const isPartial = scannedQty > 0 && !isItemComplete;

              return (
                <div
                  key={item.id}
                  style={{
                    background: isItemComplete ? '#f0fdf4' : isPartial ? '#fffbeb' : '#fff',
                    border: `1.5px solid ${isItemComplete ? '#10b981' : isPartial ? '#f59e0b' : '#e2e8f0'}`,
                    borderLeft: `6px solid ${isItemComplete ? '#10b981' : isPartial ? '#f59e0b' : '#94a3b8'}`,
                    borderRadius: '14px', padding: '12px', boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
                  }}
                >
                  <div style={{ display: 'flex', gap: '10px', alignItems: 'flex-start' }}>
                    <img
                      src={item.image || '/assets/img/products/product1.jpg'}
                      alt=""
                      style={{
                        width: '50px', height: '50px', borderRadius: '10px',
                        objectFit: 'cover', border: '1px solid #e2e8f0'
                      }}
                      onError={(e) => { e.target.src = '/assets/img/products/product1.jpg'; }}
                    />

                    <div style={{ flex: 1, minWidth: 0 }}>
                      {item.is_substituted && (
                        <span style={{
                          fontSize: '9.5px', fontWeight: '800', background: '#fef3c7',
                          color: '#d97706', padding: '2px 6px', borderRadius: '6px',
                          display: 'inline-block', marginBottom: '3px'
                        }}>
                          Substitusi: {item.original_product_name || item.original_sku}
                        </span>
                      )}
                      <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a', lineHeight: '1.3' }}>
                        {item.name}
                      </div>

                      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px', alignItems: 'center', marginTop: '4px' }}>
                        <span style={{
                          fontSize: '10.5px', fontWeight: '700', background: '#f1f5f9',
                          color: '#334155', padding: '1px 6px', borderRadius: '6px'
                        }}>
                          SKU: {item.sku}
                        </span>
                        {item.barcode && (
                          <span style={{ fontSize: '10.5px', color: '#64748b' }}>
                            Barcode: {item.barcode}
                          </span>
                        )}
                        <span style={{ fontSize: '10.5px', color: '#0284c7', fontWeight: '700' }}>
                          Stok: {item.gudang_stock} pcs
                        </span>
                      </div>
                    </div>

                    {/* Badge Counter */}
                    <div style={{ textAlign: 'right' }}>
                      <span style={{
                        fontSize: '12px', fontWeight: '800',
                        padding: '4px 10px', borderRadius: '999px',
                        background: isItemComplete ? '#10b981' : isPartial ? '#f59e0b' : '#64748b',
                        color: '#fff'
                      }}>
                        {scannedQty} / {item.quantity} pcs
                      </span>
                    </div>
                  </div>

                  {/* Bottom Counter & Substitusi */}
                  <div style={{
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                    marginTop: '10px', paddingTop: '8px', borderTop: '1px solid rgba(0,0,0,0.06)'
                  }}>
                    <button
                      type="button"
                      onClick={() => handleOpenSubstituteModal(item)}
                      style={{
                        background: 'none', border: '1px solid #cbd5e1',
                        borderRadius: '8px', padding: '3px 8px', fontSize: '10.5px',
                        fontWeight: '700', color: '#d97706', cursor: 'pointer',
                        display: 'flex', alignItems: 'center', gap: '4px'
                      }}
                    >
                      <ArrowLeftRight size={12} /> Tukar Produk
                    </button>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <button
                        type="button"
                        onClick={() => handleAdjustItemQty(item.id, -1, item.quantity)}
                        disabled={scannedQty <= 0}
                        style={{
                          width: '26px', height: '26px', borderRadius: '6px',
                          border: '1px solid #cbd5e1', background: '#fff',
                          color: '#475569', fontSize: '13px', fontWeight: '800',
                          cursor: scannedQty <= 0 ? 'not-allowed' : 'pointer'
                        }}
                      >
                        -
                      </button>
                      <span style={{ fontSize: '12px', fontWeight: '800', minWidth: '18px', textAlign: 'center' }}>
                        {scannedQty}
                      </span>
                      <button
                        type="button"
                        onClick={() => handleAdjustItemQty(item.id, 1, item.quantity)}
                        disabled={scannedQty >= item.quantity}
                        style={{
                          width: '26px', height: '26px', borderRadius: '6px',
                          border: '1px solid #cbd5e1', background: '#fff',
                          color: '#475569', fontSize: '13px', fontWeight: '800',
                          cursor: scannedQty >= item.quantity ? 'not-allowed' : 'pointer'
                        }}
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Selesai & Simpan Kemasan Button */}
          <button
            type="button"
            onClick={handleSubmitCompletePack}
            disabled={isSubmittingPack}
            style={{
              width: '100%', padding: '14px', borderRadius: '14px', border: 'none',
              background: isAllReady
                ? 'linear-gradient(135deg, #10b981, #059669)'
                : 'linear-gradient(135deg, #64748b, #475569)',
              color: '#fff', fontSize: '14px', fontWeight: '800',
              cursor: isSubmittingPack ? 'not-allowed' : 'pointer',
              display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px',
              boxShadow: isAllReady ? '0 4px 16px rgba(16,185,129,0.35)' : 'none',
              marginTop: '8px'
            }}
          >
            {isSubmittingPack ? (
              <>
                <RefreshCw size={16} className="spin-animate" /> Menyimpan Kemasan...
              </>
            ) : (
              <>
                <CheckCircle2 size={18} /> Selesai & Simpan Kemasan
              </>
            )}
          </button>
        </div>
      )}

      {/* ─── MODAL TUKAR PRODUK (SUBSTITUSI ITEM) ─── */}
      {substituteItemTarget && (
        <div style={{
          position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(0,0,0,0.6)', zIndex: 9999,
          display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px'
        }}>
          <div style={{
            background: '#fff', borderRadius: '18px', width: '100%',
            maxWidth: '480px', maxHeight: '85vh', overflowY: 'auto',
            padding: '16px', boxShadow: '0 10px 30px rgba(0,0,0,0.25)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
              <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <ArrowLeftRight size={16} color="#d97706" /> Tukar Produk Pengganti
              </div>
              <button
                type="button"
                onClick={() => setSubstituteItemTarget(null)}
                style={{ background: 'none', border: 'none', color: '#94a3b8', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <div style={{
              background: '#f8fafc', padding: '10px', borderRadius: '10px',
              border: '1px solid #e2e8f0', marginBottom: '12px', fontSize: '11.5px'
            }}>
              <span style={{ color: '#64748b' }}>Barang yang akan ditukar:</span>
              <div style={{ fontWeight: '800', color: '#0f172a' }}>{substituteItemTarget.name}</div>
              <div style={{ color: '#475569', fontSize: '10.5px' }}>SKU: {substituteItemTarget.sku}</div>
            </div>

            <div style={{ marginBottom: '10px' }}>
              <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                Cari Produk Pengganti (Nama / SKU):
              </label>
              <div style={{ display: 'flex', gap: '6px' }}>
                <input
                  type="text"
                  placeholder="Ketik nama atau SKU produk..."
                  value={substituteQuery}
                  onChange={(e) => setSubstituteQuery(e.target.value)}
                  style={{
                    flex: 1, padding: '8px 10px', borderRadius: '10px',
                    border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none'
                  }}
                />
                <button
                  type="button"
                  onClick={() => fetchSubstituteList(substituteQuery)}
                  style={{
                    padding: '8px 12px', borderRadius: '10px', border: 'none',
                    background: '#0284c7', color: '#fff', fontSize: '11px', fontWeight: '700'
                  }}
                >
                  Cari
                </button>
              </div>
            </div>

            <div style={{ marginBottom: '12px' }}>
              <label style={{ fontSize: '11px', fontWeight: '700', color: '#475569', display: 'block', marginBottom: '4px' }}>
                Catatan Alasan Penukaran:
              </label>
              <input
                type="text"
                placeholder="Contoh: Stok warna habis / fisik rusak"
                value={substituteNote}
                onChange={(e) => setSubstituteNote(e.target.value)}
                style={{
                  width: '100%', padding: '8px 10px', borderRadius: '10px',
                  border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none'
                }}
              />
            </div>

            {/* List Hasil Pencarian Produk Pengganti */}
            <div style={{ maxHeight: '220px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '6px' }}>
              {substituteLoading ? (
                <div style={{ textAlign: 'center', padding: '16px', color: '#94a3b8', fontSize: '12px' }}>
                  Memuat data produk...
                </div>
              ) : substituteResults.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '16px', color: '#94a3b8', fontSize: '12px' }}>
                  Tidak ada produk ditemukan.
                </div>
              ) : (
                substituteResults.map(p => (
                  <div
                    key={p.id}
                    onClick={() => handleApplySubstitute(p)}
                    style={{
                      padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                      border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between',
                      alignItems: 'center', cursor: 'pointer'
                    }}
                  >
                    <div>
                      <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{p.name}</div>
                      <div style={{ fontSize: '10.5px', color: '#64748b' }}>SKU: {p.sku}</div>
                    </div>
                    <div style={{ textAlign: 'right' }}>
                      <span style={{ fontSize: '11px', fontWeight: '800', color: p.stock > 0 ? '#16a34a' : '#dc2626' }}>
                        Stok: {p.stock}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
