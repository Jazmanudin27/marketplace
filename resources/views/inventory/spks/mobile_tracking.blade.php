<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tracking SPK HP #{{ $spk->no_spk }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            padding-bottom: 90px;
        }

        .mobile-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 16px 20px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .spk-badge {
            background: #2563eb;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .stage-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .stage-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sticky-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            padding: 12px 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 1040;
            border-top: 1px solid #e2e8f0;
        }

        .btn-save-mobile {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            padding: 12px;
            border-radius: 12px;
            width: 100%;
            border: none;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }

        /* ── PIN Overlay Screen ── */
        .pin-overlay {
            position: fixed;
            inset: 0;
            background: #0f172a;
            color: #fff;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .pin-dots {
            display: flex;
            gap: 14px;
            margin: 24px 0 30px 0;
        }

        .pin-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #38bdf8;
            background: transparent;
            transition: all 0.2s;
        }

        .pin-dot.filled {
            background: #38bdf8;
            box-shadow: 0 0 10px #38bdf8;
        }

        .keypad-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            width: 100%;
            max-width: 280px;
        }

        .keypad-btn {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s;
        }

        .keypad-btn:active {
            background: rgba(56, 189, 248, 0.3);
        }

        .shake {
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }
    </style>
</head>
<body>

    <!-- ── PIN LOCK OVERLAY SCREEN ── -->
    <div id="pinOverlay" class="pin-overlay" style="{{ session('spk_mobile_unlocked_' . $spk->id) ? 'display:none;' : '' }}">
        <div class="text-center mb-2">
            <div class="fs-1 mb-2">🔒</div>
            <h4 class="fw-bold mb-1">TRACKING SPK #{{ $spk->no_spk }}</h4>
            <p class="text-light-50 small mb-0" style="color: #94a3b8;">Masukkan 4-Digit Kode PIN untuk memperbarui tracking</p>
        </div>

        <div class="pin-dots" id="pinDots">
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
        </div>

        <div id="pinErrorMessage" class="text-danger small mb-3 fw-bold" style="display:none; min-height: 20px;">
            ⚠️ Kode PIN Salah! Silakan coba lagi.
        </div>

        <div class="keypad-grid">
            <div class="keypad-btn" onclick="pressPin('1')">1</div>
            <div class="keypad-btn" onclick="pressPin('2')">2</div>
            <div class="keypad-btn" onclick="pressPin('3')">3</div>
            <div class="keypad-btn" onclick="pressPin('4')">4</div>
            <div class="keypad-btn" onclick="pressPin('5')">5</div>
            <div class="keypad-btn" onclick="pressPin('6')">6</div>
            <div class="keypad-btn" onclick="pressPin('7')">7</div>
            <div class="keypad-btn" onclick="pressPin('8')">8</div>
            <div class="keypad-btn" onclick="pressPin('9')">9</div>
            <div class="keypad-btn text-danger fs-6 fw-bold" onclick="clearPin()">C</div>
            <div class="keypad-btn" onclick="pressPin('0')">0</div>
            <div class="keypad-btn text-warning fs-5" onclick="backspacePin()"><i class="fas fa-backspace"></i></div>
        </div>

        <div class="mt-4 text-center">
            <span class="text-muted small" style="font-size: 11px;">PIN Default: <strong>1234</strong></span>
        </div>
    </div>

    <!-- ── MAIN MOBILE TRACKING APP ── -->
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="spk-badge">SPK #{{ $spk->no_spk }}</span>
            <span class="badge bg-danger font-monospace">Deadline: {{ $spk->deadline ? $spk->deadline->format('d M Y') : '—' }}</span>
        </div>
        <h5 class="fw-bold mb-1 text-white">{{ $spk->pemesan ?: 'INTERNAL / STOK GUDANG' }}</h5>
        <div class="small opacity-75">
            <i class="fas fa-building me-1"></i> Instansi: {{ $spk->instansi ?: '—' }}
        </div>
    </div>

    <div class="container-fluid px-3 pt-3">
        <form id="mobileTrackingForm" action="{{ route('spks.mobile_update_tracking', $spk->id) }}" method="POST">
            @csrf

            <!-- ── STATUS SPK GLOBAL ── -->
            <div class="stage-card">
                <div class="stage-title">
                    <span><i class="fas fa-flag text-primary me-2"></i>STATUS PROSES SPK</span>
                    <span class="badge bg-primary text-white">{{ $spk->status }}</span>
                </div>
                <select name="spk_status" class="form-select form-select-lg fw-bold border-primary">
                    <option value="DRAFT" {{ $spk->status === 'DRAFT' ? 'selected' : '' }}>📝 DRAFT (Persiapan)</option>
                    <option value="DIPROSES" {{ $spk->status === 'DIPROSES' ? 'selected' : '' }}>⚡ DIPROSES (Sedang Berjalan)</option>
                    <option value="SELESAI" {{ $spk->status === 'SELESAI' ? 'selected' : '' }}>✅ SELESAI (Siap Kirim / Stock)</option>
                </select>
            </div>

            <!-- ── PETA PROSES PRODUKSI (ACCORDION STAGES) ── -->
            @foreach($spk->items as $itemIdx => $item)
                <div class="stage-card">
                    <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                        <div>
                            <div class="fw-bold text-primary font-monospace" style="font-size: 14px;">{{ $item->sku ?: $item->nama_produk }}</div>
                            <div class="small text-muted">{{ $item->ukuran ? 'Ukuran: ' . $item->ukuran : '' }} | Qty Target: <strong class="text-dark">{{ $item->quantity }} Pcs</strong></div>
                        </div>
                        <span class="badge bg-info text-dark font-monospace">{{ $item->quantity }} Pcs</span>
                    </div>

                    <!-- Detail Pekerja (Pemotong & Penjahit) -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 11px;">✂️ PEMOTONG</label>
                            <input type="text" name="items[{{ $item->id }}][pemotong]" class="form-control form-control-sm" value="{{ $item->pemotong }}" placeholder="Nama Pemotong">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 11px;">🧵 PENJAHIT</label>
                            <input type="text" name="items[{{ $item->id }}][penjahit]" class="form-control form-control-sm" value="{{ $item->penjahit }}" placeholder="Nama Penjahit" list="tailor_datalist">
                        </div>
                    </div>

                    <!-- Progress per Stage -->
                    <div class="bg-light p-2 rounded-3 border">
                        <div class="small fw-bold text-dark mb-2"><i class="fas fa-tasks me-1 text-primary"></i> Progress Selesai Per Tahapan:</div>
                        @foreach($spk->proses as $proses)
                            @php
                                $pg = $item->progres->where('spk_proses_id', $proses->id)->first();
                                $qtyDone = $pg ? $pg->qty_done : 0;
                            @endphp
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-light">
                                <span class="small fw-semibold text-secondary" style="font-size: 12px;">{{ $proses->nama_proses }}</span>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <input type="number" name="progres[{{ $pg?->id ?: 0 }}]" class="form-control text-center fw-bold font-monospace" value="{{ $qtyDone }}" min="0" max="{{ $item->quantity }}">
                                    <span class="input-group-text px-1 small" style="font-size: 10px;">/{{ $item->quantity }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <!-- Data Datalist Tailors -->
            <datalist id="tailor_datalist">
                @foreach($tailors as $tailor)
                    <option value="{{ $tailor->name }}">
                @endforeach
            </datalist>

            <!-- ── STICKY BOTTOM ACTION BAR ── -->
            <div class="sticky-bottom-bar">
                <button type="submit" class="btn btn-save-mobile">
                    <i class="fas fa-save me-2"></i> SIMPAN SINKRONISASI HP
                </button>
            </div>
        </form>
    </div>

    <!-- Alert Modal Feedback -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentPin = '';
        const correctPin = '{{ $correctPin }}';
        const verifyPinUrl = '{{ route("spks.mobile_verify_pin", $spk->id) }}';

        function pressPin(val) {
            if (currentPin.length < 4) {
                currentPin += val;
                updatePinDots();
                if (currentPin.length === 4) {
                    checkPin();
                }
            }
        }

        function backspacePin() {
            if (currentPin.length > 0) {
                currentPin = currentPin.slice(0, -1);
                updatePinDots();
            }
        }

        function clearPin() {
            currentPin = '';
            updatePinDots();
        }

        function updatePinDots() {
            const dots = document.querySelectorAll('.pin-dot');
            dots.forEach((dot, idx) => {
                if (idx < currentPin.length) {
                    dot.classList.add('filled');
                } else {
                    dot.classList.remove('filled');
                }
            });
        }

        function checkPin() {
            fetch(verifyPinUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ pin: currentPin })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pinOverlay').style.display = 'none';
                    Swal.fire({
                        icon: 'success',
                        title: 'Akses Diterima!',
                        text: 'Silakan perbarui tracking tahap produksi SPK.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    const overlay = document.getElementById('pinOverlay');
                    overlay.classList.add('shake');
                    document.getElementById('pinErrorMessage').style.display = 'block';
                    setTimeout(() => {
                        overlay.classList.remove('shake');
                        clearPin();
                    }, 500);
                }
            })
            .catch(() => {
                if (currentPin === correctPin) {
                    document.getElementById('pinOverlay').style.display = 'none';
                } else {
                    document.getElementById('pinErrorMessage').style.display = 'block';
                    clearPin();
                }
            });
        }

        // Form Submit Handler
        document.getElementById('mobileTrackingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Menyimpan...',
                text: 'Memperbarui data tracking SPK',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', 'Gagal memperbarui data.', 'error');
                }
            })
            .catch(() => {
                this.submit();
            });
        });
    </script>
</body>
</html>
