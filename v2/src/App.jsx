import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import Produk from './pages/Produk';
import Login from './pages/Login';

// Protected Route Component: checks for v2_token in localStorage
const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('v2_token');
  if (!token) {
    return <Navigate to="/login" replace />;
  }
  return children;
};

// Main Layout for authenticated pages
const AppLayout = ({ children }) => {
  return (
    <div className="v2-wrapper">
      <Sidebar />
      <main className="v2-main-content">
        <Header />
        <div className="v2-body-content">
          {children}
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
  );
};

// Public Route (Login): if already logged in, redirect to dashboard
const PublicOnlyRoute = ({ children }) => {
  const token = localStorage.getItem('v2_token');
  if (token) {
    return <Navigate to="/" replace />;
  }
  return children;
};

function App() {
  return (
    <BrowserRouter basename="/v2">
      <Routes>
        {/* Login Route (Public Only) */}
        <Route 
          path="/login" 
          element={
            <PublicOnlyRoute>
              <Login />
            </PublicOnlyRoute>
          } 
        />

        {/* Protected Application Routes */}
        <Route 
          path="/" 
          element={
            <ProtectedRoute>
              <AppLayout>
                <Dashboard />
              </AppLayout>
            </ProtectedRoute>
          } 
        />
        <Route 
          path="/dashboard" 
          element={
            <ProtectedRoute>
              <AppLayout>
                <Dashboard />
              </AppLayout>
            </ProtectedRoute>
          } 
        />
        <Route 
          path="/produk" 
          element={
            <ProtectedRoute>
              <AppLayout>
                <Produk />
              </AppLayout>
            </ProtectedRoute>
          } 
        />

        {/* Fallback to root */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;

