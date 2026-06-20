<div id="tab-presensi" class="tab-pane active">
    <!-- Floating Mockup Main Card -->
    <div class="mockup-main-card">

        <!-- Inner Check In/Out Panel -->
        <div class="mockup-inner-panel">
            <div class="mockup-date-banner">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>{{ date('d') }} {{ $monthNamesIndo[date('m')] ?? '' }} {{ date('Y') }}</span>
            </div>

            <!-- Jam Masuk & Keluar Kerja (Jadwal) -->
            <div
                style="text-align: center; margin-top: -8px; margin-bottom: 16px; font-size: 11.5px; color: var(--text-secondary);">
                Jadwal Kerja: <strong style="color: white; font-family: var(--font-display);">{{ $scheduleIn }} -
                    {{ $scheduleOut }}</strong>
            </div>

            @php
                $ci = $todayAttendance?->clock_in;
                $co = $todayAttendance?->clock_out;
                $late = $todayAttendance?->late_minutes ?? 0;
            @endphp

            <div class="mockup-check-grid">
                <!-- Check In Button/Widget -->
                @if (!$ci)
                    <form id="formClockIn" method="POST" action="{{ route('employee.clock-in') }}">
                        @csrf
                        <input type="hidden" name="latitude" id="latitudeIn">
                        <input type="hidden" name="longitude" id="longitudeIn">
                        <input type="hidden" name="photo" id="photoIn">
                        <button type="button" class="mockup-box-btn" onclick="startAttendanceVerification('in')">
                            <div class="mockup-box-content check-in">
                                <div class="mockup-box-top">
                                    <span class="mockup-box-time">Belum Scan</span>
                                    <div class="mockup-box-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <polyline points="9 11 12 14 22 4"></polyline>
                                            <path d="M21 12v7a2 2 0 0 1-2-2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="mockup-box-bottom">Check In</div>
                            </div>
                        </button>
                    </form>
                @else
                    <div class="mockup-box-content check-in disabled">
                        <div class="mockup-box-top">
                            <span class="mockup-box-time">{{ date('H:i', strtotime($ci)) }} WIB</span>
                            <div class="mockup-box-icon" style="background: rgba(255,255,255,0.35);">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="mockup-box-bottom" style="opacity: 0.9;">Checked In</div>
                    </div>
                @endif

                <!-- Check Out Button/Widget -->
                @if ($co)
                    <div class="mockup-box-content check-out disabled">
                        <div class="mockup-box-top">
                            <span class="mockup-box-time">{{ date('H:i', strtotime($co)) }} WIB</span>
                            <div class="mockup-box-icon" style="background: rgba(255,255,255,0.35);">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="mockup-box-bottom" style="opacity: 0.9;">Checked Out</div>
                    </div>
                @elseif ($ci)
                    <form id="formClockOut" method="POST" action="{{ route('employee.clock-out') }}">
                        @csrf
                        <input type="hidden" name="latitude" id="latitudeOut">
                        <input type="hidden" name="longitude" id="longitudeOut">
                        <input type="hidden" name="photo" id="photoOut">
                        <button type="button" class="mockup-box-btn" onclick="startAttendanceVerification('out')">
                            <div class="mockup-box-content check-out">
                                <div class="mockup-box-top">
                                    <span class="mockup-box-time">Belum Scan</span>
                                    <div class="mockup-box-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                            <polyline points="16 17 21 12 16 7"></polyline>
                                            <line x1="21" y1="12" x2="9" y2="12">
                                            </line>
                                        </svg>
                                    </div>
                                </div>
                                <div class="mockup-box-bottom">Check Out</div>
                            </div>
                        </button>
                    </form>
                @else
                    <div class="mockup-box-content inactive">
                        <div class="mockup-box-top">
                            <span class="mockup-box-time">Belum Scan</span>
                            <div class="mockup-box-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2">
                                    </rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mockup-box-bottom">Check Out</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- GPS & IP Verification Info -->
    <div class="glass-card" style="margin-bottom: 20px; padding: 12px 16px;">
        <div class="info-pill-container" style="margin-top: 0;">
            <div class="info-pill">
                <div class="info-pulse-dot"></div>
                <span>GPS: Terdeteksi (Radius)</span>
            </div>
            <div class="info-pill">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                    style="color: var(--color-info); flex-shrink: 0;">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Koneksi Aman</span>
            </div>
        </div>
    </div>

    <!-- Recent Logs Section (Mockup-inspired) -->
    <div
        style="margin-top: 24px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; padding: 0 4px;">
        <span style="font-family: var(--font-display); font-size: 14px; font-weight: 700; color: white;">Aktivitas
            Kehadiran Terakhir</span>
        <a href="#" onclick="switchTab('riwayat')"
            style="font-size: 12px; color: var(--color-primary); font-weight: 700; text-decoration: none;">Lihat
            Semua</a>
    </div>

    <div class="list-wrapper">
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
            <div style="text-align: center; color: var(--text-muted); padding: 20px 0; font-size: 12.5px;">
                Belum ada data presensi terdaftar.
            </div>
        @else
            @foreach ($previewLogs as $att)
                @php
                    $d = \Carbon\Carbon::parse($att->date);
                    $dayShortName = $d->format('D');
                    $dayNameIndo = $dayNamesIndo[$dayShortName] ?? $dayShortName;

                    // Calculate work hours if check out exists
                    $jamKerja = '-';
                    if ($att->clock_in && $att->clock_out) {
                        $inTime = \Carbon\Carbon::parse($att->clock_in);
                        $outTime = \Carbon\Carbon::parse($att->clock_out);
                        $diffInMinutes = abs($outTime->diffInMinutes($inTime, false));
                        $hours = $diffInMinutes / 60;
                        $jamKerja = number_format($hours, 2, ',', '.') . ' Jam';
                    }
                @endphp
                <div class="log-item-card">
                    <div class="log-item-header">
                        <span>{{ $dayNameIndo }}, {{ $d->format('d') }} {{ $monthNamesIndo[$d->format('m')] ?? '' }}
                            {{ $d->format('Y') }}</span>
                        <span class="status-pill {{ $att->status }}" style="font-size: 10px; padding: 2px 8px;">
                            {{ $statusLabels[$att->status] ?? ucfirst($att->status) }}
                        </span>
                    </div>
                    @if (in_array($att->status, ['present', 'alpha']))
                        <div class="log-item-grid">
                            <div class="log-subitem">
                                <span class="log-subitem-title">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        style="color: {{ $att->clock_in ? 'var(--color-success)' : 'rgba(255, 255, 255, 0.25)' }};">
                                        @if ($att->clock_in)
                                            <polyline points="9 11 12 14 22 4"></polyline>
                                        @else
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12">
                                            </line>
                                            <line x1="12" y1="16" x2="12.01" y2="16">
                                            </line>
                                        @endif
                                    </svg>
                                    Check In
                                </span>
                                <span class="log-subitem-val"
                                    style="{{ !$att->clock_in ? 'font-size: 11px; font-weight: 500; color: rgba(255, 255, 255, 0.25); font-style: italic;' : '' }}">
                                    {{ $att->clock_in ? date('H:i', strtotime($att->clock_in)) : 'Belum Scan' }}
                                </span>
                            </div>
                            <div class="log-subitem">
                                <span class="log-subitem-title">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        style="color: {{ $att->clock_out ? 'var(--color-info)' : 'rgba(255, 255, 255, 0.25)' }};">
                                        @if ($att->clock_out)
                                            <polyline points="9 11 12 14 22 4"></polyline>
                                        @else
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12">
                                            </line>
                                            <line x1="12" y1="16" x2="12.01" y2="16">
                                            </line>
                                        @endif
                                    </svg>
                                    Check Out
                                </span>
                                <span class="log-subitem-val"
                                    style="{{ !$att->clock_out ? 'font-size: 11px; font-weight: 500; color: rgba(255, 255, 255, 0.25); font-style: italic;' : '' }}">
                                    {{ $att->clock_out ? date('H:i', strtotime($att->clock_out)) : 'Belum Scan' }}
                                </span>
                            </div>
                            <div class="log-subitem">
                                <span class="log-subitem-title">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round" style="color: var(--color-primary);">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Jam Kerja
                                </span>
                                <span class="log-subitem-val">{{ $jamKerja }}</span>
                            </div>
                        </div>
                    @else
                        <div
                            style="font-size: 11.5px; color: var(--text-secondary); margin-top: 8px; padding-left: 2px; line-height: 1.4;">
                            <i class="fas fa-info-circle text-primary" style="margin-right: 4px;"></i> Keterangan:
                            <span
                                style="color: var(--text-primary);">{{ $att->notes ?: 'Telah disetujui HRD' }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- Recent Leave Requests Section -->
    <div
        style="margin-top: 28px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; padding: 0 4px;">
        <span style="font-family: var(--font-display); font-size: 14px; font-weight: 700; color: white;">Riwayat
            Pengajuan Izin / Cuti</span>
        <a href="#" onclick="switchTab('izin')"
            style="font-size: 12px; color: var(--color-primary); font-weight: 700; text-decoration: none;">Lihat
            Semua</a>
    </div>

    <div class="list-wrapper" style="margin-bottom: 30px;">
        @php
            $previewLeaves = $leaveRequests->take(3);
        @endphp

        @if ($previewLeaves->isEmpty())
            <div style="text-align: center; color: var(--text-muted); padding: 20px 0; font-size: 12.5px;">
                Belum ada riwayat pengajuan izin, sakit, atau cuti.
            </div>
        @else
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

                    $typeColors = [
                        'sick' => '#ef4444',
                        'permission' => '#7c6af7',
                        'leave' => '#f59e0b',
                    ];
                @endphp
                <div class="item-card" style="margin-bottom: 10px;">
                    <div class="item-left">
                        <div class="item-date-badge"
                            style="background: rgba(255, 255, 255, 0.03); border-color: {{ $typeColors[$lr->type] ?? 'var(--border-color)' }};">
                            <span
                                style="font-size: 11px; font-weight: 700; color: {{ $typeColors[$lr->type] ?? 'white' }};">
                                {{ $diff }}
                            </span>
                            <span
                                style="font-size: 7.5px; text-transform: uppercase; color: var(--text-secondary); margin-top: 1px;">
                                Hari
                            </span>
                        </div>
                        <div>
                            <div class="item-title">
                                {{ $typeLabels[$lr->type] ?? ucfirst($lr->type) }}
                            </div>
                            <div class="item-subtext">
                                @if ($diff === 1)
                                    {{ $start->format('d M Y') }}
                                @else
                                    {{ $start->format('d M') }} s/d {{ $end->format('d M Y') }}
                                @endif

                                @if ($lr->notes)
                                    <span
                                        style="display: block; font-style: italic; margin-top: 2px; color: var(--text-muted);">
                                        "{{ $lr->notes }}"
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <span class="status-pill {{ $lr->status }}">
                        {{ $statusLabels[$lr->status] ?? ucfirst($lr->status) }}
                    </span>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Camera & GPS Verification Modal -->
    <div id="attendanceCameraModal" class="camera-modal-overlay" style="display: none;">
        <div class="camera-modal-card">
            <div class="camera-modal-header">
                <h3>Verifikasi Kehadiran</h3>
                <button type="button" class="btn-close-camera" onclick="closeCameraModal()">&times;</button>
            </div>

            <div class="camera-preview-container">
                <video id="attendanceVideo" autoplay playsinline muted></video>
                <div id="attendanceMap" style="position: absolute; inset: 0; display: none; z-index: 10;"></div>
                <div id="cameraStatusGlow" class="status-glow"></div>
                <canvas id="attendanceCanvas" style="display: none;"></canvas>

                <div id="cameraLoadingSpinner" class="camera-loading-overlay">
                    <div class="spinner-neon"></div>
                    <p style="margin: 0; font-weight: 600;">Mengunci GPS & Kamera...</p>
                </div>

                <button type="button" id="btnTogglePreview" class="btn-toggle-preview"
                    onclick="togglePreviewMode()" style="display: none;">
                    <svg id="iconMap" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                        <line x1="8" y1="2" x2="8" y2="18"></line>
                        <line x1="16" y1="6" x2="16" y2="22"></line>
                    </svg>
                    <svg id="iconCamera" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" style="display: none;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z">
                        </path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    <span id="textTogglePreview">Lihat Peta</span>
                </button>
            </div>

            <div class="camera-info-pane">
                <div class="status-indicator" id="statusGps">
                    <div class="status-dot warning" id="dotGps"></div>
                    <span id="textGps">Mencari lokasi GPS...</span>
                </div>
                <div class="status-indicator" id="statusCamera">
                    <div class="status-dot warning" id="dotCamera"></div>
                    <span id="textCamera">Menghubungkan ke kamera...</span>
                </div>
                <div id="distanceWarning" class="distance-warning-banner" style="display: none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span id="textWarning">Anda berada di luar radius kantor!</span>
                </div>
            </div>

            <div class="camera-modal-actions">
                <button type="button" id="btnSubmitAttendance" class="btn-submit-attendance" disabled
                    onclick="captureSelfieAndSubmit()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z">
                        </path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    Ambil Foto & Absen
                </button>
                <button type="button" class="btn-cancel-attendance" onclick="closeCameraModal()">Batal</button>
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

        const OFFICE_LAT = @json($employee->tenant->office_latitude ?? null);
        const OFFICE_LNG = @json($employee->tenant->office_longitude ?? null);
        const OFFICE_RADIUS = @json($employee->tenant->office_radius ?? 20);

        function startAttendanceVerification(type) {
            attendanceType = type;
            gpsLocked = false;
            cameraReady = false;
            userLatitude = null;
            userLongitude = null;

            // Reset UI
            document.getElementById('attendanceCameraModal').style.display = 'flex';
            document.getElementById('cameraLoadingSpinner').style.display = 'flex';
            document.getElementById('btnSubmitAttendance').disabled = true;
            document.getElementById('distanceWarning').style.display = 'none';

            const glow = document.getElementById('cameraStatusGlow');
            glow.className = 'status-glow';

            updateStatusIndicator('Gps', 'warning', 'Mencari lokasi GPS...');
            updateStatusIndicator('Camera', 'warning', 'Menghubungkan ke kamera...');

            // Destroy existing map instance to prevent errors on restart
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

            // Start Geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(onGpsSuccess, onGpsError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                updateStatusIndicator('Gps', 'danger', 'GPS tidak didukung oleh browser ini.');
                glow.classList.add('active-error');
            }

            // Start Camera
            const video = document.getElementById('attendanceVideo');
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: {
                            ideal: 640
                        },
                        height: {
                            ideal: 480
                        }
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
                    document.getElementById('cameraLoadingSpinner').style.display = 'none';
                    glow.classList.add('active-error');
                });
            } else {
                updateStatusIndicator('Camera', 'danger', 'Kamera tidak didukung oleh browser ini.');
                document.getElementById('cameraLoadingSpinner').style.display = 'none';
                glow.classList.add('active-error');
            }
        }

        function onGpsSuccess(position) {
            userLatitude = position.coords.latitude;
            userLongitude = position.coords.longitude;
            gpsLocked = true;

            const glow = document.getElementById('cameraStatusGlow');

            // If office coordinates are configured, check radius
            if (OFFICE_LAT !== null && OFFICE_LNG !== null) {
                const distance = calculateHaversineDistance(userLatitude, userLongitude, OFFICE_LAT, OFFICE_LNG);
                const maxRadius = OFFICE_RADIUS || 20;

                if (distance > maxRadius) {
                    updateStatusIndicator('Gps', 'danger', `Di luar radius kantor (${Math.round(distance)} m)`);
                    document.getElementById('textWarning').textContent =
                        `Anda berada di luar radius kantor. Jarak: ${Math.round(distance)} meter. Maksimum: ${maxRadius} meter.`;
                    document.getElementById('distanceWarning').style.display = 'flex';
                    glow.className = 'status-glow active-error';
                    gpsLocked = false; // invalidate GPS lock to prevent submission
                } else {
                    updateStatusIndicator('Gps', 'success', `Lokasi cocok (${Math.round(distance)} m dari kantor)`);
                    document.getElementById('distanceWarning').style.display = 'none';
                    glow.className = 'status-glow active-scanning';
                }
            } else {
                // If office coordinates not configured, warning but allow
                updateStatusIndicator('Gps', 'success', `GPS Terkunci (Kantor belum dikonfigurasi)`);
                document.getElementById('distanceWarning').style.display = 'none';
                glow.className = 'status-glow active-scanning';
            }

            // Show Map toggle button once GPS is locked successfully
            document.getElementById('btnTogglePreview').style.display = 'flex';

            checkVerificationStatus();
        }

        function onGpsError(error) {
            console.error(error);
            const glow = document.getElementById('cameraStatusGlow');
            let msg = 'Gagal mendeteksi lokasi GPS.';
            if (error.code === error.PERMISSION_DENIED) {
                msg = 'Izin lokasi ditolak. Aktifkan lokasi di browser.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                msg = 'Sinyal lokasi tidak tersedia.';
            } else if (error.code === error.TIMEOUT) {
                msg = 'Waktu pencarian lokasi habis.';
            }
            updateStatusIndicator('Gps', 'danger', msg);
            glow.className = 'status-glow active-error';
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

            // Add Office Marker & Radius Circle if coordinates exist
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

            // Add User Marker
            userMarker = L.circleMarker(mapCenter, {
                color: '#7c6af7',
                fillColor: '#7c6af7',
                fillOpacity: 0.8,
                radius: 8
            }).addTo(map).bindPopup('<b>Lokasi Anda</b>');

            // Fit bounds to show both user and office if office exists
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
                // Show Map, Hide Video
                mapEl.style.display = 'block';
                videoEl.style.visibility = 'hidden'; // hide but keep stream active
                iconMap.style.display = 'none';
                iconCamera.style.display = 'inline-block';
                label.textContent = 'Lihat Kamera';

                // Initialize map on show so container sizes are computed correctly
                initMap();
                if (map) {
                    setTimeout(() => map.invalidateSize(), 50);
                }
            } else {
                // Show Video, Hide Map
                mapEl.style.display = 'none';
                videoEl.style.visibility = 'visible';
                iconMap.style.display = 'inline-block';
                iconCamera.style.display = 'none';
                label.textContent = 'Lihat Peta';
            }
        }

        function checkVerificationStatus() {
            if (cameraReady) {
                document.getElementById('cameraLoadingSpinner').style.display = 'none';
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

            dot.className = 'status-dot ' + status;
            span.textContent = text;
        }

        function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function closeCameraModal() {
            // Stop camera track
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            // Destroy existing map instance to prevent errors
            if (map) {
                map.remove();
                map = null;
            }
            document.getElementById('attendanceCameraModal').style.display = 'none';
        }

        function captureSelfieAndSubmit() {
            const video = document.getElementById('attendanceVideo');
            const canvas = document.getElementById('attendanceCanvas');
            const context = canvas.getContext('2d');

            // Disable button and change state
            const btn = document.getElementById('btnSubmitAttendance');
            btn.disabled = true;
            btn.innerHTML =
                '<div class="spinner-neon" style="width:16px; height:16px; border-width:2px; margin-right:8px; display:inline-block; vertical-align:middle;"></div>Memproses Absensi...';

            // Set canvas size matching the video aspect ratio
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            // Draw current video frame to canvas
            context.translate(canvas.width, 0);
            context.scale(-1, 1); // mirror horizontal draw
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Get Base64 image
            const photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);

            // Set value to forms
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

            // Stop camera stream immediately
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            // Destroy map instance
            if (map) {
                map.remove();
                map = null;
            }
        }
    </script>
</div>
