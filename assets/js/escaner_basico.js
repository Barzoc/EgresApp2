// Variables globales
let scanner = null;

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const elements = {
        result: document.getElementById('qr-result'),
        select: document.getElementById('camera-select'),
        buttonPerm: document.getElementById('btn-request-perm'),
        buttonStart: document.getElementById('btn-switch-camera'),
        buttonRestart: document.getElementById('btn-restart-camera'),
        reader: document.getElementById('qr-reader')
    };

    // Verificar elementos requeridos
    if (!elements.reader || !elements.select || !elements.buttonPerm || !elements.buttonStart) {
        console.error('Faltan elementos necesarios en la página');
        return;
    }

    // Mostrar mensaje en el elemento de resultado
    function showMessage(message) {
        if (elements.result) {
            elements.result.innerText = message;
        }
    }

    // Mostrar/ocultar franja inferior de errores/resultados
    function showErrorPanel(html) {
        const panel = document.getElementById('qr-error-panel');
        const text = document.getElementById('qr-error-text');
        if (panel) {
            panel.style.display = 'block';
        }
        if (text) {
            text.innerHTML = html;
        }
    }

    function hideErrorPanel() {
        const panel = document.getElementById('qr-error-panel');
        if (panel) panel.style.display = 'none';
    }

    // Detener el escáner
    async function stopScanner() {
        if (scanner) {
            try {
                await scanner.stop();
                scanner = null;
                // ocultar overlay cuando se detiene
                try { const overlay = document.querySelector('#qr-reader .qr-overlay'); if (overlay) overlay.style.display = 'none'; } catch(e){}
            } catch (error) {
                console.warn('Error al detener el escáner:', error);
            }
        }
    }

    // Iniciar el escáner
    async function startScanner(deviceId) {
        try {
            await stopScanner();
            scanner = new Html5Qrcode('qr-reader');
            
            await scanner.start(
                deviceId,
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                    showTorchButtonIfSupported: true,
                    defaultZoomValueIfSupported: 2,
                    disableFlip: false,
                    videoConstraints: {
                        width: { min: 640, ideal: 1280, max: 1920 },
                        height: { min: 480, ideal: 720, max: 1080 },
                        frameRate: { ideal: 15, max: 30 },
                        facingMode: "environment",
                        focusMode: "continuous",
                        focusDistance: { ideal: 0.3 },
                        brightness: { ideal: 100 },
                        contrast: { ideal: 100 },
                        exposureMode: "continuous"
                    },
                },
                (decodedText) => {
                    // Efecto visual de detección
                    const overlay = document.querySelector('.qr-overlay');
                    if (overlay) {
                        overlay.classList.add('detect');
                        setTimeout(() => overlay.classList.remove('detect'), 1000);
                    }

                    // Mostrar el resultado
                    showMessage('Código detectado!');
                    
                    // Procesar el enlace si es una URL
                    const linkDiv = document.getElementById('qr-link');
                    if (linkDiv) {
                        try {
                            const url = new URL(decodedText);
                            linkDiv.style.display = 'block';
                            const link = linkDiv.querySelector('a');
                            if (link) {
                                link.href = url.href;
                                link.textContent = 'Abrir enlace: ' + url.href;
                            }
                        } catch (e) {
                            linkDiv.style.display = 'none';
                            showMessage('Contenido detectado: ' + decodedText);
                        }
                    }

                    // Procesar el código
                    processScan(decodedText);

                    // Auto-redirección / abrir en nueva pestaña si el contenido es una URL válida
                    try {
                        const url = new URL(decodedText);
                        // Si es URL HTTP/HTTPS
                        if (url.protocol === 'http:' || url.protocol === 'https:') {
                            const openNewTabOpt = document.getElementById('opt-open-newtab');
                            const shouldOpenNewTab = openNewTabOpt ? openNewTabOpt.checked : true;

                            // Detener el escáner antes de abrir
                            (async () => {
                                try { await stopScanner(); } catch (e) { console.warn('Error stopping scanner', e); }

                                if (shouldOpenNewTab) {
                                    // Intentar abrir en nueva pestaña
                                    const newWin = window.open(url.href, '_blank');
                                    if (!newWin) {
                                        // Popup bloqueado -> mostrar enlace en la franja roja
                                        showErrorPanel('No se pudo abrir la nueva pestaña (bloqueado por el navegador). <a href="' + url.href + '" target="_blank">Abrir enlace</a>');
                                    }
                                } else {
                                    // Mostrar enlace en la franja roja
                                    showErrorPanel('<a href="' + url.href + '" target="_blank">Abrir enlace: ' + url.href + '</a>');
                                }
                            })();
                        }
                    } catch (e) {
                        // No es URL, no redirigir
                    }
                },
                () => {}
            );

            // Mostrar overlay cuando la cámara está activa (asegura que se vea el recuadro)
            try { const overlay = document.querySelector('#qr-reader .qr-overlay'); if (overlay) overlay.style.display = 'block'; } catch(e){}

            showMessage('Cámara activa. Apunte al código QR.');
        } catch (error) {
            showMessage('Error al iniciar la cámara: ' + error.message);
            console.error('Error completo:', error);
        }
    }

    // Enumerar cámaras disponibles
    async function updateCameraList() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');

            elements.select.innerHTML = '<option value="">Seleccione cámara</option>';
            
            cameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Cámara ${index + 1}`;
                elements.select.appendChild(option);
            });

            if (cameras.length > 0) {
                elements.select.selectedIndex = 1;
                elements.buttonStart.disabled = false;
            }

            return cameras.length > 0;
        } catch (error) {
            showMessage('Error al listar cámaras: ' + error.message);
            return false;
        }
    }

    // Event listeners
    elements.buttonPerm.addEventListener('click', async () => {
        elements.buttonPerm.disabled = true;
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
            const hasCameras = await updateCameraList();
            if (hasCameras) {
                showMessage('Permisos concedidos. Seleccione una cámara y presione "Iniciar cámara"');
            } else {
                showMessage('No se detectaron cámaras.');
            }
        } catch (error) {
            showMessage('Error al solicitar permisos: ' + error.message);
        } finally {
            elements.buttonPerm.disabled = false;
        }
    });

    elements.buttonStart.addEventListener('click', async () => {
        const selectedCamera = elements.select.value;
        if (!selectedCamera) {
            showMessage('Por favor seleccione una cámara primero.');
            return;
        }

        elements.buttonStart.disabled = true;
        elements.buttonRestart.disabled = true;
        elements.select.disabled = true;

        try {
            showMessage('Iniciando cámara...');
            await startScanner(selectedCamera);
        } finally {
            elements.buttonStart.disabled = false;
            elements.buttonRestart.disabled = false;
            elements.select.disabled = false;
        }
    });

    elements.buttonRestart.addEventListener('click', async () => {
        const selectedCamera = elements.select.value;
        if (!selectedCamera) {
            showMessage('Por favor seleccione una cámara primero.');
            return;
        }

        elements.buttonRestart.disabled = true;
        try {
            showMessage('Reiniciando cámara...');
            await startScanner(selectedCamera);
        } finally {
            elements.buttonRestart.disabled = false;
        }
    });

    // Limpiar al cerrar
    window.addEventListener('beforeunload', stopScanner);
});