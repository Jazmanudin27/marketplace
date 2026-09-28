import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import Produk from './pages/Produk';

function App() {
  return (
    <BrowserRouter basename="/v2">
      <div className="v2-wrapper">
        <Sidebar />
        <main className="v2-main-content">
          <Header />
          <div className="v2-body-content">
            <Routes>
              <Route path="/" element={<Dashboard />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/produk" element={<Produk />} />
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </div>
          <footer className="v2-footer">
            <div>
              <strong>ASPARTECH ERP Portal V2 (React JS)</strong> &copy; {new Date().getFullYear()} — All Rights Reserved.
            </div>
            <div className="d-flex align-items-center gap-2">
              <span className="badge bg-success-subtle text-success py-1 px-2" style={{ fontSize: '0.65rem' }}>React SPA Ready</span>
              <span>v2.0 Standalone</span>
            </div>
          </footer>
        </main>
      </div>
    </BrowserRouter>
  );
}

export default App;
