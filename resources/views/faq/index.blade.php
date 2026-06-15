@extends('layouts.app')
@section('title', 'Pusat Bantuan & Tutorial')
@section('page-title', 'Bantuan & Tutorial')

@push('styles')
    <style>
        .faq-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            animation: fadeInPage 0.5s ease-out;
        }

        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Glassmorphism Header */
        .faq-hero {
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.12) 0%, rgba(139, 92, 246, 0.08) 50%, rgba(15, 17, 23, 0) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 3.5rem 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .faq-hero::before {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, var(--primary) 0%, rgba(108, 99, 255, 0) 70%);
            filter: blur(50px);
            opacity: 0.25;
            top: -100px;
            right: -100px;
            z-index: 0;
        }

        .faq-hero::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--purple) 0%, rgba(139, 92, 246, 0) 70%);
            filter: blur(50px);
            opacity: 0.2;
            bottom: -80px;
            left: -80px;
            z-index: 0;
        }

        .faq-hero-content {
            position: relative;
            z-index: 1;
        }

        .faq-hero h2 {
            font-weight: 800;
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #FFF 30%, #C7D2FE 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .faq-hero p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            max-width: 650px;
            margin: 0 auto 1.75rem;
            line-height: 1.5;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            max-width: 550px;
            margin: 0 auto 1.25rem;
        }

        .search-input {
            width: 100%;
            background: rgba(15, 17, 23, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 99px;
            padding: 1.1rem 1.5rem 1.1rem 3.5rem;
            color: var(--text-primary);
            font-size: 1rem;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow), inset 0 2px 4px rgba(0, 0, 0, 0.2);
            background: rgba(15, 17, 23, 0.8);
        }

        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .search-input:focus+.search-icon {
            color: var(--primary);
        }

        /* Popular keywords tags */
        .popular-tags {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .popular-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .tag-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-secondary);
            padding: 0.35rem 0.85rem;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tag-pill:hover {
            background: rgba(108, 99, 255, 0.12);
            border-color: var(--primary);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        /* Two Column Grid Layout */
        .faq-grid-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Sidebar navigation */
        .faq-sidebar {
            position: sticky;
            top: calc(var(--topbar-h) + 1.5rem);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .category-nav-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-align: left;
            width: 100%;
        }

        .category-nav-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0px;
            background: var(--cat-color);
            transition: width 0.2s ease;
        }

        .category-nav-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.03);
            transform: translateX(4px);
        }

        .category-nav-card.active {
            background: rgba(var(--cat-color-rgb), 0.06);
            border-color: rgba(var(--cat-color-rgb), 0.3);
            box-shadow: 0 4px 20px rgba(var(--cat-color-rgb), 0.08);
        }

        .category-nav-card.active::before {
            width: 4px;
        }

        /* Category Specific Colors */
        .cat-integrasi {
            --cat-color: #6C63FF;
            --cat-color-rgb: 108, 99, 255;
        }

        .cat-produk {
            --cat-color: #8B5CF6;
            --cat-color-rgb: 139, 92, 246;
        }

        .cat-transaksi {
            --cat-color: #10B981;
            --cat-color-rgb: 16, 185, 129;
        }

        .cat-stok {
            --cat-color: #F59E0B;
            --cat-color-rgb: 245, 158, 11;
        }

        .cat-keuangan {
            --cat-color: #EF4444;
            --cat-color-rgb: 239, 68, 68;
        }

        .cat-chat {
            --cat-color: #06B6D4;
            --cat-color-rgb: 6, 182, 212;
        }

        .cat-pos {
            --cat-color: #EC4899;
            --cat-color-rgb: 236, 72, 153;
        }

        .cat-akses {
            --cat-color: #8492A6;
            --cat-color-rgb: 132, 146, 166;
        }

        .cat-laporan {
            --cat-color: #3B82F6;
            --cat-color-rgb: 59, 130, 246;
        }

        .cat-voucher {
            --cat-color: #F59E0B;
            --cat-color-rgb: 245, 158, 11;
        }

        .cat-pengaturan {
            --cat-color: #10B981;
            --cat-color-rgb: 16, 185, 129;
        }

        .cat-icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            transition: all 0.3s;
            background: rgba(var(--cat-color-rgb), 0.12);
            color: var(--cat-color);
            box-shadow: inset 0 0 12px rgba(var(--cat-color-rgb), 0.08);
        }

        .category-nav-card.active .cat-icon-wrap {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(var(--cat-color-rgb), 0.25);
            background: var(--cat-color);
            color: #fff;
        }

        .cat-info {
            flex: 1;
            min-width: 0;
        }

        .cat-info h4 {
            font-weight: 700;
            font-size: 0.95rem;
            margin: 0;
            color: var(--text-primary);
        }

        .cat-info p {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin: 0.15rem 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cat-badge {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 99px;
            transition: all 0.3s;
        }

        .category-nav-card.active .cat-badge {
            background: rgba(var(--cat-color-rgb), 0.15);
            border-color: rgba(var(--cat-color-rgb), 0.25);
            color: var(--cat-color);
        }

        /* Detail Content Cards */
        .content-card-wrapper {
            min-width: 0;
        }

        .content-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.25rem;
            box-shadow: var(--shadow);
            display: none;
            opacity: 0;
            transform: translateY(15px);
            transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .content-card.show-active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .content-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .content-header-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(var(--cat-color-rgb), 0.15);
            color: var(--cat-color);
            box-shadow: 0 0 24px rgba(var(--cat-color-rgb), 0.1);
        }

        .content-header-title h3 {
            font-weight: 800;
            font-size: 1.4rem;
            margin: 0;
            color: var(--text-primary);
        }

        .content-header-title p {
            font-size: 0.88rem;
            color: var(--text-secondary);
            margin: 0.3rem 0 0;
        }

        .content-header-meta {
            margin-left: auto;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: right;
        }

        /* Timeline Workflow */
        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .section-title-wrap h4 {
            font-weight: 800;
            font-size: 1.15rem;
            margin: 0;
            color: var(--text-primary);
        }

        .section-title-wrap i {
            color: var(--cat-color);
        }

        .workflow-timeline {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            padding-left: 1rem;
            margin-bottom: 3rem;
        }

        .workflow-timeline::before {
            content: '';
            position: absolute;
            left: 27px;
            top: 15px;
            bottom: 15px;
            width: 2px;
            background: linear-gradient(to bottom, var(--cat-color) 0%, var(--border) 100%);
            z-index: 1;
        }

        .timeline-item {
            display: flex;
            gap: 1.5rem;
            position: relative;
        }

        .timeline-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--bg-card2);
            border: 2px solid var(--cat-color);
            color: var(--cat-color);
            font-weight: 800;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            box-shadow: 0 0 10px rgba(var(--cat-color-rgb), 0.15);
            flex-shrink: 0;
        }

        .timeline-content {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            flex: 1;
            transition: all 0.3s;
        }

        .timeline-item:hover .timeline-content {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(var(--cat-color-rgb), 0.25);
            transform: translateY(-2px);
        }

        .timeline-content h5 {
            font-weight: 700;
            font-size: 0.98rem;
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }

        .timeline-content p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        /* FAQ Accordions */
        .faq-accordion {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .accordion-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-item:hover {
            border-color: rgba(var(--cat-color-rgb), 0.3);
            background: rgba(255, 255, 255, 0.04);
        }

        .accordion-item.active {
            border-color: rgba(var(--cat-color-rgb), 0.4);
            background: rgba(var(--cat-color-rgb), 0.02);
            box-shadow: 0 4px 20px rgba(var(--cat-color-rgb), 0.08);
        }

        .accordion-trigger {
            width: 100%;
            background: none;
            border: none;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-align: left;
            outline: none;
            transition: background 0.2s;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.16, 1, 0.3, 1), padding 0.3s ease-out;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
            background: rgba(0, 0, 0, 0.15);
            padding: 0 1.5rem;
        }

        .accordion-item.active .accordion-content {
            padding: 1.1rem 1.5rem 1.5rem 1.5rem;
        }

        .accordion-trigger i {
            font-size: 0.85rem;
            transition: transform 0.3s ease;
            color: var(--text-muted);
        }

        .accordion-item.active .accordion-trigger i {
            transform: rotate(180deg);
            color: var(--cat-color);
        }

        /* Badges */
        .flow-badge {
            background: rgba(108, 99, 255, 0.15);
            color: #A39EFC;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
            vertical-align: middle;
            border: 1px solid rgba(108, 99, 255, 0.25);
        }

        /* Feedback Widget */
        .feedback-widget {
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .feedback-question {
            font-size: 0.88rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .feedback-btns {
            display: flex;
            gap: 0.75rem;
        }

        .feedback-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .feedback-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        .feedback-btn.yes:hover {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.08);
            color: var(--success);
        }

        .feedback-btn.no:hover {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.08);
            color: var(--danger);
        }

        .feedback-response {
            display: none;
            font-size: 0.88rem;
            color: var(--success);
            font-weight: 600;
            animation: fadeInFeedback 0.3s ease forwards;
        }

        @keyframes fadeInFeedback {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Extra Help desk contacts */
        .extra-help-section {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .help-card {
            background: linear-gradient(135deg, rgba(26, 29, 39, 0.8) 0%, rgba(31, 35, 51, 0.8) 100%);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.75rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            transition: all 0.3s;
        }

        .help-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary-glow);
            box-shadow: var(--shadow);
        }

        .help-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
            flex-shrink: 0;
        }

        .help-card-icon.whatsapp {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .help-card-info h5 {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0 0 0.3rem 0;
            color: var(--text-primary);
        }

        .help-card-info p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0 0 1rem 0;
            line-height: 1.4;
        }

        .help-card-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .help-card-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .whatsapp .help-card-btn:hover {
            background: var(--success);
            border-color: var(--success);
        }

        /* Text highlighting class */
        .search-highlight {
            background: rgba(245, 158, 11, 0.3);
            border-bottom: 2px solid var(--warning);
            color: #fff;
            padding: 0 2px;
            border-radius: 2px;
        }

        /* Not found search state */
        .search-not-found {
            display: none;
            text-align: center;
            padding: 3rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        .search-not-found i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .search-not-found h4 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .search-not-found p {
            color: var(--text-secondary);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .faq-grid-layout {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .faq-sidebar {
                position: static;
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 0.75rem;
                scrollbar-width: none;
            }

            .faq-sidebar::-webkit-scrollbar {
                display: none;
            }

            .category-nav-card {
                flex: 0 0 auto;
                width: 260px;
                padding: 0.85rem 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="faq-wrapper">
        <!-- Hero Header -->
        <div class="faq-hero">
            <div class="faq-hero-content">
                <h2>Pusat Bantuan & Panduan ERP</h2>
                <p>Temukan panduan alur kerja sistem, solusi pemecahan masalah (FAQ), dan panduan langkah demi langkah untuk
                    memaksimalkan operasional bisnis retail multi-channel Anda.</p>

                <div class="search-container">
                    <input type="text" id="faq-search" class="search-input"
                        placeholder="Cari tutorial atau kata kunci (misal: 'mapping', 'shopee', 'retur', 'pos')...">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <div class="popular-tags">
                    <span class="popular-label">Pencarian Populer:</span>
                    <button class="tag-pill" data-query="Mapping SKU">Mapping SKU</button>
                    <button class="tag-pill" data-query="Re-otorisasi">Re-otorisasi</button>
                    <button class="tag-pill" data-query="Scan Kemas">Scan Kemas</button>
                    <button class="tag-pill" data-query="Stok Opname">Stok Opname</button>
                    <button class="tag-pill" data-query="Biaya Admin">Biaya Admin</button>
                    <button class="tag-pill" data-query="POS Offline">POS Offline</button>
                    <button class="tag-pill" data-query="Inbox Chat">Inbox Chat</button>
                </div>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div class="faq-grid-layout">
            <!-- Sidebar Navigation -->
            <div class="faq-sidebar">
                <button class="category-nav-card active cat-integrasi" data-category="integrasi">
                    <div class="cat-icon-wrap"><i class="fas fa-plug"></i></div>
                    <div class="cat-info">
                        <h4>Integrasi Toko</h4>
                        <p>Shopee & TikTok Shop</p>
                    </div>
                    <span class="cat-badge" id="badge-integrasi">6</span>
                </button>

                <button class="category-nav-card cat-produk" data-category="produk">
                    <div class="cat-icon-wrap"><i class="fas fa-box-open"></i></div>
                    <div class="cat-info">
                        <h4>Produk & Mapping</h4>
                        <p>Inventori Terpusat & SKU</p>
                    </div>
                    <span class="cat-badge" id="badge-produk">6</span>
                </button>

                <button class="category-nav-card cat-transaksi" data-category="transaksi">
                    <div class="cat-icon-wrap"><i class="fas fa-shopping-cart"></i></div>
                    <div class="cat-info">
                        <h4>Pesanan & Fulfillment</h4>
                        <p>Proses Order & Scan</p>
                    </div>
                    <span class="cat-badge" id="badge-transaksi">7</span>
                </button>

                <button class="category-nav-card cat-stok" data-category="stok">
                    <div class="cat-icon-wrap"><i class="fas fa-boxes"></i></div>
                    <div class="cat-info">
                        <h4>Stok & Opname</h4>
                        <p>Barang Masuk & Audit</p>
                    </div>
                    <span class="cat-badge" id="badge-stok">6</span>
                </button>

                <button class="category-nav-card cat-keuangan" data-category="keuangan">
                    <div class="cat-icon-wrap"><i class="fas fa-wallet"></i></div>
                    <div class="cat-info">
                        <h4>Keuangan & Profit</h4>
                        <p>HPP & Rekonsiliasi Bank</p>
                    </div>
                    <span class="cat-badge" id="badge-keuangan">7</span>
                </button>

                <button class="category-nav-card cat-chat" data-category="chat">
                    <div class="cat-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div class="cat-info">
                        <h4>Inbox Chat</h4>
                        <p>Balas Chat Multi-Toko</p>
                    </div>
                    <span class="cat-badge" id="badge-chat">6</span>
                </button>

                <button class="category-nav-card cat-pos" data-category="pos">
                    <div class="cat-icon-wrap"><i class="fas fa-store-slash"></i></div>
                    <div class="cat-info">
                        <h4>POS Offline</h4>
                        <p>Kasir Toko Fisik</p>
                    </div>
                    <span class="cat-badge" id="badge-pos">7</span>
                </button>

                <button class="category-nav-card cat-akses" data-category="akses">
                    <div class="cat-icon-wrap"><i class="fas fa-user-shield"></i></div>
                    <div class="cat-info">
                        <h4>Karyawan & Akses</h4>
                        <p>Hak Akses & Role</p>
                    </div>
                    <span class="cat-badge" id="badge-akses">7</span>
                </button>

                <button class="category-nav-card cat-laporan" data-category="laporan">
                    <div class="cat-icon-wrap"><i class="fas fa-chart-pie"></i></div>
                    <div class="cat-info">
                        <h4>Laporan & Analitik</h4>
                        <p>Turnover & Prediksi Stok</p>
                    </div>
                    <span class="cat-badge" id="badge-laporan">5</span>
                </button>

                <button class="category-nav-card cat-voucher" data-category="voucher">
                    <div class="cat-icon-wrap"><i class="fas fa-ticket-alt"></i></div>
                    <div class="cat-info">
                        <h4>Voucher & Promosi</h4>
                        <p>Campaign & Subsidi Diskon</p>
                    </div>
                    <span class="cat-badge" id="badge-voucher">5</span>
                </button>

                <button class="category-nav-card cat-pengaturan" data-category="pengaturan">
                    <div class="cat-icon-wrap"><i class="fas fa-cog"></i></div>
                    <div class="cat-info">
                        <h4>Setelan & Kurir</h4>
                        <p>Tenant & Ekspedisi Logistik</p>
                    </div>
                    <span class="cat-badge" id="badge-pengaturan">5</span>
                </button>
            </div>

            <!-- Content Area -->
            <div class="content-card-wrapper">
                <!-- Searching Not Found Screen -->
                <div class="search-not-found" id="search-empty">
                    <i class="fas fa-search-minus"></i>
                    <h4>Pencarian Tidak Ditemukan</h4>
                    <p>Maaf, kami tidak dapat menemukan hasil untuk kata kunci tersebut. Coba gunakan kata kunci lainnya.
                    </p>
                </div>

                <!-- ==================== INTEGRASI TOKO ==================== -->
                <div class="content-card show-active cat-integrasi" data-category="integrasi">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-plug"></i></div>
                        <div class="content-header-title">
                            <h3>Integrasi Toko & Otorisasi Marketplace</h3>
                            <p>Cara menghubungkan toko Shopee dan TikTok Shop ke dalam sistem ERP</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Otorisasi</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Buka Menu Kelola Toko</h5>
                                <p>Klik menu <strong>Kelola Toko</strong> pada panel navigasi INTEGRASI di sisi kiri layar
                                    untuk membuka halaman dashboard toko.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Tambah Toko & Pilih Channel</h5>
                                <p>Klik tombol <strong>Tambah Toko / Sambungkan</strong>, lalu pilih channel toko online
                                    yang ingin diintegrasikan (Shopee atau TikTok Shop).</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Otorisasi Keamanan API</h5>
                                <p>Sistem akan mengarahkan (redirect) Anda ke portal login resmi marketplace. Masukkan
                                    kredensial toko Anda dan berikan izin otorisasi data kepada ERP.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">4</div>
                            <div class="timeline-content">
                                <h5>Sinkronisasi Data Otomatis <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Setelah disetujui, Anda dialihkan kembali ke ERP. Status toko Anda akan berubah menjadi
                                    <strong style="color:var(--success);">Aktif</strong> dan ERP akan otomatis mengimpor
                                    data awal.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana jika masa aktif otorisasi token toko habis? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Token API Shopee biasanya berlaku selama 1 tahun, sedangkan TikTok Shop bertahan selama 30
                                hari. Jika status toko berubah menjadi "Expired", Anda hanya perlu mengklik tombol
                                <strong>Hubungkan Ulang (Re-authorize)</strong> pada halaman Kelola Toko. Jangan hapus data
                                toko lama agar data transaksi sejarah tidak terduplikasi.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah integrasi Tokopedia juga didukung? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Ya. Melalui kerja sama sistematis, otorisasi Tokopedia saat ini terintegrasi langsung dengan
                                TikTok Shop OAuth. Menghubungkan akun TikTok Shop Anda otomatis mensinkronisasikan inventori
                                serta pesanan Tokopedia Anda.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== PRODUK & MAPPING ==================== -->
                <div class="content-card cat-produk" data-category="produk">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-box-open"></i></div>
                        <div class="content-header-title">
                            <h3>Master Produk & Pemetaan (Product Mapping)</h3>
                            <p>Konsep inventori terpusat untuk menghubungkan variasi SKU antar marketplace</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 4 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Sinkronisasi SKU</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Tarik Data Produk Online</h5>
                                <p>Masuk ke menu <strong>Marketplace Produk</strong>, lalu klik tombol <strong>Tarik Produk
                                        Terbaru</strong> untuk memicu pengambilan data SKU mentah dari API Shopee & TikTok.
                                </p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Buat atau Tentukan Master Product</h5>
                                <p>Buat Master Product lokal di gudang Anda yang bertindak sebagai database persediaan pusat
                                    fisik.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Petakan (Mapping) SKU Marketplace</h5>
                                <p>Tautkan item produk marketplace ke SKU Master Product yang bersangkutan. Anda dapat
                                    memetakan banyak produk marketplace yang berbeda nama ke satu SKU Master gudang yang
                                    sama.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">4</div>
                            <div class="timeline-content">
                                <h5>Aktifkan Sync Stok Otomatis <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Nyalakan switch <strong>Sinkronisasi Otomatis</strong> agar setiap perubahan stok di
                                    Master Product langsung dikirim ke seluruh marketplace terkait dalam hitungan detik.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Mengapa satu Master Product perlu dipetakan ke banyak SKU
                                online? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Misalnya Anda menjual "Kaos Polos Hitam" di Shopee dengan SKU `shopee-kaos-black` dan di
                                TikTok dengan SKU `tiktok-kaos-hitam`. Dengan memetakan kedua SKU ini ke satu Master SKU
                                "Kaos Polos Hitam" di ERP, maka ketika kaos terjual di Shopee, stok master berkurang dan
                                sistem langsung mengupdate sisa stok kaos di TikTok Shop agar stok sinkron di semua toko.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana cara kerja Safety Stock (Stok Pengaman)? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Safety Stock berfungsi sebagai cadangan fisik di gudang. Jika Anda menyetel Safety Stock
                                sebanyak 2 unit, dan stok Master Anda sisa 2 unit, sistem akan mengirimkan data stok
                                bernilai 0 ke marketplace online agar produk tidak bisa dibeli lagi, menghindari risiko
                                overselling jika ternyata ada fisik barang yang cacat/rusak di gudang.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== PESANAN & FULFILLMENT ==================== -->
                <div class="content-card cat-transaksi" data-category="transaksi">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="content-header-title">
                            <h3>Manajemen Pesanan & Fulfillment (Scan Kemas)</h3>
                            <p>Alur memproses pesanan masuk hingga penarikan resi dan update status pengiriman</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 4 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Fulfillment Gudang</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Tarik Pesanan Baru</h5>
                                <p>Pergi ke menu <strong>Pesanan Masuk</strong>, lalu klik tombol <strong>Tarik Pesanan
                                        Terbaru</strong> untuk menyinkronkan daftar orderan terbaru dari Shopee dan TikTok
                                    Shop.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Booking Kurir & Cetak Resi</h5>
                                <p>Klik tombol <strong>Proses Pengiriman</strong> pada invoice pesanan untuk meminta nomor
                                    resi / AWB resmi dari kurir. Setelah disetujui, cetak label pengiriman thermal secara
                                    massal.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Scan Barcode Resi (Fulfillment Scan)</h5>
                                <p>Buka halaman <strong>Kemas Pesanan (Scan)</strong>, lalu scan barcode resi pengiriman
                                    yang tertera pada paket menggunakan barcode scanner Anda.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">4</div>
                            <div class="timeline-content">
                                <h5>Verifikasi Barang & Potong Stok <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Scan barcode produk fisik satu per satu untuk memvalidasi isinya. Setelah isi paket
                                    terkonfirmasi valid, sistem otomatis memperbarui status pesanan menjadi "Siap Dikirim"
                                    ke marketplace dan memotong stok gudang lokal.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana memproses barang retur (pengembalian)? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Masuk ke menu <strong>Pesanan Retur</strong>, klik <strong>Tarik Retur Terbaru</strong>
                                untuk mengambil data dari marketplace. Setelah paket retur sampai secara fisik di gudang
                                Anda, klik tombol <strong>Kembalikan ke Stok (Restock)</strong> agar sistem mengembalikan
                                jumlah stok produk tersebut ke database gudang pusat.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah kami bisa mengemas pesanan tanpa menggunakan scanner
                                barcode? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Bisa. Pada halaman Kemas Pesanan (Scan), Anda dapat mengetikkan ID Invoice atau nomor resi
                                secara manual pada kolom input pencarian jika scanner barcode Anda tidak aktif.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Mengapa resi gagal ditarik (Error AWB)? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Hal ini biasanya terjadi jika server kurir logistik dari pihak marketplace sedang mengalami
                                gangguan (overload). Tunggu beberapa menit kemudian klik tombol <strong>Tarik Resi
                                    Ulang</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== STOK & OPNAME ==================== -->
                <div class="content-card cat-stok" data-category="stok">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-boxes"></i></div>
                        <div class="content-header-title">
                            <h3>Inventori, Barang Masuk, & Opname Stok</h3>
                            <p>Mengelola pasokan barang fisik di gudang serta penyesuaian selisih stok database vs fisik</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Kelola Persediaan</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Penerimaan Barang (Barang Masuk)</h5>
                                <p>Ketika menerima pasokan dari Supplier, buat nota <strong>Barang Masuk</strong> baru,
                                    tentukan supplier, lalu masukkan daftar produk dan kuantitasnya. Stok Master Anda akan
                                    bertambah otomatis.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Pemantauan Kartu Stok (Stock Ledger)</h5>
                                <p>Gunakan menu <strong>Kartu Stok</strong> untuk mengaudit riwayat pergerakan keluar-masuk
                                    barang secara rinci (misal: pengurangan order online, penambahan barang masuk, retur).
                                </p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Opname Stok (Penyesuaian Fisik) <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Jika terjadi selisih stok sistem vs fisik gudang, buat dokumen penyesuaian di menu
                                    <strong>Opname Stok</strong>. Masukkan kuantitas riil fisik, sistem akan mencatat
                                    selisih rugi/lebihnya.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Mengapa stok di sistem bisa berbeda dengan stok fisik di
                                gudang? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Selisih stok sering disebabkan oleh barang rusak/cacat yang tidak tercatat, salah packing
                                saat pengiriman, barang retur tidak di-input ulang, atau kehilangan fisik. Disarankan
                                melakukan audit **Opname Stok** minimal sebulan sekali.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah saat kita melakukan barang masuk, stok di marketplace
                                langsung berubah? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Ya. Jika produk tersebut telah ter-mapping ke Master Product dan status Sinkronisasi Stok
                                Aktif, penambahan stok lokal (dari Barang Masuk / Opname) akan memicu update otomatis ke
                                seluruh toko marketplace Anda.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== KEUANGAN & LABA RUGI ==================== -->
                <div class="content-card cat-keuangan" data-category="keuangan">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-wallet"></i></div>
                        <div class="content-header-title">
                            <h3>Laporan Profitabilitas & Rekonsiliasi Dana</h3>
                            <p>Pemantauan laba bersih usaha dan pencocokan uang pencairan dari marketplace</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 4 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Pencatatan Keuangan</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Input Nilai HPP (COGS) Produk</h5>
                                <p>Pastikan Anda mengisi harga modal dasar (HPP) di setiap Master Product. HPP ini penting
                                    untuk menghitung margin keuntungan dari setiap transaksi penjualan secara akurat.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Pantau Laporan Profit per Order</h5>
                                <p>Setiap pesanan selesai akan dihitung profitnya secara otomatis di menu <strong>Laporan
                                        Profit / Pesanan</strong> dengan mengurangkan Omset Kotor dengan HPP dan komisi
                                    admin.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Lakukan Rekonsiliasi Dana Bank</h5>
                                <p>Gunakan menu <strong>Rekonsiliasi Keuangan</strong> untuk membandingkan uang rilis
                                    marketplace dengan uang yang masuk ke rekening bank Anda secara otomatis.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">4</div>
                            <div class="timeline-content">
                                <h5>Pencatatan Biaya Pengeluaran Operasional <span class="flow-badge">Alur Selesai</span>
                                </h5>
                                <p>Catat pengeluaran rutin non-produk (ongkos kirim selisih, lakban, gaji staff, iklan, dll)
                                    pada menu <strong>Pengeluaran & Biaya</strong> untuk menghasilkan laporan laba rugi
                                    bulanan bersih.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Dari mana sistem mendeteksi biaya komisi administrasi
                                marketplace? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Sistem ERP Marketplace menarik rincian biaya komisi, biaya layanan promosi (seperti gratis
                                ongkir ekstra), serta potongan voucher belanja langsung dari log settlement API pesanan
                                Shopee & TikTok secara berkala ketika sinkronisasi pesanan dijalankan.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Mengapa pencairan dana bank saya berbeda dengan omset kotor
                                pesanan? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Perbedaan ini disebabkan oleh biaya potongan admin, biaya pengiriman jika terjadi perbedaan
                                berat paket (charge selisih ongkir), dan diskon promosi yang ditanggung penjual. Gunakan
                                menu <strong>Rekonsiliasi Keuangan</strong> untuk menganalisis jika ada selisih potongan
                                ilegal.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== INBOX CHAT MULTI-TOKO ==================== -->
                <div class="content-card cat-chat" data-category="chat">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-comments"></i></div>
                        <div class="content-header-title">
                            <h3>Inbox Chat Multi-Toko (Customer Service)</h3>
                            <p>Membalas chat dari Shopee & TikTok Shop secara langsung dalam satu dasbor ERP terpusat</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Inbox Chat</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Menerima Chat Pelanggan</h5>
                                <p>Setiap pesan baru dari pembeli di Shopee atau TikTok Shop otomatis disinkronkan ke menu
                                    <strong>Inbox Chat</strong> dalam hitungan detik tanpa perlu membuka web seller-center
                                    terpisah.
                                </p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Gunakan Fitur Auto-Template (Quick Reply)</h5>
                                <p>Pilih pesan template cepat yang sudah dikonfigurasi sebelumnya (seperti info ukuran, stok
                                    ready, dll) untuk membalas pembeli secara cepat dan konsisten.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Kirim Rekomendasi Produk Langsung <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Klik tombol rekomendasi produk di dalam panel chat, pilih item dari master produk ERP,
                                    dan kirimkan link card produk tersebut langsung ke ruang obrolan pelanggan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah membalas chat lewat ERP mempengaruhi performa
                                persentase chat toko saya? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Ya. Karena ERP menggunakan koneksi API resmi dari Shopee dan TikTok Shop, semua pesan yang
                                dikirim dari sistem ini dihitung sebagai respon chat yang valid dan akan membantu menjaga
                                performa persentase chat toko Anda tetap tinggi.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Berapa lama delay sinkronisasi chat? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Sinkronisasi pesan berjalan secara real-time melalui webhook API. Rata-rata delay pesan
                                masuk berkisar antara 1 hingga 4 detik tergantung pada beban server API dari marketplace
                                tersebut.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah inbox chat mendukung pengiriman media (gambar/video)?
                                <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Pengiriman gambar sepenuhnya didukung langsung dari dasbor ERP. Namun, untuk pengiriman dan
                                pemutaran file video, saat ini belum diakomodasi oleh API marketplace, sehingga Anda harus
                                membukanya melalui web seller-center resmi.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== POS OFFLINE ==================== -->
                <div class="content-card cat-pos" data-category="pos">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-store-slash"></i></div>
                        <div class="content-header-title">
                            <h3>POS Offline (Point of Sale / Kasir Fisik)</h3>
                            <p>Melayani penjualan langsung secara fisik di toko/butik offline Anda dan memotong stok gudang
                                secara real-time</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 4 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Transaksi Kasir POS</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Buka Dasbor Penjualan Offline</h5>
                                <p>Buka halaman <strong>Penjualan Offline</strong> untuk memuat modul kasir (POS). Modul ini
                                    dioptimalkan agar ringan saat digunakan bertransaksi.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Scan Barcode Barang Belanjaan</h5>
                                <p>Gunakan barcode scanner untuk memindai barcode produk fisik pembeli. Produk otomatis
                                    masuk ke keranjang belanja beserta harga modal & diskon yang aktif.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Pilih Metode Pembayaran & Checkout</h5>
                                <p>Pilih metode pembayaran (Tunai, Kartu Debit, atau Qris Dinamis). Masukkan nominal uang
                                    tunai yang diterima untuk menghitung kembalian.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">4</div>
                            <div class="timeline-content">
                                <h5>Cetak Struk Belanja & Update Stok <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Tekan tombol <strong>Bayar & Cetak</strong> untuk mencetak nota melalui printer thermal
                                    thermal USB/Bluetooth. Stok master gudang langsung terpotong saat itu juga dan merilis
                                    update ke toko online.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah penjualan kasir offline akan langsung memotong stok di
                                Shopee/TikTok? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Ya. Transaksi POS offline langsung memotong kuantitas Master Product di gudang lokal Anda.
                                Sistem kemudian langsung mendorong perubahan sisa stok terbaru ke seluruh etalase
                                marketplace yang terhubung dalam waktu < 5 detik untuk menghindari tabrakan stok dengan
                                    pembeli online. </div>
                            </div>
                            <div class="accordion-item">
                                <button class="accordion-trigger">Apakah POS offline kasir bisa dijalankan tanpa koneksi
                                    internet? <i class="fas fa-chevron-down"></i></button>
                                <div class="accordion-content">
                                    Tidak. Karena sistem ERP terintegrasi multi-channel berbasis cloud, kasir membutuhkan
                                    koneksi internet aktif agar tidak terjadi penjualan barang online secara bersamaan yang
                                    bisa memicu overselling.
                                </div>
                            </div>
                            <div class="accordion-item">
                                <button class="accordion-trigger">Bagaimana kasir merekap omset per shift harian? <i
                                        class="fas fa-chevron-down"></i></button>
                                <div class="accordion-content">
                                    Kasir dapat membuka laporan rekap penjualan di menu Laporan Penjualan Offline, lalu
                                    memfilternya berdasarkan tanggal hari ini dan nama karyawan yang bertugas untuk melihat
                                    total omset tunai vs non-tunai.
                                </div>
                            </div>
                        </div>

                        <div class="feedback-widget">
                            <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                            <div class="feedback-btns">
                                <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                                <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang
                                    membantu</button>
                            </div>
                            <div class="feedback-response"></div>
                        </div>
                    </div>

                    <!-- ==================== HAK AKSES & KARYAWAN ==================== -->
                    <div class="content-card cat-akses" data-category="akses">
                        <div class="content-header">
                            <div class="content-header-icon"><i class="fas fa-user-shield"></i></div>
                            <div class="content-header-title">
                                <h3>Karyawan & Hak Akses (Multi-User)</h3>
                                <p>Mengelola peran kustom karyawan dan membatasi izin akses menu demi keamanan database ERP
                                </p>
                            </div>
                            <div class="content-header-meta">
                                <div>Estimasi baca: 3 mnt</div>
                                <div>Terakhir update: 15 Juni 2026</div>
                            </div>
                        </div>

                        <div class="section-title-wrap">
                            <i class="fas fa-project-diagram"></i>
                            <h4>Alur Kerja Manajemen Karyawan</h4>
                        </div>

                        <div class="workflow-timeline">
                            <div class="timeline-item">
                                <div class="timeline-number">1</div>
                                <div class="timeline-content">
                                    <h5>Buat Peran / Role Kustom</h5>
                                    <p>Masuk ke menu <strong>Hak Akses</strong>, lalu buat nama role baru sesuai kebutuhan
                                        operasional (misal: "Kasir Toko", "Staff Gudang Packing", "Akuntan Keuangan").</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-number">2</div>
                                <div class="timeline-content">
                                    <h5>Pemberian Izin Menu (Permissions)</h5>
                                    <p>Centang checkbox menu dan tombol aksi yang boleh diakses oleh role tersebut. Semisal,
                                        sembunyikan menu Keuangan dari staff gudang.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-number">3</div>
                                <div class="timeline-content">
                                    <h5>Tambahkan Data Karyawan & Tautkan Role <span class="flow-badge">Alur Selesai</span>
                                    </h5>
                                    <p>Pergi ke menu <strong>Karyawan -> Tambah Karyawan</strong>, isi identitas
                                        email/username dan kaitkan dengan Role yang telah Anda buat sebelumnya. Karyawan
                                        siap login dengan hak akses terbatas.</p>
                                </div>
                            </div>
                        </div>

                        <div class="section-title-wrap mt-4">
                            <i class="fas fa-question-circle"></i>
                            <h4>Pertanyaan Populer (FAQ)</h4>
                        </div>

                        <div class="faq-accordion">
                            <div class="accordion-item">
                                <button class="accordion-trigger">Apakah staff gudang bisa mengakses laporan keuntungan
                                    jika tidak diizinkan? <i class="fas fa-chevron-down"></i></button>
                                <div class="accordion-content">
                                    Tidak. Jika izin menu keuangan tidak dicentang untuk role staff gudang, seluruh menu
                                    Laporan Laba Rugi, Rekonsiliasi, dan Kartu Stok nominal keuangan akan secara otomatis
                                    disembunyikan sepenuhnya dari dasbor mereka ketika login.
                                </div>
                            </div>
                            <div class="accordion-item">
                                <button class="accordion-trigger">Bagaimana jika akun karyawan terkunci atau lupa password?
                                    <i class="fas fa-chevron-down"></i></button>
                                <div class="accordion-content">
                                    Sebagai Tenant Owner atau Admin Utama, Anda dapat mereset kata sandi karyawan secara
                                    manual melalui menu <strong>Master -> Pengguna -> Edit Pengguna -> Ganti
                                        Password</strong>.
                                </div>
                            </div>
                            <div class="accordion-item">
                                <button class="accordion-trigger">Apa perbedaan Tenant Owner dengan Role Admin? <i
                                        class="fas fa-chevron-down"></i></button>
                                <div class="accordion-content">
                                    Tenant Owner memiliki wewenang penuh atas kepemilikan langganan ERP, pembayaran paket,
                                    dan penghapusan database keseluruhan, sedangkan Role Admin hanya bertugas mengelola
                                    operasional sistem harian tanpa memiliki akses ke setelan billing/langganan.
                                </div>
                            </div>
                        </div>

                        <div class="feedback-widget">
                            <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                            <div class="feedback-btns">
                                <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                                <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang
                                    membantu</button>
                            </div>
                            <div class="feedback-response"></div>
                        </div>
                    </div>
                </div>

                <!-- ==================== LAPORAN & ANALITIK GUDANG ==================== -->
                <div class="content-card cat-laporan" data-category="laporan">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-chart-pie"></i></div>
                        <div class="content-header-title">
                            <h3>Laporan Gudang & Analitik Inventori</h3>
                            <p>Memantau perputaran stok barang, laporan opname, serta estimasi kapan stok akan habis</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Laporan & Analitik</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Akses Menu Analitik Inventori</h5>
                                <p>Buka halaman <strong>Analitik Inventori</strong> di bawah menu LAPORAN untuk memuat
                                    dasbor grafik laju stok Anda.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Evaluasi Estimasi Habis Stok (Stock-out Forecast)</h5>
                                <p>Sistem otomatis menghitung kecepatan penjualan rata-rata produk harian untuk memprediksi
                                    sisa hari sebelum stok fisik Anda habis total.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Ekspor Rekap Persediaan <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Masuk ke menu <strong>Rekap Persediaan</strong> atau <strong>Stok Barang</strong>, pilih
                                    filter rentang tanggal, lalu klik tombol <strong>Ekspor PDF / Excel</strong> untuk
                                    mencetak data mutasi gudang.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana sistem menghitung prediksi sisa hari stok
                                (stock-out)? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Sistem ERP Marketplace memantau laju penjualan rata-rata produk (*Sales Velocity*) selama 7,
                                30, dan 90 hari terakhir. Nilai rata-rata tersebut digunakan untuk membagi jumlah stok
                                master aktif saat ini guna menghasilkan estimasi jumlah hari yang tersisa sebelum barang
                                habis.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah laporan gudang dapat difilter berdasarkan channel toko
                                tertentu? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Ya. Di dalam modul laporan rekap persediaan, Anda dapat memilih filter "Channel Toko"
                                (seperti Shopee atau TikTok Shop) untuk melihat kontribusi penjualan masing-masing toko
                                terhadap pengurangan stok master gudang pusat Anda.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== VOUCHER & PROMOSI ==================== -->
                <div class="content-card cat-voucher" data-category="voucher">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-ticket-alt"></i></div>
                        <div class="content-header-title">
                            <h3>Promosi & Voucher Marketplace</h3>
                            <p>Mengelola kampanye diskon, coret harga, serta rekonsiliasi subsidi voucher belanja</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Voucher & Promosi</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Buka Menu Voucher & Promosi</h5>
                                <p>Pilih menu <strong>Voucher / Promosi</strong> di panel MASTER atau TRANSAKSI untuk
                                    membuka dashboard promosi gabungan.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Tarik Data Campaign Aktif</h5>
                                <p>Klik <strong>Tarik Promosi Terbaru</strong> untuk mengimpor promo coret harga atau kode
                                    voucher diskon yang sedang berjalan di Shopee dan TikTok Shop.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Mapping Subsidi Diskon <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Sistem secara otomatis membaca rincian order untuk memisahkan nominal diskon yang
                                    dipotong: apakah ditanggung penuh oleh seller atau disubsidi oleh pihak platform
                                    marketplace.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana sistem mencatat voucher subsidi diskon dari
                                marketplace? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Potongan belanja dari voucher subsidi (misal diskon potongan TikTok Shop) tidak akan
                                mengurangi omset bersih toko Anda. Nilai diskon tersebut akan dicatat oleh ERP sebagai
                                piutang platform dan ditambahkan kembali sebagai penerimaan ketika rekonsiliasi dana selesai
                                ditarik ke bank.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah kita bisa membuat promosi coret harga langsung dari
                                dasbor ERP? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Untuk menjaga kestabilan promosi dan kepatuhan terhadap API, pembuatan diskon coret harga
                                disarankan tetap dikonfigurasi melalui seller center masing-masing marketplace. Sistem ERP
                                akan secara otomatis menarik data promosi tersebut untuk sinkronisasi pesanan.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== SETELAN & KURIR ==================== -->
                <div class="content-card cat-pengaturan" data-category="pengaturan">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-cog"></i></div>
                        <div class="content-header-title">
                            <h3>Pengaturan Tenant, Profil, & Logistik Ekspedisi</h3>
                            <p>Konfigurasi profil usaha, alamat gudang fisik utama, ekspedisi kurir, dan printer thermal</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Konfigurasi Awal</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Buka Menu Pengaturan Sistem</h5>
                                <p>Klik menu <strong>Pengaturan</strong> di dashboard untuk membuka setelan profil tenant
                                    (alamat perusahaan, info kontak, logo, dll).</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Aktifkan Ekspedisi & Kurir (Shipments)</h5>
                                <p>Buka sub-menu <strong>Logistik / Ekspedisi</strong> untuk mencentang jenis ekspedisi yang
                                    didukung oleh gudang Anda (J&T, JNE, SiCepat, Shopee Express, GoSend, dll).</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Konfigurasi Printer Thermal & Webhook <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Setel format kertas struk belanja / label resi (seperti format kertas A6 thermal) dan
                                    pasang webhook URL notifikasi logistik untuk update resi otomatis.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana cara mengubah profil alamat gudang fisik utama? <i
                                    class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Masuk ke menu <strong>Pengaturan -> Alamat Gudang</strong>, klik Edit, isi titik koordinat
                                serta alamat lengkap. Alamat gudang ini digunakan sebagai acuan titik pick-up kurir logistik
                                dan perhitungan tarif ongkir pada POS Kasir Offline.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Mengapa ekspedisi pengiriman tertentu tidak muncul saat
                                proses booking kurir? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Pastikan ekspedisi tersebut telah diaktifkan di setelan pengiriman pada toko seller center
                                marketplace resmi Anda (misal seller center Shopee). Setelah itu, lakukan refresh logistik
                                di ERP untuk memuat ulang daftar opsi kurir yang valid.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>

                <!-- ==================== HAK AKSES & KARYAWAN ==================== -->
                <div class="content-card cat-akses" data-category="akses">
                    <div class="content-header">
                        <div class="content-header-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="content-header-title">
                            <h3>Karyawan & Hak Akses (Multi-User)</h3>
                            <p>Mengelola peran kustom karyawan dan membatasi izin akses menu demi keamanan database ERP</p>
                        </div>
                        <div class="content-header-meta">
                            <div>Estimasi baca: 3 mnt</div>
                            <div>Terakhir update: 15 Juni 2026</div>
                        </div>
                    </div>

                    <div class="section-title-wrap">
                        <i class="fas fa-project-diagram"></i>
                        <h4>Alur Kerja Manajemen Karyawan</h4>
                    </div>

                    <div class="workflow-timeline">
                        <div class="timeline-item">
                            <div class="timeline-number">1</div>
                            <div class="timeline-content">
                                <h5>Buat Peran / Role Kustom</h5>
                                <p>Masuk ke menu <strong>Hak Akses</strong>, lalu buat nama role baru sesuai kebutuhan operasional (misal: "Kasir Toko", "Staff Gudang Packing", "Akuntan Keuangan").</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">2</div>
                            <div class="timeline-content">
                                <h5>Pemberian Izin Menu (Permissions)</h5>
                                <p>Centang checkbox menu dan tombol aksi yang boleh diakses oleh role tersebut. Semisal, sembunyikan menu Keuangan dari staff gudang.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-number">3</div>
                            <div class="timeline-content">
                                <h5>Tambahkan Data Karyawan & Tautkan Role <span class="flow-badge">Alur Selesai</span></h5>
                                <p>Pergi ke menu <strong>Karyawan -> Tambah Karyawan</strong>, isi identitas email/username dan kaitkan dengan Role yang telah Anda buat sebelumnya. Karyawan siap login dengan hak akses terbatas.</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title-wrap mt-4">
                        <i class="fas fa-question-circle"></i>
                        <h4>Pertanyaan Populer (FAQ)</h4>
                    </div>

                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apakah staff gudang bisa mengakses laporan keuntungan jika tidak diizinkan? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Tidak. Jika izin menu keuangan tidak dicentang untuk role staff gudang, seluruh menu Laporan Laba Rugi, Rekonsiliasi, dan Kartu Stok nominal keuangan akan secara otomatis disembunyikan sepenuhnya dari dasbor mereka ketika login.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Bagaimana jika akun karyawan terkunci atau lupa password? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Sebagai Tenant Owner atau Admin Utama, Anda dapat mereset kata sandi karyawan secara manual melalui menu <strong>Master -> Pengguna -> Edit Pengguna -> Ganti Password</strong>.
                            </div>
                        </div>
                        <div class="accordion-item">
                            <button class="accordion-trigger">Apa perbedaan Tenant Owner dengan Role Admin? <i class="fas fa-chevron-down"></i></button>
                            <div class="accordion-content">
                                Tenant Owner memiliki wewenang penuh atas kepemilikan langganan ERP, pembayaran paket, dan penghapusan database keseluruhan, sedangkan Role Admin hanya bertugas mengelola operasional sistem harian tanpa memiliki akses ke setelan billing/langganan.
                            </div>
                        </div>
                    </div>

                    <div class="feedback-widget">
                        <div class="feedback-question">Apakah panduan kategori ini membantu Anda?</div>
                        <div class="feedback-btns">
                            <button class="feedback-btn yes"><i class="far fa-thumbs-up"></i> Ya, membantu</button>
                            <button class="feedback-btn no"><i class="far fa-thumbs-down"></i> Kurang membantu</button>
                        </div>
                        <div class="feedback-response"></div>
                    </div>
                </div>
            </div>

            <!-- Extra Help Desk Cards -->
            <div class="extra-help-section">
                <div class="help-card">
                    <div class="help-card-icon whatsapp"><i class="fab fa-whatsapp"></i></div>
                    <div class="help-card-info">
                        <h5>WhatsApp Customer Service</h5>
                        <p>Butuh bantuan teknis cepat? Hubungi tim support kami via obrolan WhatsApp untuk respon instan
                            harian.</p>
                        <a href="https://wa.me/6281234567890?text=Halo%20ERP%20Marketplace%20Support%2C%20saya%20butuh%20bantuan%20mengenai..."
                            target="_blank" class="help-card-btn">Hubungi WA Support</a>
                    </div>
                </div>

                <div class="help-card">
                    <div class="help-card-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="help-card-info">
                        <h5>Buat Tiket Bantuan</h5>
                        <p>Kirimkan kendala server, bug sistem, atau permintaan custom fitur melalui sistem tiket bantuan
                            kami.</p>
                        <a href="#" class="help-card-btn">Kirim Tiket Masalah</a>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const searchInput = document.getElementById('faq-search');
                    const categoryCards = document.querySelectorAll('.category-nav-card');
                    const contentCards = document.querySelectorAll('.content-card');
                    const popularTags = document.querySelectorAll('.tag-pill');
                    const searchEmptyState = document.getElementById('search-empty');

                    // 1. Initial State: Load active category from localStorage or default to 'integrasi'
                    let savedCategory = localStorage.getItem('active_faq_category');
                    if (!savedCategory || !document.querySelector(`.category-nav-card[data-category="${savedCategory}"]`)) {
                        savedCategory = 'integrasi';
                    }
                    switchCategory(savedCategory);

                    // 2. Click category handler
                    categoryCards.forEach(card => {
                        card.addEventListener('click', function() {
                            const category = this.getAttribute('data-category');
                            switchCategory(category);

                            // Clear search field on manual category change to prevent confusion
                            if (searchInput.value !== '') {
                                searchInput.value = '';
                                performSearch('');
                            }
                        });
                    });

                    // Function to switch active category
                    function switchCategory(categoryName) {
                        categoryCards.forEach(card => {
                            if (card.getAttribute('data-category') === categoryName) {
                                card.classList.add('active');
                            } else {
                                card.classList.remove('active');
                            }
                        });

                        contentCards.forEach(content => {
                            if (content.getAttribute('data-category') === categoryName) {
                                content.classList.add('show-active');
                            } else {
                                content.classList.remove('show-active');
                            }
                        });

                        localStorage.setItem('active_faq_category', categoryName);
                    }

                    // 3. Custom Accordion Toggle Logic
                    const accordionTriggers = document.querySelectorAll('.accordion-trigger');
                    accordionTriggers.forEach(trigger => {
                        trigger.addEventListener('click', function() {
                            const item = this.parentElement;
                            const content = this.nextElementSibling;
                            const isActive = item.classList.contains('active');

                            if (isActive) {
                                // If currently set to 'none' (finished expanding), restore scrollHeight to animate collapse
                                if (content.style.maxHeight === 'none') {
                                    content.style.maxHeight = content.scrollHeight + "px";
                                    void content.offsetHeight; // force reflow
                                }
                                item.classList.remove('active');
                                content.style.maxHeight = null;
                            } else {
                                // Close other active items within the same card first (optional, for accordion effect)
                                const parentCard = this.closest('.content-card');
                                parentCard.querySelectorAll('.accordion-item.active').forEach(
                                    activeItem => {
                                        const activeContent = activeItem.querySelector(
                                            '.accordion-content');
                                        if (activeContent.style.maxHeight === 'none') {
                                            activeContent.style.maxHeight = activeContent.scrollHeight +
                                                "px";
                                            void activeContent.offsetHeight;
                                        }
                                        activeItem.classList.remove('active');
                                        activeContent.style.maxHeight = null;
                                    });

                                item.classList.add('active');
                                content.style.maxHeight = content.scrollHeight + "px";

                                // Reset max-height to 'none' after transition ends so content resizing or wraps don't get clipped
                                const onTransitionEnd = function(e) {
                                    if (e.propertyName === 'max-height') {
                                        if (item.classList.contains('active')) {
                                            content.style.maxHeight = 'none';
                                        }
                                        content.removeEventListener('transitionend', onTransitionEnd);
                                    }
                                };
                                content.addEventListener('transitionend', onTransitionEnd);
                            }
                        });
                    });

                    // 4. Text highlighting logic
                    function removeHighlights(container) {
                        container.querySelectorAll('mark.search-highlight').forEach(mark => {
                            const parent = mark.parentNode;
                            const textNode = document.createTextNode(mark.textContent);
                            parent.replaceChild(textNode, mark);
                            parent.normalize();
                        });
                    }

                    function addHighlights(element, query) {
                        if (!query) return;
                        const cleanQuery = query.trim().replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                        const regex = new RegExp(`(${cleanQuery})`, 'gi');

                        // Recursive search for text nodes
                        const textNodes = [];
                        const walk = (node) => {
                            if (node.nodeType === 3) { // Text Node
                                if (node.nodeValue.match(regex)) {
                                    textNodes.push(node);
                                }
                            } else if (node.nodeType === 1 && node.childNodes && !['SCRIPT', 'STYLE', 'MARK', 'BUTTON',
                                    'I'
                                ].includes(node.nodeName)) {
                                // Only search inside structural tags, avoid icons or buttons content directly
                                Array.from(node.childNodes).forEach(walk);
                            }
                        };

                        walk(element);

                        textNodes.forEach(node => {
                            const text = node.nodeValue;
                            const matches = text.match(regex);
                            if (matches && node.parentNode) {
                                const parent = node.parentNode;
                                const wrapper = document.createElement('span');
                                wrapper.innerHTML = text.replace(regex, '<mark class="search-highlight">$1</mark>');

                                // Replace text node with highlighted nodes
                                while (wrapper.firstChild) {
                                    parent.insertBefore(wrapper.firstChild, node);
                                }
                                parent.removeChild(node);
                            }
                        });
                    }

                    // 5. Core Search Logic
                    searchInput.addEventListener('input', function() {
                        const query = this.value.toLowerCase().trim();
                        performSearch(query);
                    });

                    // Popular keywords pills handler
                    popularTags.forEach(tag => {
                        tag.addEventListener('click', function() {
                            const query = this.getAttribute('data-query');
                            searchInput.value = query;
                            searchInput.focus();
                            performSearch(query.toLowerCase());
                        });
                    });

                    function performSearch(query) {
                        // Reset all highlights first
                        contentCards.forEach(card => removeHighlights(card));

                        if (query.length < 2) {
                            // Restore normal view
                            searchEmptyState.style.display = 'none';

                            // Restore default category display
                            categoryCards.forEach(c => c.style.display = 'flex');

                            // Restore default badge numbers
                            document.getElementById('badge-integrasi').innerText = '6';
                            document.getElementById('badge-produk').innerText = '6';
                            document.getElementById('badge-transaksi').innerText = '7';
                            document.getElementById('badge-stok').innerText = '6';
                            document.getElementById('badge-keuangan').innerText = '7';
                            document.getElementById('badge-chat').innerText = '6';
                            document.getElementById('badge-pos').innerText = '7';
                            document.getElementById('badge-akses').innerText = '7';
                            document.getElementById('badge-laporan').innerText = '5';
                            document.getElementById('badge-voucher').innerText = '5';
                            document.getElementById('badge-pengaturan').innerText = '5';

                            // Collapse all accordions
                            document.querySelectorAll('.accordion-item').forEach(item => {
                                item.classList.remove('active');
                                item.querySelector('.accordion-content').style.maxHeight = null;
                            });

                            // Show current saved category
                            const currentCat = localStorage.getItem('active_faq_category') || 'integrasi';
                            switchCategory(currentCat);
                            return;
                        }

                        let totalMatchesFound = 0;
                        let firstMatchingCat = null;
                        const catMatches = {};

                        // Search inside each category content card
                        contentCards.forEach(card => {
                            const category = card.getAttribute('data-category');
                            let catMatchCount = 0;

                            // Check timeline workflow steps
                            const timelineItems = card.querySelectorAll('.timeline-content');
                            timelineItems.forEach(item => {
                                const text = item.textContent.toLowerCase();
                                if (text.includes(query)) {
                                    catMatchCount++;
                                    addHighlights(item, query);
                                }
                            });

                            // Check accordion FAQs
                            const accordionItems = card.querySelectorAll('.accordion-item');
                            accordionItems.forEach(item => {
                                const headerText = item.querySelector('.accordion-trigger').textContent
                                    .toLowerCase();
                                const contentText = item.querySelector('.accordion-content').textContent
                                    .toLowerCase();

                                const isMatchHeader = headerText.includes(query);
                                const isMatchContent = contentText.includes(query);

                                if (isMatchHeader || isMatchContent) {
                                    catMatchCount++;

                                    // Auto-expand matched FAQ item
                                    item.classList.add('active');
                                    const accordionContent = item.querySelector('.accordion-content');
                                    accordionContent.style.maxHeight = 'none';

                                    // Highlight matches
                                    addHighlights(item.querySelector('.accordion-trigger'), query);
                                    addHighlights(accordionContent, query);
                                } else {
                                    // Collapse unmatched FAQ item
                                    item.classList.remove('active');
                                    item.querySelector('.accordion-content').style.maxHeight = null;
                                }
                            });

                            catMatches[category] = catMatchCount;
                            totalMatchesFound += catMatchCount;

                            if (catMatchCount > 0 && !firstMatchingCat) {
                                firstMatchingCat = category;
                            }
                        });

                        // Update sidebar state based on matches
                        categoryCards.forEach(card => {
                            const category = card.getAttribute('data-category');
                            const badge = card.querySelector('.cat-badge');
                            const matches = catMatches[category] || 0;

                            if (matches > 0) {
                                card.style.display = 'flex';
                                badge.innerText = matches;
                                badge.style.background = 'var(--warning-glow)';
                                badge.style.color = 'var(--warning)';
                                badge.style.borderColor = 'rgba(245, 158, 11, 0.3)';
                            } else {
                                // Hide or dim unmatched category cards
                                card.style.display = 'none';
                            }
                        });

                        if (totalMatchesFound > 0) {
                            searchEmptyState.style.display = 'none';

                            // Auto-switch to the first matching category if the current one has no matches
                            const currentCat = document.querySelector('.category-nav-card.active').getAttribute(
                                'data-category');
                            if ((catMatches[currentCat] || 0) === 0) {
                                switchCategory(firstMatchingCat);
                            } else {
                                // Refresh layout of active card to make sure expanded items occupy space correctly
                                const activeContentCard = document.querySelector('.content-card.show-active');
                                if (activeContentCard) {
                                    activeContentCard.classList.remove('show-active');
                                    // Force redraw browser trick
                                    void activeContentCard.offsetWidth;
                                    activeContentCard.classList.add('show-active');
                                }
                            }
                        } else {
                            // Display not found screen
                            searchEmptyState.style.display = 'block';
                            contentCards.forEach(c => c.classList.remove('show-active'));
                            categoryCards.forEach(c => c.style.display = 'none');
                        }
                    }

                    // 6. Interactive Feedback Widgets
                    const feedbackContainers = document.querySelectorAll('.feedback-widget');
                    feedbackContainers.forEach(container => {
                        const btnsContainer = container.querySelector('.feedback-btns');
                        const responseText = container.querySelector('.feedback-response');
                        const yesBtn = container.querySelector('.feedback-btn.yes');
                        const noBtn = container.querySelector('.feedback-btn.no');

                        yesBtn.addEventListener('click', function() {
                            btnsContainer.style.display = 'none';
                            responseText.innerText =
                                'Senang bisa membantu! Terima kasih atas masukan Anda. ❤️';
                            responseText.style.display = 'block';
                        });

                        noBtn.addEventListener('click', function() {
                            btnsContainer.style.display = 'none';
                            responseText.innerText =
                                'Terima kasih atas masukannya. Masukan Anda direkam untuk membantu meningkatkan halaman panduan ini. 🙏';
                            responseText.style.color = 'var(--text-secondary)';
                            responseText.style.display = 'block';
                        });
                    });
                });
            </script>
        @endpush
    @endsection
