import React from 'react';
import MutasiKeuanganPage from './MutasiKeuanganPage';

export default function FinancePage({ metrics }) {
  // Langsung tampilkan Mutasi Keuangan (keluar masuk kas) tanpa tab
  return <MutasiKeuanganPage initialMetrics={metrics} />;
}
