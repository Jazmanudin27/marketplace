<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ASPARTECH ERP Dashboard V2 - Modern ERP Marketplace Interface">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard V2') | ASPARTECH ERP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Design System V2 CSS -->
    <link rel="stylesheet" href="{{ asset('css/app_v2.css') }}">

    @stack('styles')

    <!-- JS Core Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const savedTheme = localStorage.getItem('v2_theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
</head>

<body class="v2-layout">

    <div class="v2-wrapper">
        <!-- Include V2 Sidebar -->
        @include('v2.layouts.sidebar')

        <!-- Main Content Wrapper -->
        <main class="v2-main-content">
            {{-- Impersonation / Simulation Mode Banner --}}
            @if (session()->has('impersonator_id'))
                @php
                    $impersonatorUser = \App\Models\User::find(session('impersonator_id'));
                    $currentRole = Auth::user()?->roles->first()?->name ?? Auth::user()?->role ?? 'User';
                @endphp
                <div class="bg-warning text-dark px-3 py-2 border-bottom d-flex justify-content-between align-items-center position-sticky top-0 w-100" style="z-index: 9999; background: #fef08a !important;">
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="badge bg-dark text-warning fw-bold px-2 py-1 shadow-sm">
                            <i class="fas fa-user-secret me-1"></i>MODE SIMULASI USER
                        </span>
                        <span class="fw-bold">
                            Anda sedang simulasi sebagai: <u>{{ Auth::user()?->name }}</u> (<span class="text-uppercase">{{ $currentRole }}</span>)
                        </span>
                    </div>
                    <form action="{{ route('impersonate.leave') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-pill px-3 py-1 shadow-sm">
                            <i class="fas fa-undo me-1 text-warning"></i> Kembali ke Akun Admin
                        </button>
                    </form>
                </div>
            @endif

            <!-- Top Header Navbar V2 -->
            <header class="v2-header">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" onclick="toggleV2Sidebar()">
                        <i class="bi bi-list fs-5"></i>
                    </button>

                    <!-- Global Quick Search -->
                    <div class="v2-header-search d-none d-md-block">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Cari produk, order ID, pelanggan..." id="v2GlobalSearch">
                    </div>
                </div>

                <!-- Header Right Action Tools -->
                <div class="v2-header-actions">
                    <!-- Switch Theme (Dark / Light) -->
                    <button type="button" class="v2-icon-btn" onclick="toggleV2Theme()" title="Beralih Mode Gelap/Terang">
                        <i class="bi bi-moon-stars" id="themeIcon"></i>
                    </button>

                    <!-- Notification Button -->
                    <div class="dropdown">
                        <button class="v2-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <span class="badge-dot"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3" style="width: 320px; border-radius: 14px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold m-0" style="font-size: 0.9rem;">Notifikasi Baru</h6>
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 0.75rem;">Sistem Online</span>
                            </div>
                            <hr class="my-2">
                            <div class="py-2 text-center text-muted" style="font-size: 0.825rem;">
                                <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                                Semua toko sinkron dengan lancar.
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    @if(Auth::user())
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="v2-user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="d-none d-md-block text-start" style="line-height: 1.2;">
                                <div class="fw-bold text-body" style="font-size: 0.825rem;">{{ Auth::user()->name }}</div>
                                <div class="text-muted" style="font-size: 0.725rem;">{{ ucfirst(Auth::user()->roles->first()?->name ?? Auth::user()->role) }}</div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" style="border-radius: 12px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ Auth::user()->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ Auth::user()->email }}</div>
                            </li>
                            @if(Route::has('users.edit'))
                            <li><a class="dropdown-menu-item dropdown-item my-1 text-secondary" href="{{ route('users.edit', Auth::id()) }}"><i class="bi bi-person me-2"></i> Pengaturan Profil</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger fw-semibold">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar / Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    @endif
                </div>
            </header>

            <!-- Main Page Content Slot -->
            <div class="v2-body-content">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Page Footer V2 -->
            <footer class="v2-footer">
                <div>
                    <strong>ASPARTECH ERP Marketplace V2</strong> &copy; {{ date('Y') }} — Hak Cipta Dilindungi.
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success-subtle text-success">Systems Normal</span>
                    <span>v2.0.0</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Layout Scripts -->
    <script>
        function toggleV2Sidebar() {
            const sidebar = document.getElementById('v2Sidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }

        function toggleV2Theme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('v2_theme', newTheme);
            
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = currentTheme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars';
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
