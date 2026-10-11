import React from 'react';
import { ArrowLeft } from 'lucide-react';

export default function SubPageHeader({ title, subtitle, onBack, rightAction }) {
  return (
    <header className="subpage-header">
      <div className="subpage-header-row">
        {onBack ? (
          <button 
            type="button" 
            className="subpage-back-btn" 
            onClick={onBack}
            aria-label="Kembali"
          >
            <ArrowLeft size={20} strokeWidth={2.4} />
          </button>
        ) : (
          <div style={{ width: '40px' }} />
        )}

        <div className="subpage-title-wrap">
          <h2 className="subpage-title">{title}</h2>
          {subtitle && <p className="subpage-subtitle">{subtitle}</p>}
        </div>

        <div className="subpage-right-action">
          {rightAction || <div style={{ width: '40px' }} />}
        </div>
      </div>
    </header>
  );
}
