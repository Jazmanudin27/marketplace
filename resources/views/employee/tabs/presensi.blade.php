<div id="tab-presensi" class="tab-pane active">
    <!-- Date and Schedule Banner -->
    <div class="card mb-3 border rounded shadow-sm bg-white">
        <div class="card-body text-center py-3">
            <div class="d-inline-flex align-items-center gap-2 bg-light border px-3 py-1.5 rounded-pill mb-2 small fw-bold text-dark">
                <i class="fas fa-calendar-day text-primary"></i>
                <span>{{ date('d') }} {{ $monthNamesIndo[date('m')] ?? '' }} {{ date('Y') }}</span>
            </div>
            <div class="small text-muted">
                Jadwal Kerja: <span class="fw-bold text-dark">{{ $scheduleIn }} - {{ $scheduleOut }}</span>
            </div>
        </div>
    </div>

    @php
        $ci = $todayAttendance?->clock_in;
        $co = $todayAttendance?->clock_out;
        $late = $todayAttendance?->late_minutes ?? 0;
    @endphp

    <!-- Check In/Out Actions -->
    <div class="row g-2 mb-3">
        <!-- Check In Button/Widget -->
        <div class="col-6">
            @if (!$ci)
                <form id="formClockIn" method="POST" action="{{ route('employee.clock-in') }}">
                    @csrf
                    <input type="hidden" name="latitude" id="latitudeIn">
                    <input type="hidden" name="longitude" id="longitudeIn">
                    <input type="hidden" name="photo" id="photoIn">
                    <button type="button" class="btn btn-outline-success w-100 py-4 d-flex flex-column align-items-center justify-content-center h-100 rounded-3 shadow-sm" onclick="startAttendanceVerification('in')">
                        <i class="fas fa-sign-in-alt fs-2 mb-2 text-success"></i>
                        <span class="fw-bold d-block text-dark small">Check In</span>
                        <small class="text-muted" style="font-size: 0.7rem;">Belum Scan</small>
                    </button>
                </form>
            @else
                <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 py-4 px-2 text-center h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-check-circle text-success fs-2 mb-2"></i>
                    <span class="fw-bold d-block text-dark small">Checked In</span>
                    <span class="text-success fw-bold small" style="font-size: 0.75rem;">{{ date('H:i', strtotime($ci)) }} WIB</span>
                </div>
            @endif
        </div>

        <!-- Check Out Button/Widget -->
        <div class="col-6">
            @if ($co)
                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 py-4 px-2 text-center h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-check-circle text-primary fs-2 mb-2"></i>
                    <span class="fw-bold d-block text-dark small">Checked Out</span>
                    <span class="text-primary fw-bold small" style="font-size: 0.75rem;">{{ date('H:i', strtotime($co)) }} WIB</span>
                </div>
            @elseif ($ci)
                <form id="formClockOut" method="POST" action="{{ route('employee.clock-out') }}">
                    @csrf
                    <input type="hidden" name="latitude" id="latitudeOut">
                    <input type="hidden" name="longitude" id="longitudeOut">
                    <input type="hidden" name="photo" id="photoOut">
                    <button type="button" class="btn btn-outline-primary w-100 py-4 d-flex flex-column align-items-center justify-content-center h-100 rounded-3 shadow-sm" onclick="startAttendanceVerification('out')">
                        <i class="fas fa-sign-out-alt fs-2 mb-2 text-primary"></i>
                        <span class="fw-bold d-block text-dark small">Check Out</span>
                        <small class="text-muted" style="font-size: 0.7rem;">Belum Scan</small>
                    </button>
                </form>
            @else
                <div class="bg-light border rounded-3 py-4 px-2 text-center h-100 d-flex flex-column align-items-center justify-content-center text-muted" style="opacity: 0.65;">
                    <i class="fas fa-lock fs-2 mb-2"></i>
                    <span class="fw-bold d-block small">Check Out</span>
                    <small style="font-size: 0.7rem;">Belum Scan</small>
                </div>
            @endif
        </div>
    </div>

    <!-- GPS & IP Verification Info -->
    <div class="card mb-3 border rounded shadow-sm bg-white">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-1.5 small text-muted">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 8px; height: 8px;"></span>
                    <span class="fw-semibold">GPS: Terdeteksi</span>
                </div>
                <div class="d-flex align-items-center gap-1.5 small text-muted">
                    <i class="fas fa-shield-alt text-info" style="font-size: 0.85rem;"></i>
                    <span class="fw-semibold">Koneksi Aman</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Kehadiran Terakhir -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <span class="fw-bold text-dark small">Aktivitas Kehadiran Terakhir</span>
        <a href="#" onclick="switchTab('riwayat')" class="text-primary fw-bold text-decoration-none small" style="font-size: 0.75rem;">Lihat Semua</a>
    </div>

    <div class="card mb-3 border rounded shadow-sm bg-white">
        <div class="card-body">
            @php
                $previewLogs = $history
                    ->sortByDesc('date')
                    ->whereNotIn('status', ['sick', 'permission', 'leave', 'sakit', 'izin', 'cuti'])
                    ->take(3);
                $statusLabels = [
                    'present' => 'Hadir',
                    'alpha' => 'Alpha',
                    'izin' => 'Izin',
                    'sakit' => 'Sakit',
                    'cuti' => 'Cuti',
                    'sick' => 'Sakit',
                    'permission' => 'Izin',
                    'leave' => 'Cuti',
                ];
            @endphp

            @if ($previewLogs->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada data presensi terdaftar.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($previewLogs as $att)
                        @php
                            $d = \Carbon\Carbon::parse($att->date);
                            $dayShortName = $d->format('D');
                            $dayNameIndo = $dayNamesIndo[$dayShortName] ?? $dayShortName;

                            $jamKerja = '-';
                            if ($att->clock_in && $att->clock_out) {
                                $inTime = \Carbon\Carbon::parse($att->clock_in);
                                $outTime = \Carbon\Carbon::parse($att->clock_out);
                                $diffInMinutes = abs($outTime->diffInMinutes($inTime, false));
                                $hours = $diffInMinutes / 60;
                                $jamKerja = number_format($hours, 2, ',', '.') . ' Jam';
                            }

                            $badgeClass = 'bg-secondary';
                            if ($att->status === 'present') {
                                $badgeClass = $att->clock_in ? 'bg-success' : 'bg-warning';
                            } elseif (in_array($att->status, ['sick', 'sakit'])) {
                                $badgeClass = 'bg-danger';
                            } elseif (in_array($att->status, ['permission', 'izin'])) {
                                $badgeClass = 'bg-primary';
                            } elseif (in_array($att->status, ['leave', 'cuti'])) {
                                $badgeClass = 'bg-info';
                            }
                        @endphp
                        <div class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small">{{ $dayNameIndo }}, {{ $d->format('d') }} {{ $monthNamesIndo[$d->format('m')] ?? '' }} {{ $d->format('Y') }}</span>
                                <span class="badge {{ $badgeClass }}">{{ $statusLabels[$att->status] ?? ucfirst($att->status) }}</span>
                            </div>

                            @if (in_array($att->status, ['present', 'alpha']))
                                <div class="row g-2 mt-1">
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-light text-center">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Check In</small>
                                            <span class="fw-semibold text-dark small" style="font-size: 0.8rem;">
                                                {{ $att->clock_in ? date('H:i', strtotime($att->clock_in)) : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-light text-center">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Check Out</small>
                                            <span class="fw-semibold text-dark small" style="font-size: 0.8rem;">
                                                {{ $att->clock_out ? date('H:i', strtotime($att->clock_out)) : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-light text-center">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Jam Kerja</small>
                                            <span class="fw-semibold text-dark small" style="font-size: 0.8rem;">
                                                {{ $jamKerja }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    Keterangan: <span class="text-dark">{{ $att->notes ?: 'Telah disetujui HRD' }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Riwayat Pengajuan Izin / Cuti -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <span class="fw-bold text-dark small">Riwayat Pengajuan Izin / Cuti</span>
        <a href="#" onclick="switchTab('izin')" class="text-primary fw-bold text-decoration-none small" style="font-size: 0.75rem;">Lihat Semua</a>
    </div>

    <div class="card mb-3 border rounded shadow-sm bg-white">
        <div class="card-body">
            @php
                $previewLeaves = $leaveRequests->take(3);
            @endphp

            @if ($previewLeaves->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada riwayat pengajuan izin, sakit, atau cuti.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($previewLeaves as $lr)
                        @php
                            $start = \Carbon\Carbon::parse($lr->start_date);
                            $end = \Carbon\Carbon::parse($lr->end_date);
                            $diff = $start->diffInDays($end) + 1;

                            $typeLabels = [
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'leave' => 'Cuti',
                             ];

                            $statusLabels = [
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ];

                            $badgeClass = 'bg-warning';
                            if ($lr->status === 'approved') {
                                $badgeClass = 'bg-success';
                            } elseif ($lr->status === 'rejected') {
                                $badgeClass = 'bg-danger';
                            }

                            $typeBadgeClass = 'bg-secondary';
                            if ($lr->type === 'sick') {
                                $typeBadgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                            } elseif ($lr->type === 'permission') {
                                $typeBadgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                            } elseif ($lr->type === 'leave') {
                                $typeBadgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                            }
                        @endphp
                        <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center d-flex flex-column justify-content-center px-2 py-1 rounded small fw-bold {{ $typeBadgeClass }}" style="min-width: 50px; height: 44px;">
                                    <span class="fs-6 lh-1">{{ $diff }}</span>
                                    <span style="font-size: 0.6rem; text-transform: uppercase;">Hari</span>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $typeLabels[$lr->type] ?? ucfirst($lr->type) }}</div>
                                    <div class="text-muted small">
                                        @if ($diff === 1)
                                            {{ $start->format('d M Y') }}
                                        @else
                                            {{ $start->format('d M') }} s/d {{ $end->format('d M Y') }}
                                        @endif

                                        @if ($lr->notes)
                                            <span class="d-block fst-italic text-muted mt-1">"{{ $lr->notes }}"</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }} py-1.5 px-2.5 small flex-shrink-0">
                                {{ $statusLabels[$lr->status] ?? ucfirst($lr->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Camera & GPS Verification Modal -->
    <div id="attendanceCameraModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark">Verifikasi Kehadiran</h5>
                    <button type="button" class="btn-close" onclick="closeCameraModal()"></button>
                </div>
                <div class="modal-body p-0 position-relative bg-dark" style="aspect-ratio: 4/3; overflow: hidden;">
                    <video id="attendanceVideo" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                    <div id="attendanceMap" class="position-absolute start-0 top-0 w-100 h-100" style="display: none; z-index: 10;"></div>
                    <canvas id="attendanceCanvas" style="display: none;"></canvas>

                    <div id="cameraLoadingSpinner" class="position-absolute start-0 top-0 w-100 h-100 bg-dark bg-opacity-75 d-flex flex-column align-items-center justify-content-center text-white" style="z-index: 15;">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="mb-0 fw-bold small">Mengunci GPS & Kamera...</p>
                    </div>

                    <button type="button" id="btnTogglePreview" class="btn btn-dark btn-sm position-absolute bottom-0 end-0 m-2 opacity-90" onclick="togglePreviewMode()" style="display: none; z-index: 20;">
                        <i id="iconMap" class="fas fa-map"></i>
                        <i id="iconCamera" class="fas fa-camera" style="display: none;"></i>
                        <span id="textTogglePreview" class="ms-1 small">Lihat Peta</span>
                    </button>
                </div>
                
                <div class="p-3 border-top bg-light">
                    <div class="d-flex align-items-center mb-2" id="statusGps">
                        <span class="spinner-grow spinner-grow-sm text-warning me-2" id="dotGps" style="width: 8px; height: 8px;"></span>
                        <span id="textGps" class="small text-muted fw-semibold">Mencari lokasi GPS...</span>
                    </div>
                    <div class="d-flex align-items-center mb-0" id="statusCamera">
                        <span class="spinner-grow spinner-grow-sm text-warning me-2" id="dotCamera" style="width: 8px; height: 8px;"></span>
                        <span id="textCamera" class="small text-muted fw-semibold">Menghubungkan ke kamera...</span>
                    </div>
                    <div id="distanceWarning" class="alert alert-danger d-flex align-items-center gap-2 mt-2 py-2 px-3 mb-0" style="display: none !important;">
                        <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
                        <span id="textWarning" class="small fw-semibold">Anda berada di luar radius kantor!</span>
                    </div>
                </div>
                
                <div class="modal-footer justify-content-between bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeCameraModal()">Batal</button>
                    <button type="button" id="btnSubmitAttendance" class="btn btn-primary btn-sm d-flex align-items-center gap-2" disabled onclick="captureSelfieAndSubmit()">
                        <i class="fas fa-camera"></i>
                        <span>Ambil Foto & Absen</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let attendanceType = null;
        let mediaStream = null;
        let userLatitude = null;
        let userLongitude = null;
        let gpsLocked = false;
        let cameraReady = false;

        let map = null;
        let userMarker = null;
        let officeMarker = null;
        let radiusCircle = null;
        let bsModal = null;

        const OFFICE_LAT = @json($employee->tenant->office_latitude ?? null);
        const OFFICE_LNG = @json($employee->tenant->office_longitude ?? null);
        const OFFICE_RADIUS = @json($employee->tenant->office_radius ?? 20);

        function startAttendanceVerification(type) {
            attendanceType = type;
            gpsLocked = false;
            cameraReady = false;
            userLatitude = null;
            userLongitude = null;

            if (!bsModal) {
                bsModal = new bootstrap.Modal(document.getElementById('attendanceCameraModal'), {
                    keyboard: false
                });
            }
            bsModal.show();

            document.getElementById('cameraLoadingSpinner').style.setProperty('display', 'flex', 'important');
            document.getElementById('btnSubmitAttendance').disabled = true;

            const distWarning = document.getElementById('distanceWarning');
            distWarning.style.setProperty('display', 'none', 'important');
            distWarning.classList.remove('d-flex');

            updateStatusIndicator('Gps', 'warning', 'Mencari lokasi GPS...');
            updateStatusIndicator('Camera', 'warning', 'Menghubungkan ke kamera...');

            if (map) {
                map.remove();
                map = null;
            }
            document.getElementById('attendanceMap').style.display = 'none';
            document.getElementById('attendanceVideo').style.visibility = 'visible';
            document.getElementById('btnTogglePreview').style.display = 'none';
            document.getElementById('iconMap').style.display = 'inline-block';
            document.getElementById('iconCamera').style.display = 'none';
            document.getElementById('textTogglePreview').textContent = 'Lihat Peta';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(onGpsSuccess, onGpsError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                updateStatusIndicator('Gps', 'danger', 'GPS tidak didukung oleh browser ini.');
            }

            const video = document.getElementById('attendanceVideo');
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                }).then(stream => {
                    mediaStream = stream;
                    video.srcObject = stream;
                    video.onloadedmetadata = () => {
                        cameraReady = true;
                        updateStatusIndicator('Camera', 'success', 'Kamera depan aktif.');
                        checkVerificationStatus();
                    };
                }).catch(err => {
                    console.error(err);
                    updateStatusIndicator('Camera', 'danger', 'Gagal mengakses kamera: ' + err.message);
                    document.getElementById('cameraLoadingSpinner').style.setProperty('display', 'none', 'important');
                });
            } else {
                updateStatusIndicator('Camera', 'danger', 'Kamera tidak didukung oleh browser ini.');
                document.getElementById('cameraLoadingSpinner').style.setProperty('display', 'none', 'important');
            }
        }

        function onGpsSuccess(position) {
            userLatitude = position.coords.latitude;
            userLongitude = position.coords.longitude;
            gpsLocked = true;

            if (OFFICE_LAT !== null && OFFICE_LNG !== null) {
                const distance = calculateHaversineDistance(userLatitude, userLongitude, OFFICE_LAT, OFFICE_LNG);
                const maxRadius = OFFICE_RADIUS || 20;

                if (distance > maxRadius) {
                    updateStatusIndicator('Gps', 'danger', `Di luar radius kantor (${Math.round(distance)} m)`);
                    document.getElementById('textWarning').textContent =
                        `Anda berada di luar radius kantor. Jarak: ${Math.round(distance)} meter. Maksimum: ${maxRadius} meter.`;
                    const distWarning = document.getElementById('distanceWarning');
                    distWarning.style.setProperty('display', 'flex', 'important');
                    distWarning.classList.add('d-flex');
                    gpsLocked = false;
                } else {
                    updateStatusIndicator('Gps', 'success', `Lokasi cocok (${Math.round(distance)} m dari kantor)`);
                    const distWarning = document.getElementById('distanceWarning');
                    distWarning.style.setProperty('display', 'none', 'important');
                    distWarning.classList.remove('d-flex');
                }
            } else {
                updateStatusIndicator('Gps', 'success', `GPS Terkunci (Kantor belum dikonfigurasi)`);
                const distWarning = document.getElementById('distanceWarning');
                distWarning.style.setProperty('display', 'none', 'important');
                distWarning.classList.remove('d-flex');
            }

            document.getElementById('btnTogglePreview').style.display = 'flex';
            checkVerificationStatus();
        }

        function onGpsError(error) {
            console.error(error);
            let msg = 'Gagal mendeteksi lokasi GPS.';
            if (error.code === error.PERMISSION_DENIED) {
                msg = 'Izin lokasi ditolak. Aktifkan lokasi di browser.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                msg = 'Sinyal lokasi tidak tersedia.';
            } else if (error.code === error.TIMEOUT) {
                msg = 'Waktu pencarian lokasi habis.';
            }
            updateStatusIndicator('Gps', 'danger', msg);
            checkVerificationStatus();
        }

        function initMap() {
            if (map) return;
            if (userLatitude === null || userLongitude === null) return;

            const mapCenter = [userLatitude, userLongitude];
            map = L.map('attendanceMap', {
                zoomControl: false,
                attributionControl: false
            }).setView(mapCenter, 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(map);

            if (OFFICE_LAT !== null && OFFICE_LNG !== null) {
                const officePos = [OFFICE_LAT, OFFICE_LNG];

                officeMarker = L.marker(officePos).addTo(map)
                    .bindPopup('<b>Lokasi Kantor</b>')
                    .openPopup();

                const distance = calculateHaversineDistance(userLatitude, userLongitude, OFFICE_LAT, OFFICE_LNG);
                const maxRadius = OFFICE_RADIUS || 20;
                const isInside = distance <= maxRadius;

                radiusCircle = L.circle(officePos, {
                    color: isInside ? '#10b981' : '#ef4444',
                    fillColor: isInside ? '#10b981' : '#ef4444',
                    fillOpacity: 0.15,
                    radius: maxRadius
                }).addTo(map);
            }

            userMarker = L.circleMarker(mapCenter, {
                color: '#0d6efd',
                fillColor: '#0d6efd',
                fillOpacity: 0.8,
                radius: 8
            }).addTo(map).bindPopup('<b>Lokasi Anda</b>');

            if (OFFICE_LAT !== null && OFFICE_LNG !== null) {
                const bounds = L.latLngBounds([mapCenter, [OFFICE_LAT, OFFICE_LNG]]);
                map.fitBounds(bounds, {
                    padding: [30, 30]
                });
            }
        }

        function togglePreviewMode() {
            const mapEl = document.getElementById('attendanceMap');
            const videoEl = document.getElementById('attendanceVideo');
            const iconMap = document.getElementById('iconMap');
            const iconCamera = document.getElementById('iconCamera');
            const label = document.getElementById('textTogglePreview');

            if (mapEl.style.display === 'none') {
                mapEl.style.display = 'block';
                videoEl.style.visibility = 'hidden';
                iconMap.style.display = 'none';
                iconCamera.style.display = 'inline-block';
                label.textContent = 'Lihat Kamera';

                initMap();
                if (map) {
                    setTimeout(() => map.invalidateSize(), 50);
                }
            } else {
                mapEl.style.display = 'none';
                videoEl.style.visibility = 'visible';
                iconMap.style.display = 'inline-block';
                iconCamera.style.display = 'none';
                label.textContent = 'Lihat Peta';
            }
        }

        function checkVerificationStatus() {
            if (cameraReady) {
                document.getElementById('cameraLoadingSpinner').style.setProperty('display', 'none', 'important');
            }

            if (cameraReady && gpsLocked) {
                document.getElementById('btnSubmitAttendance').disabled = false;
            } else {
                document.getElementById('btnSubmitAttendance').disabled = true;
            }
        }

        function updateStatusIndicator(id, status, text) {
            const dot = document.getElementById('dot' + id);
            const span = document.getElementById('text' + id);

            if (status === 'success') {
                dot.className = 'fas fa-check-circle text-success me-2';
                dot.style.display = 'inline-block';
                dot.style.width = 'auto';
                dot.style.height = 'auto';
            } else if (status === 'danger') {
                dot.className = 'fas fa-times-circle text-danger me-2';
                dot.style.display = 'inline-block';
                dot.style.width = 'auto';
                dot.style.height = 'auto';
            } else {
                dot.className = 'spinner-grow spinner-grow-sm text-warning me-2';
                dot.style.display = 'inline-block';
                dot.style.width = '8px';
                dot.style.height = '8px';
            }
            span.textContent = text;
        }

        function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function closeCameraModal() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            if (map) {
                map.remove();
                map = null;
            }
            if (bsModal) {
                bsModal.hide();
            }
        }

        function captureSelfieAndSubmit() {
            const video = document.getElementById('attendanceVideo');
            const canvas = document.getElementById('attendanceCanvas');
            const context = canvas.getContext('2d');

            const btn = document.getElementById('btnSubmitAttendance');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memproses...';

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);

            if (attendanceType === 'in') {
                document.getElementById('latitudeIn').value = userLatitude;
                document.getElementById('longitudeIn').value = userLongitude;
                document.getElementById('photoIn').value = photoDataUrl;
                document.getElementById('formClockIn').submit();
            } else if (attendanceType === 'out') {
                document.getElementById('latitudeOut').value = userLatitude;
                document.getElementById('longitudeOut').value = userLongitude;
                document.getElementById('photoOut').value = photoDataUrl;
                document.getElementById('formClockOut').submit();
            }

            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            if (map) {
                map.remove();
                map = null;
            }
        }
    </script>
</div>
