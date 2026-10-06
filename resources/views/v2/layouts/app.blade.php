<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ASPARTECH ERP Dashboard V2 - High Density Compact Enterprise Interface">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal ERP V2') | ASPARTECH ERP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Select2 Searchable Dropdown CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <!-- Design System V2 CSS -->
    <link rel="stylesheet" href="{{ asset('css/app_v2.css') }}?v={{ file_exists(public_path('css/app_v2.css')) ? filemtime(public_path('css/app_v2.css')) : time() }}">

    @stack('styles')

    <!-- JS Core Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 Searchable Dropdown JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body class="v2-layout">

    <div class="v2-wrapper">
        <!-- Include V2 Sidebar -->
        @include('v2.layouts.sidebar')

        <!-- Main Content Wrapper -->
        <main class="v2-main-content">
            <!-- Top Header Navbar Compact -->
            <header class="v2-header">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary py-1 px-2 border-0" type="button"
                        onclick="toggleV2Sidebar()">
                        <i class="bi bi-list fs-5"></i>
                    </button>

                    <!-- Breadcrumb / System Nav Tag -->
                    <div class="d-none d-md-flex align-items-center gap-2" style="font-size: 0.78rem;">
                        <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                            <i class="bi bi-grid me-1"></i>ERP V2
                        </span>
                        <span class="text-muted">/</span>
                        <span class="fw-semibold text-dark">Marketplace Hub</span>
                    </div>
                </div>

                <!-- Global Search Box -->
                <div class="v2-header-search d-none d-lg-block">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Cari order, produk, toko... (Ctrl+K)">
                </div>

                <!-- Header Right Action Tools -->
                <div class="v2-header-actions">
                    <!-- Realtime Clock -->
                    <div class="text-muted d-none d-xl-flex align-items-center gap-1 fw-medium px-2"
                        style="font-size: 0.75rem;">
                        <i class="bi bi-clock"></i>
                        <span id="realtimeClock">{{ \Carbon\Carbon::now()->translatedFormat('d M Y, H:i:s') }}</span>
                    </div>

                    <!-- Sync Store Quick Button -->
                    <a href="{{ Route::has('stock_sync.index') ? route('stock_sync.index') : url('/v2/produk') }}"
                        class="btn btn-sm btn-v2-secondary py-1 px-2.5 d-flex align-items-center gap-1"
                        style="font-size: 0.75rem;">
                        <i class="bi bi-arrow-repeat text-primary"></i> Sync Toko
                    </a>

                    <!-- Notification Dropdown -->
                    <div class="dropdown">
                        <button class="v2-icon-btn position-relative" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false" title="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <span
                                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-3"
                            style="width: 280px; border-radius: 8px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold m-0" style="font-size: 0.8rem;">Notifikasi Sistem</h6>
                                <span class="badge bg-primary-subtle text-primary"
                                    style="font-size: 0.68rem;">Live</span>
                            </div>
                            <hr class="my-1">
                            <div class="py-2 text-center text-muted" style="font-size: 0.78rem;">
                                <i class="bi bi-check-circle-fill text-success fs-5 d-block mb-1"></i>
                                Semua sistem terintegrasi normal.
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm p-0 d-flex align-items-center gap-2 border-0" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="v2-user-avatar" style="width: 32px; height: 32px; font-size: 0.78rem;">
                                {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                            </div>
                            <div class="d-none d-md-block text-start">
                                <div class="fw-bold text-dark text-truncate"
                                    style="max-width: 120px; font-size: 0.78rem; line-height: 1.1;">
                                    {{ Auth::user()->name ?? 'User' }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    {{ Auth::user()->tenant->name ?? 'Ruang Seragam' }}</div>
                            </div>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 0.7rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                            style="font-size: 0.78rem; border-radius: 8px;">
                            <li><a class="dropdown-item py-1.5"
                                    href="{{ route('v2.users.index') }}"><i
                                        class="bi bi-people me-2"></i>Pengguna Sistem</a></li>
                            <li><a class="dropdown-item py-1.5" href="{{ url('/v2/produk') }}"><i
                                        class="bi bi-box-seam me-2"></i>Katalog Produk V2</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}"
                                    method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger py-1.5">
                                        <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Body Page Content -->
            <div class="v2-body-content">
                @yield('content')
            </div>

            <!-- Compact Footer -->
            <footer class="v2-footer">
                <div>
                    <strong>ASPARTECH ERP</strong> &copy; {{ date('Y') }} — Portal Management V2 System
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Engine
                        Active</span>
                    <span>v2.4.0</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        function toggleV2Sidebar() {
            $('#v2Sidebar').toggleClass('collapsed');
        }

        // Live Clock Counter
        setInterval(function() {
            const now = new Date();
            const timeStr = now.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }) + ', ' + now.toLocaleTimeString('id-ID');
            $('#realtimeClock').text(timeStr);
        }, 1000);

        // Global Select2 Initializer with tight wrapper for 100% position & width accuracy
        window.initV2Select2 = function(context) {
            var $target = context ? 
                $(context).find('select.form-select, select.form-select-sm, select.select2, select.v2-select2') : 
                $('select.form-select, select.form-select-sm, select.select2, select.v2-select2');

            $target.not('.no-select2').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    return; // Already initialized
                }
                if (!$select.parent().hasClass('select2-wrapper')) {
                    $select.wrap('<div class="select2-wrapper position-relative d-block w-100" style="max-width: 100%;"></div>');
                }
                var $wrapper = $select.parent();

                $select.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $select.data('placeholder') || '-- Pilih --',
                    allowClear: true,
                    dropdownParent: $wrapper
                });
            });
        };

        $(document).ready(function() {
            window.initV2Select2();
        });
    </script>

    <!-- Global SweetAlert2 Confirm Handler -->
    <script>
    // Global SweetAlert confirm untuk semua form dengan data-swal
    $(document).on('submit', 'form[data-swal-title]', function(e) {
        e.preventDefault();
        const $form = $(this);
        const title   = $form.data('swal-title')   || 'Konfirmasi';
        const text    = $form.data('swal-text')    || 'Apakah Anda yakin?';
        const icon    = $form.data('swal-icon')    || 'question';
        const btnText = $form.data('swal-btn')     || 'Ya, Lanjutkan';
        const btnColor = $form.data('swal-color')  || '#3b82f6';

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: btnText,
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'swal2-compact',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $form[0].submit();
            }
        });
    });

    // SweetAlert notifikasi session flash otomatis
    @if(session('success'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: @json(session('success')),
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
        });
    @endif
    @if(session('error'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: @json(session('error')),
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
        });
    @endif
    @if(session('warning'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: @json(session('warning')),
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
        });
    @endif
    <!-- Global Automatic Thousands Separator (Numeric Dot Format) Handler -->
    <script>
    (function() {
        window.formatNumberWithDots = function(val) {
            if (val === null || val === undefined) return '';
            var str = val.toString().replace(/\D/g, '');
            return str.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        };

        window.cleanNumberFromDots = function(val) {
            if (!val) return 0;
            return parseFloat(val.toString().replace(/\./g, '')) || 0;
        };

        // Live formatting listener for inputs with class .numeric-dot-format or data-format="number"
        document.addEventListener('input', function(e) {
            if (e.target && (e.target.classList.contains('numeric-dot-format') || e.target.getAttribute('data-format') === 'number')) {
                var cursorPosition = e.target.selectionStart;
                var oldLength = e.target.value.length;

                var formatted = window.formatNumberWithDots(e.target.value);
                e.target.value = formatted;

                var newLength = formatted.length;
                var newPosition = cursorPosition + (newLength - oldLength);
                if (newPosition < 0) newPosition = 0;
                e.target.setSelectionRange(newPosition, newPosition);
            }
        });

        // Initialize formatting on DOM ready for pre-filled values
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.numeric-dot-format, input[data-format="number"]').forEach(function(input) {
                if (input.value) {
                    input.value = window.formatNumberWithDots(input.value);
                }
            });
        });
    })();
    </script>

    @stack('scripts')
</body>

</html>
