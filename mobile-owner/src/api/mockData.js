// Mock Data Realistis Sesuai Schema Database ERP Marketplace

export const mockOwnerProfile = {
  name: "Dina Saparinda, S.Kom",
  role: "Super Admin / Business Owner",
  tenantName: "Ruang Seragam",
  avatarInitials: "DS",
  email: "owner@ruangseragam.com",
  phone: "+62 812-3456-7890",
  systemStatus: "Online (V2 Sync Aktif)",
  storesCount: 4,
  connectedMarketplaces: ["Shopee", "TikTok Shop", "Tokopedia", "POS Offline"]
};

export const mockOwnerMetrics = {
  ordersNew: 18,
  ordersToShip: 34,
  ordersReturned: 2,
  ordersCompletedToday: 89,
  
  todayOmset: 14850000,
  todayMargin: 3240000,
  todayHpp: 11610000,
  
  targetMonthlyMargin: 150000000,
  actualMonthlyMargin: 126750000,
  targetProgressPercent: 84.5,
  totalEarnedCommission: 8872500,

  cashBalance: 48920000,
  receivableEscrow: 24150000 // Dana tertahan di Shopee/TikTok
};

export const mockOrders = [
  {
    id: 101,
    invoiceNumber: "261006TNY6YAPM",
    marketplaceId: "SHP-261006TNY6YAPM",
    storeName: "NUSANTARA SERAGAM",
    channel: "shopee",
    channelCode: "shopee",
    buyerName: "Ahmad Fauzi",
    buyerPhone: "081298765432",
    buyerCity: "Jakarta Selatan",
    courier: "J&T Express",
    trackingNumber: "JT9928172635",
    completedAt: "2026-10-10 21:40",
    status: "COMPLETED",
    quantity: 1,
    releasedValue: 72181,
    hppModal: 56200,
    margin: 15981,
    commission: 1119,
    commissionRate: 7.0,
    items: [
      {
        id: 1,
        productName: "Seragam Pramuka Siaga Putra Lengan Pendek",
        sku: "SP-SIA-PUTRA-L",
        size: "Size L",
        quantity: 1,
        price: 79000,
        costPrice: 56200,
        marketplaceFee: 6819
      }
    ]
  },
  {
    id: 102,
    invoiceNumber: "261005T7TU40CG",
    marketplaceId: "TOK-261005T7TU40CG",
    storeName: "Koperasi Sahabat Anak Bangsa",
    channel: "tokopedia",
    channelCode: "tokopedia",
    buyerName: "Ibu Nurul Hidayah",
    buyerPhone: "085611223344",
    buyerCity: "Bandung",
    courier: "SiCepat REG",
    trackingNumber: "004128919201",
    completedAt: "2026-10-10 21:40",
    status: "COMPLETED",
    quantity: 1,
    releasedValue: 84750,
    hppModal: 57400,
    margin: 27350,
    commission: 1915,
    commissionRate: 7.0,
    items: [
      {
        id: 2,
        productName: "Batik PGRI Kusuma Bangsa Katun Halus",
        sku: "BTK-PGRI-XL",
        size: "Size XL",
        quantity: 1,
        price: 92000,
        costPrice: 57400,
        marketplaceFee: 7250
      }
    ]
  },
  {
    id: 103,
    invoiceNumber: "261006U6GNX7C8",
    marketplaceId: "TIK-261006U6GNX7C8",
    storeName: "Koperasi Sahabat Anak Bangsa",
    channel: "tiktok",
    channelCode: "tiktok",
    buyerName: "Dewi Lestari",
    buyerPhone: "087899887766",
    buyerCity: "Surabaya",
    courier: "J&T Cargo",
    trackingNumber: "JT8829102931",
    completedAt: "2026-10-10 21:06",
    status: "COMPLETED",
    quantity: 1,
    releasedValue: 67550,
    hppModal: 53000,
    margin: 14550,
    commission: 1019,
    commissionRate: 7.0,
    items: [
      {
        id: 3,
        productName: "Rok Rempel Panjang SD Putih Tebal",
        sku: "ROK-SD-PUTIH-M",
        size: "Size M",
        quantity: 1,
        price: 74000,
        costPrice: 53000,
        marketplaceFee: 6450
      }
    ]
  },
  {
    id: 104,
    invoiceNumber: "261005TCC91RSJ",
    marketplaceId: "SHP-261005TCC91RSJ",
    storeName: "RUANG SERAGAM",
    channel: "shopee",
    channelCode: "shopee",
    buyerName: "Budi Santoso",
    buyerPhone: "081344556677",
    buyerCity: "Semarang",
    courier: "SPX Standard",
    trackingNumber: "SPXID02918291",
    completedAt: "2026-10-10 20:49",
    status: "COMPLETED",
    quantity: 1,
    releasedValue: 67330,
    hppModal: 53000,
    margin: 14330,
    commission: 1003,
    commissionRate: 7.0,
    items: [
      {
        id: 4,
        productName: "Celana Panjang Pramuka SMP Bahan Famatex",
        sku: "CLN-SMP-PRM-30",
        size: "No 30",
        quantity: 1,
        price: 73500,
        costPrice: 53000,
        marketplaceFee: 6170
      }
    ]
  },
  {
    id: 105,
    invoiceNumber: "2610071A8RE6FM",
    marketplaceId: "SHP-2610071A8RE6FM",
    storeName: "RUANG SERAGAM",
    channel: "shopee",
    channelCode: "shopee",
    buyerName: "Drs. Hendro Wibowo",
    buyerPhone: "082133445566",
    buyerCity: "Yogyakarta",
    courier: "J&T Express",
    trackingNumber: "JT7718291029",
    completedAt: "2026-10-10 20:35",
    status: "COMPLETED",
    quantity: 2,
    releasedValue: 258442,
    hppModal: 106000,
    margin: 152442,
    commission: 10671,
    commissionRate: 7.0,
    items: [
      {
        id: 5,
        productName: "Jas Almamater Kampus Bahan Wool Blend Premium",
        sku: "JAS-ALMA-XXL",
        size: "Size XXL",
        quantity: 2,
        price: 140000,
        costPrice: 53000,
        marketplaceFee: 21558
      }
    ]
  },
  {
    id: 106,
    invoiceNumber: "261002H7CWCR5F",
    marketplaceId: "TOK-261002H7CWCR5F",
    storeName: "NUSANTARA SERAGAM",
    channel: "tokopedia",
    channelCode: "tokopedia",
    buyerName: "Siti Rahmawati",
    buyerPhone: "081988776655",
    buyerCity: "Tangerang",
    courier: "Anteraja REG",
    trackingNumber: "100028919201",
    completedAt: "2026-10-10 19:46",
    status: "READY_TO_SHIP",
    quantity: 1,
    releasedValue: 71566,
    hppModal: 47900,
    margin: 23666,
    commission: 1657,
    commissionRate: 7.0,
    items: [
      {
        id: 6,
        productName: "Baju Kemeja Putih Polos SD Bahan Oxford",
        sku: "KMJ-SD-PUTIH-12",
        size: "No 12",
        quantity: 1,
        price: 78000,
        costPrice: 47900,
        marketplaceFee: 6434
      }
    ]
  },
  {
    id: 107,
    invoiceNumber: "586443892018415441",
    marketplaceId: "TIK-586443892018415441",
    storeName: "SERAGAM SEKOLAH STORE",
    channel: "tiktok",
    channelCode: "tiktok",
    buyerName: "Rina Kusuma",
    buyerPhone: "081277665544",
    buyerCity: "Bekasi",
    courier: "J&T Express",
    trackingNumber: "JT6615243819",
    completedAt: "2026-10-10 19:20",
    status: "SHIPPED",
    quantity: 1,
    releasedValue: 71009,
    hppModal: 49100,
    margin: 21909,
    commission: 1534,
    commissionRate: 7.0,
    items: [
      {
        id: 7,
        productName: "Dasi & Gesper Ikat Pinggang Setelan SMP",
        sku: "SET-ACC-SMP",
        size: "All Size",
        quantity: 1,
        price: 77000,
        costPrice: 49100,
        marketplaceFee: 5991
      }
    ]
  }
];
