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
    <div id="loading">
        <div class="spinner"></div>
        <p style="margin-top:18px; font-size:16px; font-weight:600;">Menggabungkan {{ count($pdfBase64List) }} resi pesanan...</p>
        <small style="color:#94a3b8; margin-top:6px; font-size:13px;">Sedang memproses seluruh halaman label marketplace</small>
    </div>

    <iframe id="viewer" src="about:blank"></iframe>

    <script>
        const pdfBase64List = @json($pdfBase64List);

        function base64ToUint8Array(base64) {
            const binaryString = window.atob(base64);
            const len = binaryString.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return bytes;
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
                const blobUrl = URL.createObjectURL(blob);

                const viewer = document.getElementById('viewer');
                viewer.src = blobUrl;
                viewer.style.display = 'block';

                const loading = document.getElementById('loading');
                if (loading) loading.style.display = 'none';
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
