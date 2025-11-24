// Variables globales
let scanner = null;
let currentCamera = null;

// Configuración del escáner
const config = {
    fps: 10,
    qrbox: {
        width: 250,
        height: 250
    },
    aspectRatio: 1.0,
    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
    verbose: false,
    videoConstraints: {
        width: 640,
        height: 480,
        facingMode: "user"
    }
};

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

    // Detener el escáner
    async function stopScanner() {
        if (scanner) {
            try {
                await scanner.stop();
                await new Promise(resolve => setTimeout(resolve, 300)); // Espera para asegurar que la cámara se libere
                await scanner.clear();
                // Limpiar las referencias internas del escáner
                const videoElement = document.querySelector('#qr-reader video');
                if (videoElement) {
                    videoElement.srcObject = null;
                }
                scanner = null;
            } catch (error) {
                console.warn('Error al detener el escáner:', error);
            }
        }
        
        // Limpiar el elemento del lector y cualquier residuo
        const readerElement = document.getElementById('qr-reader');
        if (readerElement) {
            readerElement.innerHTML = '';
            // Forzar liberación de recursos
            window.URL.revokeObjectURL(readerElement.querySelector('video')?.src);
        }
    }

    // Iniciar el escáner
    async function startScanner(deviceId) {
        try {
            // Siempre detener y limpiar primero
            await stopScanner();
            
            // Crear nuevo escáner
            scanner = new Html5Qrcode('qr-reader');
            
            let constraints;
            if (deviceId) {
                constraints = {
                    deviceId: { exact: deviceId },
                    width: 640,
                    height: 480,
                    facingMode: "user"
                };
            } else {
                constraints = {
                    facingMode: "user",
                    width: 640,
                    height: 480
                };
            }

            // Iniciar el escáner con las restricciones apropiadas
            await scanner.start(
                { facingMode: "user" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                },
                (text) => {
                    showMessage('Código detectado: ' + text);
                    processScan(text);
                },
                () => {} // Ignorar errores de escaneo
            );
            
            // Configuración con mejor calidad
            const scanConfig = {
                ...config,
                videoConstraints: {
                    deviceId: deviceId,
                    width: { ideal: 1920, min: 1280 },
                    height: { ideal: 1080, min: 720 },
                    focusMode: ['continuous', 'auto'],
                    exposureMode: ['continuous', 'auto'],
                    whiteBalanceMode: ['continuous', 'auto']
                }
            };

            await scanner.start(
                deviceId,
                scanConfig,
                (text) => {
                    showMessage('Código detectado: ' + text);
                    processScan(text);
                },
                () => {} // Ignorar errores de escaneo
            );

            showMessage('Cámara activa. Apunte al código QR.');
        } catch (error) {
            showMessage('Error al iniciar la cámara: ' + error.message);
            console.error('Error completo:', error);
        }
    }

    // Enumerar cámaras disponibles
    async function updateCameraList() {
        try {
            // Primero liberamos todas las tracks existentes
            const tracks = await navigator.mediaDevices.getUserMedia({ video: true });
            tracks.getTracks().forEach(track => track.stop());

            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');

            elements.select.innerHTML = '<option value="">Seleccione cámara</option>';
            
            // Nueva lógica de ordenamiento y etiquetado
            const cameraOrder = cameras.map(camera => {
                const label = (camera.label || '').toLowerCase();
                const isIntegrated = label.includes('integrated') || 
                                   label.includes('integrada') || 
                                   label.includes('built') || 
                                   label.includes('internal');
                const isUSB = label.includes('usb') || 
                            label.includes('external') || 
                            label.includes('webcam');
                
                return {
                    device: camera,
                    isIntegrated,
                    isUSB,
                    order: isIntegrated ? 0 : (isUSB ? 1 : 2)
                };
            }).sort((a, b) => a.order - b.order);

            cameraOrder.forEach(({device}, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                
                // Mejor etiquetado para las cámaras
                let label = device.label || `Cámara ${index + 1}`;
                if (label.toLowerCase().includes('integrated') || 
                    label.toLowerCase().includes('built') || 
                    label.toLowerCase().includes('internal')) {
                    label = '📸 Cámara Integrada';
                } else if (label.toLowerCase().includes('usb') || 
                         label.toLowerCase().includes('external') || 
                         label.toLowerCase().includes('webcam')) {
                    label = '🎥 Webcam USB';
                }
                
                option.text = label;
                elements.select.appendChild(option);
            });

            // Seleccionar la primera cámara disponible
            if (cameraOrder.length > 0) {
                elements.select.selectedIndex = 1;
                elements.buttonStart.disabled = false;
                // Guardar la primera cámara como predeterminada
                localStorage.setItem('defaultCamera', cameraOrder[0].device.deviceId);
            }

            return cameraOrder.length > 0;
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

    // Función para reiniciar completamente el sistema de cámaras
    async function resetCameraSystem() {
        try {
            // Detener el escáner actual
            await stopScanner();
            
            // Liberar TODOS los recursos de video
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            for (const device of videoDevices) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: { exact: device.deviceId } }
                    });
                    stream.getTracks().forEach(track => track.stop());
                } catch (e) {
                    console.warn(`Error liberando dispositivo ${device.label}:`, e);
                }
            }

            // Esperar a que se liberen los recursos
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Actualizar lista de cámaras
            await updateCameraList();
            
            return true;
        } catch (error) {
            console.error('Error en resetCameraSystem:', error);
            return false;
        }
    }

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
            
            // Siempre realizar un reinicio completo al cambiar de cámara
            await resetCameraSystem();
            
            // Esperar un momento adicional
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Intentar iniciar la cámara seleccionada
            await startScanner(selectedCamera);
            
            // Actualizar la cámara actual
            currentCamera = selectedCamera;
            
        } catch (error) {
            console.error('Error al cambiar de cámara:', error);
            showMessage('Error al cambiar de cámara. Intentando recuperar...');
            
            // Intentar recuperar usando la cámara predeterminada
            const defaultCamera = localStorage.getItem('defaultCamera');
            if (defaultCamera && defaultCamera !== selectedCamera) {
                try {
                    await startScanner(defaultCamera);
                    elements.select.value = defaultCamera;
                    currentCamera = defaultCamera;
                } catch (e) {
                    showMessage('No se pudo recuperar la cámara. Por favor, recarga la página.');
                }
            }
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
        elements.buttonStart.disabled = true;
        elements.select.disabled = true;

        try {
            showMessage('Reiniciando cámara...');
            await startScanner(selectedCamera);
        } finally {
            elements.buttonRestart.disabled = false;
            elements.buttonStart.disabled = false;
            elements.select.disabled = false;
        }
    });

    // Limpiar al cerrar
    window.addEventListener('beforeunload', stopScanner);
});