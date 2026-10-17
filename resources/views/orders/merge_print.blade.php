<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi Massal ({{ count($pdfBase64List) }} Dokumen)</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { width: 100%; height: 100%; overflow: hidden; background: #525659; font-family: system-ui, -apple-system, sans-serif; }
        #loading {
            position: fixed; inset: 0; background: #0f172a; color: #fff;
            display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999;
        }
        .spinner {
            width: 52px; height: 52px; border: 4px solid rgba(255,255,255,0.15);
            border-top-color: #38bdf8; border-radius: 50%; animation: spin 0.75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        #viewer { width: 100%; height: 100%; border: none; display: none; }
        #topbar {
            position: fixed; top: 0; left: 0; right: 0; height: 48px; background: #0f172a;
            color: #f8fafc; display: none; align-items: center; justify-content: space-between;
            padding: 0 18px; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.35);
        }
        .tb-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;
            border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;
            cursor: pointer; border: none; transition: all 0.15s ease;
        }
        .tb-btn-primary { background: #2563eb; color: #fff; }
        .tb-btn-primary:hover { background: #1d4ed8; }
        .tb-btn-ghost { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        .tb-btn-ghost:hover { background: rgba(255,255,255,0.2); }
    </style>
    <!-- pdf-lib: Local bundle first with CDN fallbacks -->
    <script src="{{ asset('pdf-lib.min.js') }}"></script>
    <script>
        if (!window.PDFLib) {
            document.write('<script src="https://cdn.jsdelivr.net/npm/pdf-lib/dist/pdf-lib.min.js"><\/script>');
        }
    </script>
</head>
<body>
    <div id="topbar">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:16px;">📄</span>
            <span style="font-weight:700; font-size:14px;">Resi Massal Resmi Marketplace</span>
            <span style="background:#334155; color:#38bdf8; font-size:11.5px; padding:2px 8px; border-radius:12px; font-weight:600;">
                {{ count($pdfBase64List) }} Pesanan
            </span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button class="tb-btn tb-btn-primary" onclick="printPdf()">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak Semua Resi
            </button>
            <a id="btn-download" class="tb-btn tb-btn-ghost" href="#" download="resi_massal_resmi_{{ date('Ymd_His') }}.pdf">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Download PDF
            </a>
            <button class="tb-btn tb-btn-ghost" onclick="window.close()" title="Tutup Tab">✕</button>
        </div>
    </div>

    <div id="loading">
        <div class="spinner"></div>
        <p style="margin-top:18px; font-size:16px; font-weight:600;">Menggabungkan {{ count($pdfBase64List) }} resi pesanan...</p>
        <small style="color:#94a3b8; margin-top:6px; font-size:13px;">Sedang memproses seluruh halaman label marketplace</small>
    </div>

    <iframe id="viewer" src="about:blank"></iframe>

    <script>
        const pdfBase64List = @json($pdfBase64List);
        let activeBlobUrl = null;

        function base64ToUint8Array(base64) {
            const binaryString = window.atob(base64);
            const len = binaryString.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return bytes;
        }

        function printPdf() {
            const viewer = document.getElementById('viewer');
            try {
                if (viewer && viewer.contentWindow) {
                    viewer.contentWindow.focus();
                    viewer.contentWindow.print();
                    return;
                }
            } catch (e) {
                console.warn("Direct iframe print failed:", e);
            }
            if (activeBlobUrl) {
                window.open(activeBlobUrl, '_blank');
            }
        }

        async function mergeAndDisplayPdfs() {
            try {
                if (!window.PDFLib) {
                    throw new Error("Library PDF-Lib tidak dapat dimuat. Periksa koneksi internet.");
                }

                const mergedPdf = await PDFLib.PDFDocument.create();
                let pageCountTotal = 0;

                for (let i = 0; i < pdfBase64List.length; i++) {
                    const b64 = pdfBase64List[i];
                    if (!b64) continue;
                    try {
                        const pdfBytes = base64ToUint8Array(b64);
                        const pdfDoc = await PDFLib.PDFDocument.load(pdfBytes, { ignoreEncryption: true });
                        const copiedPages = await mergedPdf.copyPages(pdfDoc, pdfDoc.getPageIndices());
                        copiedPages.forEach((page) => {
                            mergedPdf.addPage(page);
                            pageCountTotal++;
                        });
                    } catch (itemErr) {
                        console.warn("Gagal memuat dokumen PDF indeks " + i + ":", itemErr);
                    }
                }

                if (pageCountTotal === 0) {
                    throw new Error("Tidak ada halaman dokumen PDF yang valid untuk digabungkan.");
                }

                const mergedBytes = await mergedPdf.save();
                const blob = new Blob([mergedBytes], { type: 'application/pdf' });
                activeBlobUrl = URL.createObjectURL(blob);

                const dlBtn = document.getElementById('btn-download');
                if (dlBtn) dlBtn.href = activeBlobUrl;

                const topbar = document.getElementById('topbar');
                if (topbar) topbar.style.display = 'flex';

                const viewer = document.getElementById('viewer');
                viewer.src = activeBlobUrl;
                viewer.style.display = 'block';
                viewer.style.height = 'calc(100% - 48px)';
                viewer.style.marginTop = '48px';

                const loading = document.getElementById('loading');
                if (loading) loading.style.display = 'none';

                // Auto trigger print setelah render
                setTimeout(() => {
                    try {
                        if (viewer.contentWindow) {
                            viewer.contentWindow.focus();
                        }
                    } catch (e) {}
                }, 500);
            } catch (err) {
                console.error("Gagal menggabungkan PDF:", err);
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.innerHTML = `
                        <div style="background:#ef4444; color:#fff; padding:25px 35px; border-radius:12px; max-width:540px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.3);">
                            <h3 style="font-size:18px; margin-bottom:10px;">⚠️ Gagal Menggabungkan Resi</h3>
                            <p style="font-size:13.5px; opacity:0.95; line-height:1.5;">${err.message || 'Terjadi kesalahan saat memproses file PDF.'}</p>
                            <button onclick="window.close()" style="margin-top:18px; padding:8px 20px; background:#fff; color:#ef4444; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:13px;">Tutup Tab</button>
                        </div>
                    `;
                }
            }
        }

        async function initMerge() {
            // Beri waktu sejenak jika script fallback sedang loading
            let retries = 0;
            while (!window.PDFLib && retries < 20) {
                await new Promise(r => setTimeout(r, 150));
                retries++;
            }
            await mergeAndDisplayPdfs();
        }

        window.addEventListener('load', initMerge);
    </script>
</body>
</html>
