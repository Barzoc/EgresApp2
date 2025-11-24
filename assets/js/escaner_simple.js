document.addEventListener('DOMContentLoaded', function() {
    const elements = {
        result: document.getElementById('qr-result'),
        buttonPerm: document.getElementById('btn-request-perm'),
        buttonStart: document.getElementById('btn-switch-camera'),
        buttonRestart: document.getElementById('btn-restart-camera'),
        reader: document.getElementById('qr-reader')
    };

    // Verificar elementos requeridos
    if (!elements.reader || !elements.buttonPerm || !elements.buttonStart) {
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
                await scanner.clear();
                scanner = null;
            } catch (error) {
                console.warn('Error al detener el escáner:', error);
            }
        }
    }

    // Actualizar lista de cámaras
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
        } catch (error) {
            showMessage('Error al listar cámaras: ' + error.message);
        }
    }

    // Solicitar permisos y listar cámaras
    elements.buttonPerm.addEventListener('click', async () => {
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
            await updateCameraList();
            showMessage('Permisos concedidos. Seleccione una cámara y presione "Iniciar cámara"');
        } catch (error) {
            showMessage('Error al solicitar permisos: ' + error.message);
        }
    });

    // Iniciar cámara
    elements.buttonStart.addEventListener('click', async () => {
        try {
            await stopScanner();
            
            scanner = new Html5Qrcode('qr-reader');
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            await scanner.start(
                { facingMode: 'user' },
                config,
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
    });

    // Reiniciar cámara
    elements.buttonRestart.addEventListener('click', async () => {
        try {
            await stopScanner();
            elements.buttonStart.click();
        } catch (error) {
            showMessage('Error al reiniciar la cámara: ' + error.message);
        }
    });

    // Limpiar al cerrar
    window.addEventListener('beforeunload', stopScanner);
});