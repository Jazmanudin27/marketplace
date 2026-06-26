<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="ASPARTECH ERP Dashboard - Kelola semua toko marketplace Anda dalam satu dashboard terpusat">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | ERP Marketplace</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
    <!-- JS Core -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-body-secondary">

    <!-- Sidebar Navigation for Offcanvas Mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel"
        style="width: 260px;">
        <div class="offcanvas-body p-0">
            @include('layouts.sidebar')
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Main Content Area -->
            <main class="col-12 p-0 d-flex flex-column min-vh-100">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2 px-3 shadow-sm">
                    <div class="container-fluid p-0">
                        <button class="btn btn-sm btn-outline-secondary me-2" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                            <i class="bi bi-list fs-5"></i>
                        </button>
                        <div class="ms-1">
                            <h6 class="mb-0 fw-bold fs-6 text-dark">
                                @yield('page-title', 'Dashboard')
                            </h6>
                        </div>

                        <div class="ms-auto d-flex align-items-center gap-3">
                            @yield('topbar-actions')
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                    style="width: 32px; height: 32px; font-size: 0.85rem;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="lh-sm d-none d-sm-block">
                                    <span class="text-dark fw-semibold d-block"
                                        style="font-size: 0.8rem;">{{ Auth::user()->name }}</span>
                                    <span class="text-muted"
                                        style="font-size: 0.7rem;">{{ ucfirst(Auth::user()->role) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page content wrapper -->
                <div class="container-fluid p-4">

                    <!-- System Session Alerts -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-start border-4 border-success d-flex align-items-center gap-2 p-3 mb-4"
                            role="alert">
                            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                            <div>
                                {{ session('success') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger d-flex align-items-center gap-2 p-3 mb-4"
                            role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                            <div>
                                {{ session('error') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show border-start border-4 border-danger p-3 mb-4"
                            role="alert">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                                <strong class="text-danger">Terjadi kesalahan!</strong>
                            </div>
                            <ul class="mb-0 ps-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Main Content Slot -->
                    @yield('content')

                </div>
            </main>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // Initialize Select2
            if ($.fn.select2) {
                setTimeout(function() {
                    $('.form-select').each(function() {
                        const select = $(this);
                        if (!select.hasClass('select2-hidden-accessible')) {
                            const modal = select.closest('.modal');
                            const options = {
                                theme: 'bootstrap-5',
                                width: '100%',
                                placeholder: select.data('placeholder') || 'Pilih opsi...'
                            };
                            
                            if (modal.length) {
                                options.dropdownParent = modal;
                            }
                            
                            select.select2(options);
                        }
                    });
                }, 50);
            }

            // SweetAlert2 Session Messages
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: "{{ session('success') }}",
                    timer: 3000,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'border border-light-subtle shadow-sm'
                    }
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: "{{ session('error') }}",
                    customClass: {
                        popup: 'border border-light-subtle shadow-sm'
                    }
                });
            @endif

            // Automatically convert all inline confirm forms to SweetAlert2
            $('form[onsubmit*="confirm("]').each(function() {
                const form = $(this);
                const onsubmitAttr = form.attr('onsubmit');

                // Extract message from confirm('message') or confirm("message")
                const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
                if (match && match[1]) {
                    const message = match[1];

                    // Remove inline onsubmit so it doesn't trigger native confirm
                    form.removeAttr('onsubmit');

                    // Attach jQuery submit handler with Swal
                    form.on('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: 'Konfirmasi Tindakan',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#4b5563',
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal',
                            customClass: {
                                popup: 'border border-light-subtle shadow-sm'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Submit form programmatically bypassing jQuery handlers
                                form[0].submit();
                            }
                        });
                    });
                }
            });

            // Automatically convert all inline onclick confirm elements to SweetAlert2
            $('[onclick*="confirm("]').each(function() {
                const element = $(this);
                const onclickAttr = element.attr('onclick');
                const match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
                if (match && match[1]) {
                    const message = match[1];
                    element.removeAttr('onclick');
                    element.on('click', function(e) {
                        e.preventDefault();
                        const href = element.attr('href');

                        Swal.fire({
                            title: 'Konfirmasi Tindakan',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#4b5563',
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal',
                            customClass: {
                                popup: 'border border-light-subtle shadow-sm'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (href && href !== '#' && !href.startsWith(
                                        'javascript:')) {
                                    window.location.href = href;
                                } else {
                                    // Trigger form submit if it's a submit button
                                    const form = element.closest('form');
                                    if (form.length) {
                                        form[0].submit();
                                    }
                                }
                            }
                        });
                    });
                }
            });
        });
    </script>
    @stack('scripts')

</body>

</html>
