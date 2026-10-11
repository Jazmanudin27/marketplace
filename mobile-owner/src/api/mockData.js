// Default empty schemas for initial component states (NO FAKE / MOCK NUMBERS)

export const initialOwnerProfile = {
  name: "",
  role: "Owner",
  tenantName: "",
  avatarInitials: "--",
  email: "",
  phone: "",
  systemStatus: "Online",
  storesCount: 0,
  connectedMarketplaces: []
};

export const initialOwnerMetrics = {
  ordersNew: 0,
  ordersToShip: 0,
  ordersReturned: 0,
  ordersCompletedToday: 0,
  
  todayOmset: 0,
  todayMargin: 0,
  todayHpp: 0,
  todayMarketplaceFee: 0,
  todayRefund: 0,
  
  targetMonthlyMargin: 0,
  actualMonthlyMargin: 0,
  targetProgressPercent: 0,
  totalEarnedCommission: 0,

  cashBalance: 0,
  receivableEscrow: 0,
  storesCount: 0,
  connectedMarketplaces: []
};

export const initialOrders = [];
