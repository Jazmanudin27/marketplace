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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .img-thumbnail-clickable {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .img-thumbnail-clickable:hover {
            transform: scale(1.1);
            filter: brightness(0.9);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-body-secondary">

    <!-- Sidebar Navigation for Offcanvas Mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel"
        style="width: 310px;">
        <div class="offcanvas-body p-0">
            @include('layouts.sidebar')
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Main Content Area -->
            <main class="col-12 p-0 d-flex flex-column min-vh-100">
                <!-- Navbar -->
                <nav id="main-navbar" class="navbar navbar-expand-lg navbar-dark bg-primary border-bottom sticky-top py-2 px-3 shadow-sm">
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

                    <!-- Main Content Slot -->
                    @yield('content')

                    <!-- Global Application Footer -->
                    <footer class="mt-5 py-3 text-center text-muted border-top border-light-subtle">
                        <small class="small">ERP Marketplace &copy; {{ date('Y') }} — Dikelola oleh <strong>Jazmanudin</strong></small>
                    </footer>

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

            // Toast Notification (Popup 5 Detik & Tidak Mendorong Layout Card)
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            @if (session('success'))
                Toast.fire({
                    icon: 'success',
                    title: "{{ session('success') }}"
                });
            @endif

            @if (session('error'))
                Toast.fire({
                    icon: 'error',
                    title: "{{ session('error') }}"
                });
            @endif

            @if ($errors->any())
                Toast.fire({
                    icon: 'error',
                    title: "{{ $errors->first() }}"
                });
            @endif

            // Auto dismiss static alert banners after 5 seconds
            setTimeout(function() {
                $('.alert-dismissible').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);

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
            // Handler for placeholder links
            $(document).on('click', '.placeholder-link', function(e) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'Fitur Segera Hadir',
                    text: 'Fitur ini sedang dalam pengembangan.',
                    confirmButtonColor: '#006cff',
                    confirmButtonText: 'Oke',
                    customClass: {
                        popup: 'border border-light-subtle shadow-sm'
                    }
                });
            // Global Image Preview Modal Trigger
            $(document).on('click', '.img-thumbnail-clickable', function() {
                const imgUrl = $(this).attr('data-image-src') || $(this).attr('src');
                const productName = $(this).attr('data-product-name') || 'Foto Produk';
                $('#globalImagePreviewSrc').attr('src', imgUrl);
                if (productName) {
                    $('#globalImagePreviewModalLabel').text(productName);
                }
                
                const previewModal = new bootstrap.Modal(document.getElementById('globalImagePreviewModal'));
                previewModal.show();
            });
        });
    </script>

    <!-- Global Image Preview Modal -->
    <div class="modal fade" id="globalImagePreviewModal" tabindex="-1" aria-labelledby="globalImagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h6 class="modal-title fw-bold" id="globalImagePreviewModalLabel">Pratinjau Foto Produk</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center bg-light d-flex align-items-center justify-content-center" style="min-height: 250px;">
                    <img id="globalImagePreviewSrc" src="" alt="Pratinjau Foto" class="img-fluid" style="max-height: 75vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- GLOBAL RUPIAH FORMATTER                                      --}}
    {{-- Otomatis format semua input angka dengan prefix "Rp"        --}}
    {{-- dan semua input dengan data-rupiah attribute                 --}}
    {{-- ============================================================ --}}
    <script>
    (function () {
        'use strict';

        /* ---- helpers ---- */
        function toRaw(str) {
            // Hapus semua titik ribuan, kembalikan angka bersih
            return String(str).replace(/\./g, '').replace(/[^\d]/g, '');
        }

        function formatRupiah(str) {
            var raw = toRaw(str);
            if (raw === '') return '';
            return raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        /* ---- format satu input ---- */
        function applyFormat(input) {
            if (input.dataset.rupiahInit) return; // sudah di-init
            input.dataset.rupiahInit = '1';

            // Ubah type ke text agar bisa tampilkan titik
            var originalType = input.type;
            if (originalType === 'number') {
                input.type = 'text';
                input.setAttribute('inputmode', 'numeric');
            }

            // Format nilai awal
            if (input.value !== '') {
                input.value = formatRupiah(input.value);
            }

            // Format saat mengetik
            input.addEventListener('input', function () {
                var pos   = this.selectionStart;
                var oldLen = this.value.length;
                this.value = formatRupiah(this.value);
                // Adjust cursor position after formatting
                var diff = this.value.length - oldLen;
                try { this.setSelectionRange(pos + diff, pos + diff); } catch(e){}
            });

            // Saat focus: tidak perlu ubah (sudah terbaca dengan titik)
            // Saat blur: pastikan format sudah benar
            input.addEventListener('blur', function () {
                this.value = formatRupiah(this.value);
            });
        }

        /* ---- strip sebelum submit ---- */
        function stripForm(form) {
            var inputs = form.querySelectorAll('[data-rupiah-init]');
            inputs.forEach(function (inp) {
                inp.value = toRaw(inp.value);
            });
        }

        /* ---- deteksi input yang harus diformat ---- */
        function detectAndInit(root) {
            root = root || document;

            // 1. Input yang punya sibling/parent input-group-text = "Rp"
            var groups = root.querySelectorAll('.input-group');
            groups.forEach(function (group) {
                var prefixEl = group.querySelector('.input-group-text');
                if (prefixEl && prefixEl.textContent.trim() === 'Rp') {
                    var inp = group.querySelector('input[type="number"], input[type="text"][inputmode="numeric"], input.rupiah-input');
                    // Juga cari input number/text dalam group
                    var inputs = group.querySelectorAll('input');
                    inputs.forEach(function (i) {
                        var name = (i.name || '').toLowerCase();
                        // Skip quantity / step / dimensi / persen
                        if (name.match(/qty|quantity|step|radius|day|percent|persen|weight|length|width|height|min_stock|stock|preorder/)) return;
                        applyFormat(i);
                    });
                }
            });

            // 2. Input dengan class rupiah-input atau data-rupiah
            root.querySelectorAll('.rupiah-input, [data-rupiah]').forEach(function (inp) {
                applyFormat(inp);
            });

            // 3. Input amount di modal hutang supplier
            root.querySelectorAll('input[name="amount"], input[name="unit_price"], input[name="unit_cost"], input[name="harga_jual"], input[name="harga_beli"], input[name="min_purchase"], input[name="max_discount"], input[name="value"][type="number"]').forEach(function(inp) {
                // Hanya jika bukan qty / dimensi
                applyFormat(inp);
            });
        }

        /* ---- Main init ---- */
        document.addEventListener('DOMContentLoaded', function () {
            detectAndInit(document);

            // Strip rupiah sebelum submit semua form
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    stripForm(this);
                });
            });

            // Observe DOM changes (untuk row yang ditambah dinamis / JS)
            if (window.MutationObserver) {
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (m) {
                        m.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                detectAndInit(node);
                                // Pastikan form baru juga di-strip saat submit
                                if (node.tagName === 'FORM') {
                                    node.addEventListener('submit', function () { stripForm(this); });
                                }
                                node.querySelectorAll && node.querySelectorAll('form').forEach(function (f) {
                                    f.addEventListener('submit', function () { stripForm(this); });
                                });
                            }
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });

        // Expose globally agar bisa dipanggil manual setelah render dinamis
        window.initRupiahInputs = detectAndInit;
        window.parseRupiah = function (val) { return parseFloat(toRaw(val)) || 0; };
        window.formatRupiah = formatRupiah;
    })();
    </script>

    @stack('modals')
    @stack('scripts')


</body>

</html>
