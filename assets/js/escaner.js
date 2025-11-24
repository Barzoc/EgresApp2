// Variables globales
let html5QrCode = null;
let activeCamera = null;

// Configuración del escáner
const config = {
    fps: 10,
    qrbox: { width: 250, height: 250 },
    aspectRatio: 1.0,
    experimentalFeatures: {
        useBarCodeDetectorIfSupported: true
    }
};

// Elementos del DOM
const elements = {
    result: null,
    select: null,
    btnStart: null,
    btnPerm: null,
    btnRestart: null,
    container: null
};

window.addEventListener('DOMContentLoaded', function() {
    // Inicializar referencias a elementos del DOM
    elements.result = document.getElementById('qr-result');
    elements.select = document.getElementById('camera-select');
    elements.btnStart = document.getElementById('btn-switch-camera');
    elements.btnPerm = document.getElementById('btn-request-perm');
    elements.btnRestart = document.getElementById('btn-restart-camera');
    elements.container = document.getElementById('qr-reader');

    // Función para mostrar mensajes
    function showMessage(message) {
        if (elements.result) {
            elements.result.innerText = message;
        }
    }

    // Función para listar cámaras
    async function listCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');
            
            // Limpiar select
            elements.select.innerHTML = '<option value="">Seleccione cámara</option>';
            
            cameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Cámara ${index + 1}`;
                elements.select.appendChild(option);
            });

            if (cameras.length > 0) {
                elements.select.selectedIndex = 1; // Seleccionar primera cámara
                elements.btnStart.disabled = false;
            }
            
            return cameras.length > 0;
        } catch (error) {
            showMessage('Error al listar cámaras: ' + error.message);
            return false;
        }
    }

    // Función para detener el escáner
    async function stopScanner() {
        if (html5QrCode) {
            try {
                await html5QrCode.stop();
                await html5QrCode.clear();
                html5QrCode = null;
            } catch (error) {
                console.error('Error al detener escáner:', error);
            }
        }
    }

    // Función para iniciar el escáner
    async function startScanner(cameraId) {
        try {
            await stopScanner();
            
            html5QrCode = new Html5Qrcode('qr-reader');
            
            const constraints = {
                ...config,
                videoConstraints: {
                    deviceId: cameraId,
                    width: { ideal: 1920, min: 1280 },
                    height: { ideal: 1080, min: 720 },
                    focusMode: ['continuous', 'auto'],
                    exposureMode: ['continuous', 'auto'],
                    whiteBalanceMode: ['continuous', 'auto']
                }
            };
            
            await html5QrCode.start(
                cameraId,
                constraints,
                (text) => {
                    showMessage('Código detectado: ' + text);
                    // Aquí podrías agregar un efecto visual o sonido de éxito
                    const scanRegion = document.querySelector('.scan-region');
                    if (scanRegion) {
                        scanRegion.style.borderColor = '#2ecc71';
                        scanRegion.style.boxShadow = '0 0 20px rgba(46,204,113,0.4)';
                        setTimeout(() => {
                            scanRegion.style.borderColor = 'rgba(255,255,255,0.8)';
                            scanRegion.style.boxShadow = 'none';
                        }, 1000);
                    }
                },
                (error) => {
                    // Ignoramos errores menores de escaneo
                }
            );
            
            showMessage('Cámara activada. Apunte al código QR.');
        } catch (error) {
            showMessage('Error al iniciar cámara: ' + error.message);
            console.error('Error completo:', error);
        }
    }

    // Event Listeners
    elements.btnPerm.addEventListener('click', async () => {
        elements.btnPerm.disabled = true;
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
            const hasCameras = await listCameras();
            if (hasCameras) {
                showMessage('Permisos concedidos. Seleccione una cámara y presione "Iniciar cámara".');
            } else {
                showMessage('No se detectaron cámaras en el dispositivo.');
            }
        } catch (error) {
            showMessage('Error al solicitar permisos: ' + error.message);
        } finally {
            elements.btnPerm.disabled = false;
        }
    });

    elements.btnStart.addEventListener('click', async () => {
        if (!elements.select.value) {
            showMessage('Por favor, seleccione una cámara primero.');
            return;
        }

        elements.btnStart.disabled = true;
        elements.btnRestart.disabled = true;
        
        try {
            showMessage('Iniciando cámara...');
            await startScanner(elements.select.value);
        } catch (error) {
            showMessage('Error al iniciar cámara: ' + error.message);
        } finally {
            elements.btnStart.disabled = false;
            elements.btnRestart.disabled = false;
        }
    });

    elements.btnRestart.addEventListener('click', async () => {
        if (!elements.select.value) {
            showMessage('Por favor, seleccione una cámara primero.');
            return;
        }

        elements.btnRestart.disabled = true;
        elements.btnStart.disabled = true;
        
        try {
            showMessage('Reiniciando cámara...');
            await startScanner(elements.select.value);
        } catch (error) {
            showMessage('Error al reiniciar cámara: ' + error.message);
        } finally {
            elements.btnRestart.disabled = false;
            elements.btnStart.disabled = false;
        }
    });

    // Limpiar al cerrar
    window.addEventListener('beforeunload', () => {
        stopScanner();
    });
});
// Requiere incluir: https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js

window.addEventListener('DOMContentLoaded', async () => {
    const resultEl = document.getElementById('qr-result');
    const readerId = 'qr-reader';
    const btnRequestPerm = document.getElementById('btn-request-perm');
    const btnSwitch = document.getElementById('btn-switch-camera');
    const btnRestart = document.getElementById('btn-restart-camera');
    const select = document.getElementById('camera-select');

    // Comprobar elementos necesarios
    if (!document.getElementById(readerId) || !select || !btnRequestPerm || !btnSwitch) {
        if (resultEl) resultEl.innerText = 'Error: Faltan elementos en la página.';
        return;
    }

    // Variable global para la instancia activa del scanner
    let activeScanner = null;

    // Función para actualizar la lista de cámaras
    async function updateCameraList() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');
            
            // Limpiar select
            select.innerHTML = '<option value="">Seleccione cámara</option>';
            
            // Añadir cámaras
            cameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Cámara ${index + 1}`;
                select.appendChild(option);
            });

            return cameras.length > 0;
        } catch (error) {
            console.error('Error al enumerar cámaras:', error);
            if (resultEl) resultEl.innerText = 'Error al listar cámaras: ' + error.message;
            return false;
        }
    }

    // Función para detener el scanner activo
    async function stopScanner() {
        if (activeScanner) {
            try {
                await activeScanner.stop();
                await activeScanner.clear();
                activeScanner = null;
            } catch (error) {
                console.warn('Error al detener scanner:', error);
            }
        }
        
        // Limpiar el contenedor del scanner
        const container = document.getElementById(readerId);
        if (container) {
            container.innerHTML = '';
        }
    }

    // Función para iniciar el scanner
    async function startScanner(deviceId) {
        if (!deviceId) {
            if (resultEl) resultEl.innerText = 'Por favor seleccione una cámara.';
            return;
        }

        try {
            await stopScanner();

            const html5QrcodeScanner = new Html5Qrcode(readerId);
            
            const config = {
                fps: 15,
                qrbox: { width: 300, height: 300 },
                aspectRatio: 1.0
            };

            await html5QrcodeScanner.start(
                deviceId,
                {
                    ...config,
                    videoConstraints: {
                        deviceId: deviceId,
                        width: { ideal: 1920, min: 1280 },
                        height: { ideal: 1080, min: 720 }
                    }
                },
                (decodedText, decodedResult) => {
                    if (resultEl) resultEl.innerText = 'Código detectado: ' + decodedText;
                    // Aquí puedes procesar el resultado como necesites
                },
                (errorMessage) => {
                    // console.log(errorMessage); // Comentado para evitar spam en la consola
                }
            );

            activeScanner = html5QrcodeScanner;
            if (resultEl) resultEl.innerText = 'Cámara activada. Apunte al código QR.';

        } catch (error) {
            console.error('Error al iniciar scanner:', error);
            if (resultEl) resultEl.innerText = 'Error al iniciar cámara: ' + error.message;
        }
    }

    // Manejar cambio de cámara
    btnSwitch.addEventListener('click', async () => {
        const selectedDeviceId = select.value;
        if (!selectedDeviceId) {
            if (resultEl) resultEl.innerText = 'Por favor seleccione una cámara primero.';
            return;
        }

        btnSwitch.disabled = true;
        try {
            await startScanner(selectedDeviceId);
        } finally {
            btnSwitch.disabled = false;
        }
    });

    // Manejar reinicio de cámara
    if (btnRestart) {
        btnRestart.addEventListener('click', async () => {
            const selectedDeviceId = select.value;
            if (!selectedDeviceId) {
                if (resultEl) resultEl.innerText = 'Por favor seleccione una cámara primero.';
                return;
            }

            btnRestart.disabled = true;
            try {
                await stopScanner();
                await startScanner(selectedDeviceId);
            } finally {
                btnRestart.disabled = false;
            }
        });
    }

    // Manejar clic en botón de permisos
    btnRequestPerm.addEventListener('click', async () => {
        try {
            // Solicitar permisos de cámara
            await navigator.mediaDevices.getUserMedia({ video: true });
            
            // Actualizar lista de cámaras
            const hasCameras = await updateCameraList();
            
            if (hasCameras) {
                if (resultEl) resultEl.innerText = 'Permiso concedido. Seleccione una cámara y presione "Cambiar cámara".';
                // Seleccionar primera cámara por defecto
                if (select.options.length > 1) {
                    select.selectedIndex = 1;
                }
            } else {
                if (resultEl) resultEl.innerText = 'No se detectaron cámaras.';
            }
        } catch (error) {
            console.error('Error al solicitar permisos:', error);
            if (resultEl) resultEl.innerText = 'Error al solicitar permisos: ' + error.message;
        }
    });

    // Opciones del escáner con mejor calidad
    const config = { 
        fps: 15,
        qrbox: { width: 300, height: 300 },
        aspectRatio: 1.0,
        formatsToSupport: ['QR_CODE'],
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        }
    };

    // Intentar obtener las cámaras disponibles y arrancar la cámara preferida (trasera si existe)
    const cameraSelect = document.getElementById('camera-select');
    const cameraOptionsEl = cameraSelect ? cameraSelect.querySelector('.options') : null;
    const cameraSelectedEl = cameraSelect ? cameraSelect.querySelector('.selected') : null;
    const btnSwitch = document.getElementById('btn-switch-camera');
    const btnRestart = document.getElementById('btn-restart-camera');
    const btnRequestPerm = document.getElementById('btn-request-perm');

    // Poll para esperar html5-qrcode (5s) y luego iniciar flujo; si no existe, usar enumerateDevices como fallback para poblar select
    const waitForHtml5 = (timeout = 5000) => new Promise((resolve) => {
        const start = Date.now();
        const check = () => {
            if (window.Html5Qrcode && Html5Qrcode.getCameras) return resolve(true);
            if (Date.now() - start > timeout) return resolve(false);
            setTimeout(check, 200);
        };
        check();
    });

    const populateFromEnumerate = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return [];
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cams = devices.filter(d => d.kind === 'videoinput');
            if (cameraSelect) {
                // si usamos custom selector, poblar opciones
                if (cameraOptionsEl) {
                    cameraOptionsEl.innerHTML = '';
                    cams.forEach((c, idx) => {
                        const id = c.deviceId || c.id || idx;
                        const div = document.createElement('div');
                        div.className = 'camera-option';
                        div.dataset.id = id;
                        div.style.padding = '6px 8px';
                        div.style.cursor = 'pointer';
                        div.innerText = c.label || `Cámara ${idx+1}`;
                        div.addEventListener('click', () => {
                            if (cameraSelectedEl) {
                                cameraSelectedEl.innerText = div.innerText;
                                cameraSelectedEl.dataset.id = id;
                            }
                        });
                        cameraOptionsEl.appendChild(div);
                    });
                }
            }
            return cams;
        } catch (e) {
            console.warn('No se pudo enumerar dispositivos:', e);
            return [];
        }
    };

    // Funciones de cámara nativa disponibles globalmente dentro de este scope
    let nativeStream = null;
    async function stopNativeCamera() {
        try {
            if (nativeStream) {
                nativeStream.getTracks().forEach(t => t.stop());
                nativeStream = null;
            }
            // Limpiar todos los elementos de video
            const readerEl = document.getElementById(readerId);
            if (readerEl) {
                const videos = readerEl.querySelectorAll('video');
                videos.forEach(video => {
                    if (video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                    }
                    video.remove();
                });
                // Limpiar completamente el contenedor
                readerEl.innerHTML = '';
            }
            stopProcessingLoop();
        } catch (e) {
            console.warn('Error parando la cámara nativa', e);
        }
    }

    async function startNativeCamera(deviceId) {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) throw new Error('getUserMedia no soportado');
            await stopNativeCamera();
            const constraints = { video: { deviceId: deviceId ? { exact: deviceId } : undefined, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false };
            nativeStream = await navigator.mediaDevices.getUserMedia(constraints);
            const readerEl = document.getElementById(readerId);
            let video = readerEl.querySelector('#native-video');
            if (!video) {
                video = document.createElement('video');
                video.id = 'native-video';
                video.autoplay = true;
                video.playsInline = true;
                video.style.width = '100%';
                readerEl.appendChild(video);
            }
            video.srcObject = nativeStream;
            if (resultEl) resultEl.innerText = 'Cámara nativa iniciada.';
            // arrancar loop de preprocesado
            startProcessingLoop();
        } catch (err) {
            console.error('Error al iniciar cámara nativa:', err);
            if (resultEl) resultEl.innerText = 'No se pudo iniciar la cámara nativa: ' + err.message;
        }
    }

    // Intentar iniciar usando la librería si está disponible, o nativo si no
    async function cleanupCamera() {
        // Detener y limpiar HTML5QRCode si existe
        if (window._activeHtml5Qr) {
            try {
                await window._activeHtml5Qr.stop();
                await window._activeHtml5Qr.clear();
                window._activeHtml5Qr = null;
            } catch (e) {
                console.warn('Error limpiando HTML5QRCode:', e);
            }
        }

        // Detener todas las transmisiones de video
        const readerEl = document.getElementById(readerId);
        if (readerEl) {
            const videos = readerEl.getElementsByTagName('video');
            Array.from(videos).forEach(video => {
                try {
                    if (video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                    }
                    video.remove();
                } catch (e) {
                    console.warn('Error limpiando video:', e);
                }
            });
            
            // Limpiar completamente el contenedor
            readerEl.innerHTML = '';
        }

        // Detener el loop de procesamiento
        stopProcessingLoop();

        // Pequeña pausa para asegurar la limpieza
        await new Promise(resolve => setTimeout(resolve, 200));
    }

    async function attemptStart(deviceId) {
        if (!deviceId) {
            console.error('No se proporcionó ID de dispositivo');
            return;
        }

        try {
            // Limpiar cámara anterior
            await cleanupCamera();

            if (window.Html5Qrcode) {
                // Crear nueva instancia
                const html5Qr = new Html5Qrcode(readerId);
                const configWithConstraints = Object.assign({}, config, { videoConstraints: { width: { ideal: 1280 }, height: { ideal: 720 } } });
                html5Qr.start(deviceId, configWithConstraints, onScanSuccess, onScanFailure)
                .then(()=> { window._activeHtml5Qr = html5Qr; startProcessingLoop(); })
                .catch(err => {
                    console.warn('Fallo al iniciar con html5-qrcode, intentando sin constraints', err);
                    html5Qr.start(deviceId, config, onScanSuccess, onScanFailure).then(()=>{ window._activeHtml5Qr = html5Qr; startProcessingLoop(); }).catch(e=>{ console.error(e); startNativeCamera(deviceId); });
                });
            } catch (e) {
                console.warn('Error iniciando html5-qrcode:', e);
                startNativeCamera(deviceId);
            }
        } else {
            startNativeCamera(deviceId);
            startProcessingLoop();
        }
    }

    // --- Preprocesado y escaneo con jsQR (fallback y mejora) ---
    let scanInterval = null;
    let lastScan = 0;
    const SCAN_DEBOUNCE_MS = 2500;

    function startProcessingLoop() {
        stopProcessingLoop();
        const readerEl = document.getElementById(readerId);
        const video = readerEl.querySelector('video');
        if (!video) return;

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        scanInterval = setInterval(() => {
            try {
                const w = video.videoWidth;
                const h = video.videoHeight;
                if (!w || !h) return;
                // ajustar canvas al tamaño del video (usar scale para mejorar performance)
                const scale = 0.6; // reduce tamaño para acelerar
                canvas.width = Math.floor(w * scale);
                canvas.height = Math.floor(h * scale);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Preprocesado mejorado: ajuste adaptativo de contraste y nitidez
                const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = img.data;
                
                // Calcular valores promedio y máximo para ajuste adaptativo
                let sum = 0, max = 0;
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i], g = data[i+1], b = data[i+2];
                    const v = 0.299*r + 0.587*g + 0.114*b; // Mejor fórmula para luminancia
                    sum += v;
                    max = Math.max(max, v);
                }
                const avg = sum / (data.length / 4);
                
                // Ajustar contraste y brillo de forma adaptativa
                const contrast = max < 180 ? 1.5 : 1.3; // Más contraste si la imagen es oscura
                const brightness = avg < 128 ? 20 : 10; // Más brillo si la imagen es oscura
                
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i], g = data[i+1], b = data[i+2];
                    // Convertir a escala de grises con mejor fórmula
                    let v = 0.299*r + 0.587*g + 0.114*b;
                    
                    // Aplicar contraste y brillo adaptativos
                    v = ((v - 128) * contrast) + 128 + brightness;
                    
                    // Añadir nitidez local
                    if (i > 4 && i < data.length - 4) {
                        const prev = 0.299*data[i-4] + 0.587*data[i-3] + 0.114*data[i-2];
                        const next = 0.299*data[i+4] + 0.587*data[i+5] + 0.114*data[i+6];
                        v = v + (v - (prev + next) / 2) * 0.5;
                    }
                    
                    // Asegurar límites
                    v = Math.max(0, Math.min(255, v));
                    data[i] = data[i+1] = data[i+2] = v;
                }
                ctx.putImageData(img, 0, 0);

                // run jsQR on the preprocessed image
                if (window.jsQR) {
                    const code = jsQR(img.data, canvas.width, canvas.height);
                    const overlay = document.querySelector('.qr-overlay');
                    if (code && code.data) {
                        // determinar si el centro del code está dentro del ROI central del overlay
                        const cx = (code.location.topLeftCorner.x + code.location.bottomRightCorner.x) / 2;
                        const cy = (code.location.topLeftCorner.y + code.location.bottomRightCorner.y) / 2;
                        // ROI: centro del canvas, un rectángulo relativo
                        const roiW = canvas.width * 0.7;
                        const roiH = canvas.height * 0.4;
                        const roiX = (canvas.width - roiW) / 2;
                        const roiY = (canvas.height - roiH) / 2;
                        const inside = cx >= roiX && cx <= (roiX + roiW) && cy >= roiY && cy <= (roiY + roiH);
                        if (overlay) {
                            if (inside) overlay.classList.add('detect'); else overlay.classList.remove('detect');
                        }
                        if (inside) {
                            const now = Date.now();
                            if (now - lastScan > SCAN_DEBOUNCE_MS) {
                                lastScan = now;
                                onScanSuccess(code.data, null);
                            }
                        }
                    } else {
                        if (overlay) overlay.classList.remove('detect');
                    }
                }
            } catch (e) {
                console.warn('Error en loop de preprocesado:', e);
            }
        }, 300);
    }

    function stopProcessingLoop() {
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
    }

    // Función para enumerar y poblar las cámaras
    async function enumerateAndPopulateCameras() {
        try {
            // Solicitar permiso de cámara primero
            await navigator.mediaDevices.getUserMedia({ video: true });
            
            // Obtener lista de cámaras
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');
            
            const select = document.getElementById('camera-select');
            if (!select) return;
            
            // Limpiar opciones existentes
            select.innerHTML = '<option value="">Seleccione cámara</option>';
            
            if (cameras.length === 0) {
                if (resultEl) resultEl.innerText = 'No se detectaron cámaras.';
                return;
            }
            
            // Ordenar cámaras (integrada primero)
            cameras.sort((a, b) => {
                const labelA = (a.label || '').toLowerCase();
                const labelB = (b.label || '').toLowerCase();
                const isIntegratedA = labelA.includes('integrated') || labelA.includes('integrada');
                const isIntegratedB = labelB.includes('integrated') || labelB.includes('integrada');
                if (isIntegratedA && !isIntegratedB) return -1;
                if (!isIntegratedA && isIntegratedB) return 1;
                return 0;
            });
            
            // Añadir cámaras al selector
            cameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Cámara ${index + 1}`;
                select.appendChild(option);
            });
            
            // Seleccionar primera cámara por defecto
            if (select.options.length > 1) {
                select.selectedIndex = 1;
                if (resultEl) resultEl.innerText = 'Cámara detectada. Haga clic en Cambiar cámara para iniciar.';
            }
            
            return cameras;
        } catch (error) {
            console.error('Error al enumerar cámaras:', error);
            if (resultEl) resultEl.innerText = 'Error al acceder a las cámaras: ' + error.message;
            return [];
        }
    }
    
    // Asociar la enumeración al botón de permisos
    if (btnRequestPerm) {
        btnRequestPerm.addEventListener('click', async () => {
            btnRequestPerm.disabled = true;
            try {
                const cameras = await enumerateAndPopulateCameras();
                if (cameras.length > 0) {
                    if (resultEl) resultEl.innerText = 'Permiso concedido. Seleccione una cámara y haga clic en Cambiar cámara.';
                }
            } catch (error) {
                console.error('Error al solicitar permisos:', error);
                if (resultEl) resultEl.innerText = 'Error al solicitar permisos de cámara: ' + error.message;
            } finally {
                btnRequestPerm.disabled = false;
            }
        });
    }

    // Listeners básicos para los botones siempre disponibles
    if (btnSwitch) {
        btnSwitch.addEventListener('click', () => {
            const id = cameraSelectedEl ? cameraSelectedEl.dataset.id : null;
            if (id) attemptStart(id);
        });
    }
    if (btnRestart) {
        btnRestart.addEventListener('click', () => {
            const id = cameraSelectedEl ? cameraSelectedEl.dataset.id : null;
            if (id) attemptStart(id);
        });
    }
    if (btnRequestPerm) {
        btnRequestPerm.addEventListener('click', async () => {
            try {
                // Solicitar permiso para cámara para poder leer labels
                await navigator.mediaDevices.getUserMedia({ video: true });
                const cams = await populateFromEnumerate();
                if (cams && cams.length && cameraSelect) cameraSelect.value = cameraSelect.value || (cams[0].deviceId || cams[0].id);
                if (resultEl) resultEl.innerText = 'Permiso concedido. Selecciona cámara y reinicia si es necesario.';
            } catch (e) {
                console.error('Permiso de cámara denegado o error:', e);
                if (resultEl) resultEl.innerText = 'No se concedió permiso de cámara.';
            }
        });
    }

    (async () => {
        const hasLib = await waitForHtml5(5000);
        if (!hasLib) {
            // fallback: poblar select usando enumerateDevices (puede requerir permiso de cámara)
            const cams = await populateFromEnumerate();
            if (!cams.length) {
                if (resultEl) resultEl.innerText = 'No se detectaron cámaras o la librería no cargó. Asegúrate de permitir el acceso a la cámara.';
            } else {
                if (resultEl) resultEl.innerText = 'Selecciona una cámara y pulsa Reiniciar cámara para intentarlo.';
            }
            // Configurar handlers para fallback usando getUserMedia
            let nativeStream = null;
            const readerEl = document.getElementById(readerId);

            async function startNativeCamera(deviceId) {
                try {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) throw new Error('getUserMedia no soportado');
                    // parar stream existente
                    await stopNativeCamera();
                    const constraints = { 
                    video: { 
                        deviceId: deviceId ? { exact: deviceId } : undefined, 
                        width: { ideal: 1920, min: 1280 }, 
                        height: { ideal: 1080, min: 720 },
                        focusMode: { ideal: "continuous" },
                        exposureMode: { ideal: "continuous" },
                        whiteBalanceMode: { ideal: "continuous" }
                    }, 
                    audio: false 
                };
                    nativeStream = await navigator.mediaDevices.getUserMedia(constraints);
                    // crear video si no existe
                    let video = readerEl.querySelector('#native-video');
                    if (!video) {
                        video = document.createElement('video');
                        video.id = 'native-video';
                        video.autoplay = true;
                        video.playsInline = true;
                        video.style.width = '100%';
                        readerEl.appendChild(video);
                    }
                    video.srcObject = nativeStream;
                    if (resultEl) resultEl.innerText = 'Cámara nativa iniciada.';
                } catch (err) {
                    console.error('Error al iniciar cámara nativa:', err);
                    if (resultEl) resultEl.innerText = 'No se pudo iniciar la cámara nativa: ' + err.message;
                }
            }

            async function stopNativeCamera() {
                try {
                    if (nativeStream) {
                        nativeStream.getTracks().forEach(t => t.stop());
                        nativeStream = null;
                    }
                    const video = document.getElementById('native-video');
                    if (video && video.parentNode) video.parentNode.removeChild(video);
                } catch (e) {
                    console.warn('Error parando la cámara nativa', e);
                }
            }

            // listeners para botones (fallback)
            if (btnSwitch && cameraSelect) {
                btnSwitch.addEventListener('click', () => {
                    const id = cameraSelect.value;
                    startNativeCamera(id);
                });
            }
            if (btnRestart) {
                btnRestart.addEventListener('click', () => {
                    const id = (cameraSelect && cameraSelect.value) ? cameraSelect.value : (cams[0] ? (cams[0].deviceId||cams[0].id) : null);
                    startNativeCamera(id);
                });
            }

            // Iniciar automáticamente la primera cámara si hay una
            if (cams.length) {
                const firstId = cameraSelect.value || (cams[0].deviceId || cams[0].id);
                startNativeCamera(firstId);
            }

            return;
        }

            // Si la librería está disponible, usar el flujo original
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length) {
                // Limpiar cualquier cámara activa primero
                if (window._activeHtml5Qr) {
                    window._activeHtml5Qr.stop().catch(() => {});
                    window._activeHtml5Qr.clear().catch(() => {});
                    window._activeHtml5Qr = null;
                }

                // Ordenar cámaras (integrada primero)
                cameras.sort((a, b) => {
                    const labelA = (a.label || '').toLowerCase();
                    const labelB = (b.label || '').toLowerCase();
                    const isIntegratedA = labelA.includes('integrated') || labelA.includes('integrada');
                    const isIntegratedB = labelB.includes('integrated') || labelB.includes('integrada');
                    if (isIntegratedA && !isIntegratedB) return -1;
                    if (!isIntegratedA && isIntegratedB) return 1;
                    return 0;
                });

                let cameraId = cameras[0].id || cameras[0].deviceId;                // Poblar select con cámaras
                if (cameraSelect) {
                    cameraSelect.innerHTML = '';
                    cameras.forEach(cam => {
                        const id = cam.id || cam.deviceId || cam.id;
                        const option = document.createElement('option');
                        option.value = id;
                        option.text = cam.label || id;
                        cameraSelect.appendChild(option);
                    });
                    cameraSelect.value = cameraId;
                }

                const html5Qr = new Html5Qrcode(readerId);

                // Intentar iniciar con constraints de resolución si el dispositivo lo soporta
                const startCamera = (id) => {
                    // Config con posible preferencia de resolución
                    const configWithConstraints = Object.assign({}, config, { videoConstraints: { width: { ideal: 1280 }, height: { ideal: 720 } } });
                    return html5Qr.start(id, configWithConstraints, onScanSuccess, onScanFailure)
                        .then(() => { window._activeHtml5Qr = html5Qr; })
                        .catch(err => {
                            // Si falla por constraints, reintentar sin constraints
                            console.warn('Fallo al aplicar constraints, reintentando sin constraints', err);
                            return html5Qr.start(id, config, onScanSuccess, onScanFailure).then(()=>{ window._activeHtml5Qr = html5Qr; });
                        });
                };

                attemptStart(cameraId);

                // Botón para cambiar cámara
                if (btnSwitch) {
                    btnSwitch.addEventListener('click', async () => {
                        const select = document.getElementById('camera-select');
                        if (!select || !select.value) {
                            if (resultEl) resultEl.innerText = 'Por favor, primero haga clic en Permitir cámara y seleccione una.';
                            return;
                        }
                        
                        // Deshabilitar controles
                        btnSwitch.disabled = true;
                        if (btnRestart) btnRestart.disabled = true;
                        if (select) select.disabled = true;
                        if (resultEl) resultEl.innerText = 'Iniciando cámara...';
                        
                        try {
                            // Limpiar cualquier instancia previa
                            await cleanupCamera();
                            
                            // Iniciar nueva cámara
                            const html5Qr = new Html5Qrcode(readerId);
                            const config = { 
                                fps: 15,
                                qrbox: { width: 300, height: 300 },
                                aspectRatio: 1.0,
                                experimentalFeatures: {
                                    useBarCodeDetectorIfSupported: true
                                }
                            };
                            
                            const configWithConstraints = {
                                ...config,
                                videoConstraints: {
                                    deviceId: select.value,
                                    width: { ideal: 1920, min: 1280 },
                                    height: { ideal: 1080, min: 720 },
                                    facingMode: "environment"
                                }
                            };
                            
                            await html5Qr.start(
                                select.value,
                                configWithConstraints,
                                onScanSuccess,
                                onScanFailure
                            );
                            
                            window._activeHtml5Qr = html5Qr;
                            if (resultEl) resultEl.innerText = 'Cámara activada. Apunte al código QR.';
                            
                        } catch (err) {
                            console.error('Error al iniciar la cámara:', err);
                            if (resultEl) resultEl.innerText = 'Error al iniciar la cámara: ' + err.message;
                        } finally {
                            // Reactivar controles
                            btnSwitch.disabled = false;
                            if (btnRestart) btnRestart.disabled = false;
                            if (select) select.disabled = false;
                        }
                    });
                }

                // Botón para reiniciar la cámara actual
                if (btnRestart) {
                    btnRestart.addEventListener('click', () => {
                        const currentId = cameraSelectedEl ? cameraSelectedEl.dataset.id : cameraId;
                        if (window._activeHtml5Qr) {
                            window._activeHtml5Qr.stop().then(()=> { stopProcessingLoop(); startCamera(currentId); }).catch(err=> console.error(err));
                        } else {
                            startCamera(currentId);
                        }
                    });
                }

                // Detener la cámara al salir de la página
                window.addEventListener('beforeunload', () => {
                    if (window._activeHtml5Qr) {
                        window._activeHtml5Qr.stop().catch(()=>{});
                        stopProcessingLoop();
                    }
                });
            } else {
                if (resultEl) resultEl.innerText = 'No se detectaron cámaras en este dispositivo.';
            }
        }).catch(async err => {
            console.warn('getCameras falló, intentando enumerateDevices como respaldo', err);
            await populateFromEnumerate();
            if (resultEl) resultEl.innerText = 'No se pudieron listar cámaras desde la librería; selecciona una cámara y presiona Reiniciar.';
        });
    })();

    function onScanSuccess(decodedText, decodedResult) {
        // Muestra el resultado en pantalla
        if (resultEl) resultEl.innerText = 'Código detectado: ' + decodedText;

        // Enviar al servidor para buscar datos del egresado
        fetch('../controlador/ProcesarQrController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            const infoEl = document.getElementById('qr-info');
            if (!infoEl) return;
            if (data.status === 'ok' && data.data && data.data.egresado) {
                const e = data.data.egresado;
                const titulos = data.data.titulos || [];
                // Renderizar tarjeta con datos y botones
                infoEl.innerHTML = `
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(e.nombreCompleto)}</h5>
                            <p><b>Identificación:</b> ${escapeHtml(e.identificacion)}</p>
                            <p><b>Carnet:</b> ${escapeHtml(e.carnet)}</p>
                            <p><b>Correo:</b> ${escapeHtml(e.correoPrincipal)}</p>
                            <p><b>Teléfono:</b> ${escapeHtml(e.telResidencia)}</p>
                            <p><b>Dirección:</b> ${escapeHtml(e.dirResidencia)}</p>
                            <p><b>Títulos:</b> ${escapeHtml(titulos.map(t=> t.nombre + (t.fechaGrado ? ' ('+t.fechaGrado+')' : '')).join(', '))}</p>
                            <div class="mt-3">
                                <button id="btn-download-pdf" class="btn btn-success btn-sm mr-2">Descargar Certificado PDF</button>
                                <button id="btn-print" class="btn btn-primary btn-sm">Imprimir Certificado</button>
                            </div>
                        </div>
                    </div>
                `;

                // Añadir listeners a los botones
                document.getElementById('btn-download-pdf').addEventListener('click', function(){
                    generateCertificatePDF(e, titulos, false);
                });
                document.getElementById('btn-print').addEventListener('click', function(){
                    generateCertificatePDF(e, titulos, true);
                });
            } else if (data.status === 'notfound') {
                infoEl.innerHTML = '<div class="alert alert-warning">Egresado no encontrado para el código escaneado.</div>';
            } else {
                infoEl.innerHTML = '<div class="alert alert-danger">Error al procesar el código.</div>';
            }
        })
        .catch(err => {
            const infoEl = document.getElementById('qr-info');
            if (infoEl) infoEl.innerHTML = '<div class="alert alert-danger">Error de conexión al servidor.</div>';
            console.error(err);
        });

        // Opcional: detener el lector automáticamente
        // scanner.clear();
    }

    function onScanFailure(error) {
        // No hacemos nada en cada fallo de lectura para no saturar la consola
        // console.warn(`Scan failure: ${error}`);
    }

    // Nota: el arranque ya se gestiona con Html5Qrcode.start() arriba
});

// Función para escapar texto en HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.replace(/[&<>"'`=\/]/g, function (s) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        })[s];
    });
}

// Solicita al backend la generación del certificado y abre el PDF resultante
function generateCertificatePDF(egresado, titulos, print) {
    const rut = (egresado.carnet || egresado.identificacion || '').trim();
    if (!rut) {
        if (window.Swal) Swal.fire('Error', 'No se pudo determinar el RUT para generar el certificado.', 'error');
        return;
    }

    if (window.Swal) {
        Swal.fire({
            title: 'Generando certificado...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    fetch('../controlador/GenerarCertificadoPDF.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams({ rut })
    })
        .then(response => response.json())
        .then(res => {
            if (window.Swal) Swal.close();
            if (res && res.success && res.url) {
                const win = window.open(res.url, '_blank');
                if (!win && window.Swal) {
                    Swal.fire('Listo', 'Certificado generado. Revisa la descarga bloqueada por el navegador.', 'success');
                }
            } else {
                if (window.Swal) Swal.fire('Error', (res && res.message) ? res.message : 'No se pudo generar el certificado.', 'error');
            }
        })
        .catch(() => {
            if (window.Swal) Swal.close();
            if (window.Swal) Swal.fire('Error', 'Error de conexión al generar el certificado.', 'error');
        });
}
