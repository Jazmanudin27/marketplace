import React from 'react';
import { 
  X, 
  ShoppingBag, 
  RotateCcw, 
  Boxes, 
  Receipt, 
  ScanLine, 
  PackagePlus, 
  PackageMinus, 
  HandCoins, 
  CreditCard, 
  LineChart, 
  Layers, 
  Target, 
  Store, 
  Users, 
  Building2, 
  Truck, 
  FileText, 
  ShieldCheck, 
  ArrowUpRight,
  ChevronRight
} from 'lucide-react';

export default function AllModulesModal({ isOpen, onClose, onSelectAction }) {
  if (!isOpen) return null;

  const moduleGroups = [
    {
      group: 'Transaksi & Penjualan',
      items: [
        { id: 'orders', title: 'Pesanan Masuk', desc: 'Daftar transaksi marketplace & POS', icon: ShoppingBag, color: '#2563eb' },
        { id: 'returns', title: 'Pesanan Retur', desc: 'Komplain pembeli & retur barang', icon: RotateCcw, color: '#ef4444' },
        { id: 'offline_sales', title: 'Penjualan Offline', desc: 'Kasir fisik toko / order manual', icon: Receipt, color: '#10b981' },
        { id: 'target', title: 'Target Marketing', desc: 'Realisasi omset & komisi tim', icon: Target, color: '#0ea5e9' },
        { id: 'spks', title: 'SPK Produksi', desc: 'Surat perintah kerja konveksi', icon: FileText, color: '#8b5cf6' },
      ]
    },
    {
      group: 'Gudang & Stok Jadi',
      items: [
        { id: 'warehouse', title: 'Mutasi Masuk & Keluar', desc: 'Pergerakan stok gudang jadi', icon: Boxes, color: '#7c3aed' },
        { id: 'stock_opname', title: 'Opname Stok Jadi', desc: 'Pengecekan fisik stok akhir', icon: ShieldCheck, color: '#6366f1' },
        { id: 'scanner', title: 'Scan & Kemas Barcode', desc: 'Fulfillment cepat pesanan', icon: ScanLine, color: '#06b6d4' },
      ]
    },
    {
      group: 'Pembelian & Titipan Barang',
      items: [
        { id: 'pembelian_masuk', title: 'Pemasukan Barang', desc: 'Penerimaan barang dari supplier', icon: PackagePlus, color: '#f59e0b' },
        { id: 'pembelian_keluar', title: 'Pengeluaran Bahan', desc: 'Bahan keluar untuk produksi', icon: PackageMinus, color: '#ea580c' },
        { id: 'titipan', title: 'Titipan Barang (Konsinyasi)', desc: 'Barang titipan pihak ketiga & supplier', icon: HandCoins, color: '#d946ef' },
        { id: 'hutang_supplier', title: 'Hutang Supplier', desc: 'Tagihan tempo pembelian barang', icon: CreditCard, color: '#e11d48' },
      ]
    },
    {
      group: 'Keuangan & Laba Rugi',
      items: [
        { id: 'margin', title: 'Laba Rugi Aktual', desc: 'Margin bersih per pesanan & total', icon: LineChart, color: '#059669' },
        { id: 'cashflow', title: 'Mutasi Kas & Bank', desc: 'Pencatatan kas masuk & keluar', icon: CreditCard, color: '#0d9488' },
        { id: 'marketplace_wallets', title: 'Saldo Marketplace', desc: 'Shopee & TikTok escrow balance', icon: Store, color: '#0284c7' },
      ]
    },
    {
      group: 'Master Data & Konfigurasi',
      items: [
        { id: 'master_products', title: 'Master Produk', desc: 'Katalog SKU, variasi, dan HPP', icon: Layers, color: '#8b5cf6' },
        { id: 'stores', title: 'Toko Marketplace', desc: 'Sinkronisasi toko Shopee & TikTok', icon: Store, color: '#ec4899' },
        { id: 'users', title: 'Pengguna & Hak Akses', desc: 'Karyawan dan akses sistem', icon: Users, color: '#64748b' },
        { id: 'company', title: 'Perusahaan & Tenant', desc: 'Profil usaha dan alamat gudang', icon: Building2, color: '#475569' },
      ]
    }
  ];

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div 
        className="bottom-sheet-card" 
        onClick={(e) => e.stopPropagation()}
        style={{ maxHeight: '92vh', padding: '16px 18px 36px' }}
      >
        <div className="sheet-handle"></div>

        {/* Modal Header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '18px' }}>
          <div>
            <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a' }}>
              Katalog Seluruh Modul ERP
            </h3>
            <p style={{ fontSize: '11.5px', color: '#64748b' }}>
              Akses cepat fitur ERP Marketplace versi Mobile Owner
            </p>
          </div>

          <button 
            type="button" 
            onClick={onClose}
            style={{ 
              background: '#f1f5f9', 
              border: 'none', 
              borderRadius: '50%', 
              width: '32px', 
              height: '32px', 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center',
              cursor: 'pointer' 
            }}
          >
            <X size={18} color="#475569" />
          </button>
        </div>

        {/* Category Accordion / Groups */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
          {moduleGroups.map((grp, idx) => (
            <div key={idx}>
              <div style={{ 
                fontSize: '11.5px', 
                fontWeight: '800', 
                color: '#64748b', 
                textTransform: 'uppercase', 
                letterSpacing: '0.5px',
                marginBottom: '8px'
              }}>
                {grp.group}
              </div>

              <div style={{ 
                display: 'flex', 
                flexDirection: 'column', 
                gap: '8px',
                background: '#f8fafc',
                borderRadius: '16px',
                padding: '8px',
                border: '1px solid #e2e8f0'
              }}>
                {grp.items.map((item) => {
                  const IconComp = item.icon;
                  return (
                    <div 
                      key={item.id}
                      onClick={() => {
                        onClose();
                        onSelectAction && onSelectAction(item.id);
                      }}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '10px 12px',
                        background: '#ffffff',
                        borderRadius: '12px',
                        border: '1px solid #f1f5f9',
                        cursor: 'pointer',
                        transition: 'transform 0.15s ease'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{
                          width: '38px',
                          height: '38px',
                          borderRadius: '11px',
                          background: item.color,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          color: '#ffffff',
                          boxShadow: `0 4px 10px ${item.color}35`,
                          flexShrink: 0
                        }}>
                          <IconComp size={19} strokeWidth={2.2} />
                        </div>

                        <div>
                          <div style={{ fontSize: '13px', fontWeight: '700', color: '#0f172a' }}>
                            {item.title}
                          </div>
                          <div style={{ fontSize: '11px', color: '#64748b' }}>
                            {item.desc}
                          </div>
                        </div>
                      </div>

                      <ChevronRight size={16} color="#94a3b8" />
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
