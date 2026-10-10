/**
 * Format numbers as Indonesian Rupiah string
 */
export function formatRupiah(value) {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0';
  return 'Rp ' + Math.round(Number(value)).toLocaleString('id-ID');
}

/**
 * Format date string to readable Indonesian date
 */
export function formatIndonesianDate(dateObj = new Date()) {
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
  ];

  const d = new Date(dateObj);
  const dayName = days[d.getDay()];
  const dateNum = d.getDate();
  const monthName = months[d.getMonth()];
  const year = d.getFullYear();

  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const seconds = String(d.getSeconds()).padStart(2, '0');

  return {
    fullText: `${dayName}, ${dateNum} ${monthName} ${year} • ${hours}:${minutes}:${seconds} WIB`,
    timeText: `${hours}:${minutes}:${seconds}`,
    dateOnly: `${dateNum} ${monthName} ${year}`
  };
}

/**
 * Get channel color class and label
 */
export function getChannelBadge(channelName = '') {
  const ch = String(channelName).toLowerCase();
  if (ch.includes('shopee')) {
    return { class: 'shopee', label: 'Shopee', color: '#ee4d2d' };
  }
  if (ch.includes('tiktok')) {
    return { class: 'tiktok', label: 'TikTok Shop', color: '#111827' };
  }
  if (ch.includes('tokopedia')) {
    return { class: 'tokopedia', label: 'Tokopedia', color: '#03ac0e' };
  }
  return { class: 'pos', label: 'POS / Store', color: '#4f46e5' };
}
