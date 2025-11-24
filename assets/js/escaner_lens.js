// escaner_lens.js
// Captura un frame del elemento video dentro de #qr-reader y lo envía al backend para analizar con Google Vision

document.addEventListener('DOMContentLoaded', () => {
    const btnAnalyze = document.getElementById('btn-analyze-lens');
    const reader = document.getElementById('qr-reader');
    const resultDiv = document.getElementById('qr-info');

    function showResult(html) {
        if (resultDiv) resultDiv.innerHTML = html;
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Rellena campos comunes si existen en la página y emite evento 'cedulaDecoded'
    function fillFields({ rut, dv, birth }) {
        try {
            // Buscar inputs típicos por id o name
            if (rut) {
                const selectors = ['#rut', 'input[name="rut"]', '#run', 'input[name="run"]'];
                for (const sel of selectors) {
                    const el = document.querySelector(sel);
                    if (el) { el.value = rut + (dv ? ('-' + dv) : ''); break; }
                }
            }
            if (birth) {
                const selectors = ['#fecha_nacimiento', 'input[name="fecha_nacimiento"]', '#birth', 'input[name="birth"]'];
                for (const sel of selectors) {
                    const el = document.querySelector(sel);
                    if (el) { el.value = birth; break; }
                }
            }

            // Emitir evento personalizado con detalle
            const evt = new CustomEvent('cedulaDecoded', { detail: { rut: rut, dv: dv, birth: birth } });
            document.dispatchEvent(evt);
        } catch (e) {
            console.warn('fillFields error', e);
        }
    }

    // Habilitar botón cuando haya video. Usamos MutationObserver + polling como fallback.
    try {
        const observer = new MutationObserver(() => {
            const video = reader ? reader.querySelector('video') : null;
            if (btnAnalyze) {
                btnAnalyze.disabled = !video;
            }
        });
        if (reader) observer.observe(reader, { childList: true, subtree: true });
    } catch (e) {
        console.warn('MutationObserver no disponible:', e);
    }

    // Polling fallback
    const pollInterval = setInterval(() => {
        const video = reader ? reader.querySelector('video') : null;
        if (btnAnalyze) btnAnalyze.disabled = !video;
        if (video) clearInterval(pollInterval);
    }, 500);

    // Calcula DV de RUT chileno
    function calcularDvRut(rutSinDv) {
        const s = String(rutSinDv).replace(/\D/g, '');
        let suma = 0;
        let factor = 2;
        for (let i = s.length - 1; i >= 0; i--) {
            suma += Number(s[i]) * factor;
            factor = (factor === 7) ? 2 : factor + 1;
        }
        const resto = suma % 11;
        const dv = 11 - resto;
        if (dv === 11) return '0';
        if (dv === 10) return 'K';
        return String(dv);
    }

    // Dibuja el frame actual del video a un canvas y devuelve dataURL
    function captureFrameFromVideo(video) {
        const w = video.videoWidth || 640;
        const h = video.videoHeight || 480;
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        return canvas.toDataURL('image/jpeg', 0.9);
    }

    // Preprocesado: devuelve un canvas con grayscale y contraste opcional
    function preprocessCanvas(imageDataUrl, options = { contrast: 0, scale: 1, rotate: 0 }) {
        const img = new Image();
        img.src = imageDataUrl;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        // esperamos a que cargue: pero la función será usada con img.decode previo en analyze
        const w = img.naturalWidth || 640;
        const h = img.naturalHeight || 480;
        canvas.width = Math.round(w * options.scale);
        canvas.height = Math.round(h * options.scale);

        if (options.rotate && options.rotate % 360 !== 0) {
            // rotación simple 90/180/270
            ctx.save();
            const angle = (options.rotate % 360) * Math.PI / 180;
            ctx.translate(canvas.width/2, canvas.height/2);
            ctx.rotate(angle);
            ctx.drawImage(img, -canvas.width/2, -canvas.height/2, canvas.width, canvas.height);
            ctx.restore();
        } else {
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        }

        // Grayscale + contraste simple
        const id = ctx.getImageData(0,0,canvas.width, canvas.height);
        const data = id.data;
        const contrast = options.contrast || 0; // -1..1
        const factor = (259 * (contrast * 255 + 255)) / (255 * (259 - contrast * 255));
        for (let i=0;i<data.length;i+=4) {
            const r = data[i], g = data[i+1], b = data[i+2];
            // grayscale
            let v = 0.299*r + 0.587*g + 0.114*b;
            // contraste
            if (contrast !== 0) v = factor * (v - 128) + 128;
            data[i] = data[i+1] = data[i+2] = v;
        }
        ctx.putImageData(id, 0, 0);
        return canvas;
    }

    // Analizar: captura frame, intenta decodificar con variantes y fallback a servidor
    async function analyze() {
        btnAnalyze.disabled = true;
        showResult('<div class="alert alert-info">Analizando imagen...</div>');

        let tempStream = null;
        let video = reader ? reader.querySelector('video') : null;

        try {
            if (!video) {
                try {
                    tempStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                    video = document.createElement('video');
                    video.autoplay = true;
                    video.playsInline = true;
                    video.srcObject = tempStream;
                    await new Promise((res) => {
                        const to = setTimeout(() => res(), 1500);
                        video.onloadedmetadata = () => { clearTimeout(to); res(); };
                    });
                } catch (e) {
                    console.error('No se pudo acceder a la cámara:', e);
                    showResult('<div class="alert alert-danger">No se pudo acceder a la cámara: ' + escapeHtml(e.message || e) + '</div>');
                    btnAnalyze.disabled = false;
                    return;
                }
            }

            const dataUrl = captureFrameFromVideo(video);

            // preparar variantes
            const variants = [{ canvas: null, dataUrl: dataUrl, desc: 'original' }];
            const tmpImg = new Image(); tmpImg.src = dataUrl; await tmpImg.decode().catch(()=>{});
            try {
                variants.push({ canvas: preprocessCanvas(dataUrl, { contrast: 0.3, scale: 1 }), desc: 'contrast' });
                variants.push({ canvas: preprocessCanvas(dataUrl, { contrast: 0.5, scale: 0.85 }), desc: 'contrast-small' });
                variants.push({ canvas: preprocessCanvas(dataUrl, { contrast: 0, scale: 1.4 }), desc: 'scale-up' });
            } catch (e) { console.warn('preprocess error', e); }

            // Intentar jsQR primero (si está disponible)
            try {
                if (typeof jsQR !== 'undefined') {
                    for (const v of variants) {
                        try {
                            let imgData;
                            if (v.canvas) imgData = v.canvas.getContext('2d').getImageData(0,0,v.canvas.width,v.canvas.height);
                            else {
                                const c = document.createElement('canvas'); const ctx = c.getContext('2d'); const img = new Image(); img.src = v.dataUrl; await img.decode().catch(()=>{});
                                c.width = img.naturalWidth; c.height = img.naturalHeight; ctx.drawImage(img,0,0);
                                imgData = ctx.getImageData(0,0,c.width,c.height);
                            }
                            const qrRes = jsQR(imgData.data, imgData.width, imgData.height);
                            if (qrRes && qrRes.data) {
                                const decoded = qrRes.data;
                                console.log('jsQR decoded:', decoded);
                                // intentar extraer y completar
                                const numMatch = decoded.match(/(\d{7,8})/g) || [];
                                const dateMatches = decoded.match(/(\d{6})/g) || [];
                                const docNum = numMatch.length? (numMatch.find(n=>n.length>=7)||numMatch[0]) : null;
                                let birth = null; const today = new Date(); const pivot = today.getFullYear()%100;
                                for (const d of dateMatches) { const yy=parseInt(d.slice(0,2),10); const mm=parseInt(d.slice(2,4),10); const dd=parseInt(d.slice(4,6),10); if (mm>=1&&mm<=12&&dd>=1&&dd<=31){ const year=(yy>pivot)?1900+yy:2000+yy; birth=`${year}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`; break; } }
                                if (docNum || birth) { try{ fillFields({ rut: docNum, dv: docNum?calcularDvRut(docNum):null, birth: birth }); }catch(e){}; showResult('<div class="alert alert-success"><b>Decodificado localmente (jsQR)</b></div>'); if (tempStream) try{tempStream.getTracks().forEach(t=>t.stop());}catch(e){}; btnAnalyze.disabled=false; return; }
                            }
                        } catch(e) { /* seguir con siguiente variante */ }
                    }
                }
            } catch (e) { console.warn('jsQR multi-variant error', e); }

            // Intentar ZXing en variantes
            try {
                if (typeof ZXing !== 'undefined' && ZXing.BrowserMultiFormatReader) {
                    const { BrowserMultiFormatReader, BarcodeFormat, DecodeHintType } = ZXing;
                    const hints = new Map();
                    hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.PDF_417, BarcodeFormat.QR_CODE]);
                    const readerZX = new BrowserMultiFormatReader(hints);
                    for (const v of variants) {
                        try {
                            let zxResult = null;
                            if (v.canvas) zxResult = await readerZX.decodeFromCanvas(v.canvas);
                            else { const img = new Image(); img.src = v.dataUrl; await img.decode().catch(()=>{}); zxResult = await readerZX.decodeFromImage(img); }
                            if (zxResult && zxResult.text) {
                                const decoded = zxResult.text;
                                console.log('ZXing decoded:', decoded);
                                const numMatch = decoded.match(/(\d{7,8})/g) || [];
                                const dateMatches = decoded.match(/(\d{6})/g) || [];
                                const docNum = numMatch.length? (numMatch.find(n=>n.length>=7)||numMatch[0]) : null;
                                let birth = null; const today = new Date(); const pivot = today.getFullYear()%100;
                                for (const d of dateMatches) { const yy=parseInt(d.slice(0,2),10); const mm=parseInt(d.slice(2,4),10); const dd=parseInt(d.slice(4,6),10); if (mm>=1&&mm<=12&&dd>=1&&dd<=31){ const year=(yy>pivot)?1900+yy:2000+yy; birth=`${year}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`; break; } }
                                if (docNum || birth) { try{ fillFields({ rut: docNum, dv: docNum?calcularDvRut(docNum):null, birth: birth }); }catch(e){}; showResult('<div class="alert alert-success"><b>Decodificado localmente (ZXing)</b></div>'); if (tempStream) try{tempStream.getTracks().forEach(t=>t.stop());}catch(e){}; btnAnalyze.disabled=false; return; }
                            }
                        } catch (e) { /* sigue con siguiente variante */ }
                    }
                }
            } catch (e) { console.warn('ZXing multi-variant error', e); }

            // Fallback: enviar al controlador de Vision
            try {
                const decodedPath = decodeURIComponent(window.location.pathname);
                const controllerPath = decodedPath.replace(/\/vista\/.*$/i, '/controlador/ProcesarVisionController.php');
                const controllerUrl = window.location.origin + controllerPath;
                const resp = await fetch(controllerUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: dataUrl })
                });
                if (!resp.ok) { const text = await resp.text(); showResult('<div class="alert alert-danger">Error del servidor: ' + resp.status + ' ' + escapeHtml(text) + '</div>'); return; }
                const json = await resp.json();
                let html = '<h5>Resultados de Vision API</h5>';
                const annotations = json.responses && json.responses[0] ? json.responses[0] : {};
                if (annotations.labelAnnotations) { html += '<h6>Etiquetas</h6><ul>'; annotations.labelAnnotations.forEach(l => { html += `<li>${escapeHtml(l.description)} (score: ${Number(l.score).toFixed(2)})</li>`; }); html += '</ul>'; }
                if (annotations.textAnnotations) { html += '<h6>Texto detectado</h6><pre>' + escapeHtml(annotations.textAnnotations[0].description || '') + '</pre>'; }
                if (annotations.webDetection && annotations.webDetection.webEntities) { html += '<h6>Web entities</h6><ul>'; annotations.webDetection.webDetection && annotations.webDetection.webEntities && annotations.webDetection.webEntities.forEach && annotations.webDetection.webEntities.forEach(w => { html += `<li>${escapeHtml(w.description || 'sin descripcion')} (score: ${Number(w.score || 0).toFixed(2)})</li>`; }); html += '</ul>'; }
                if (!annotations.labelAnnotations && !annotations.textAnnotations && !(annotations.webDetection && annotations.webDetection.webEntities)) { html += '<div class="alert alert-secondary">No se detectó información relevante.</div>'; }
                showResult(html);
                // intentar parsear texto devuelto por Vision para autocompletar
                try {
                    const text = (annotations.textAnnotations && annotations.textAnnotations[0] && annotations.textAnnotations[0].description) ? annotations.textAnnotations[0].description : '';
                    if (text) {
                        const numMatch = text.match(/(\d{7,8})/g) || [];
                        const dateMatches = text.match(/(\d{6})/g) || [];
                        const docNum = numMatch.length? (numMatch.find(n=>n.length>=7)||numMatch[0]) : null;
                        let birth = null; const today = new Date(); const pivot = today.getFullYear()%100;
                        for (const d of dateMatches) { const yy=parseInt(d.slice(0,2),10); const mm=parseInt(d.slice(2,4),10); const dd=parseInt(d.slice(4,6),10); if (mm>=1&&mm<=12&&dd>=1&&dd<=31){ const year=(yy>pivot)?1900+yy:2000+yy; birth=`${year}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`; break; } }
                        if (docNum || birth) { try{ fillFields({ rut: docNum, dv: docNum?calcularDvRut(docNum):null, birth: birth }); }catch(e){} }
                    }
                } catch(e) { console.warn('post-vision parse error', e); }
                return;
            } catch (err) {
                console.error('Error al enviar la imagen:', err);
                showResult('<div class="alert alert-danger">Error al enviar la imagen: ' + escapeHtml(err.message || err) + '</div>');
                return;
            }

        } catch (err) {
            console.error('Error en analyze():', err);
            showResult('<div class="alert alert-danger">Error al analizar: ' + escapeHtml(err.message || err) + '</div>');
        } finally {
            if (tempStream) { try { tempStream.getTracks().forEach(t => t.stop()); } catch (e) {} }
            btnAnalyze.disabled = false;
        }
    }

    if (btnAnalyze) btnAnalyze.addEventListener('click', analyze);
});
