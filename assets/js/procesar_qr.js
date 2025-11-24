// Función para procesar el código QR escaneado
async function processScan(qrText) {
    try {
        const formData = new FormData();
        formData.append('qr', qrText);

        const response = await fetch('../controlador/ProcesarQrController.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        const infoContainer = document.getElementById('qr-info');
        if (!infoContainer) {
            console.error('No se encontró el contenedor de información');
            return;
        }

        // Actualizar el contenedor con la información
        if (data.success) {
            infoContainer.innerHTML = `
                <div class="alert alert-success mt-3">
                    <h4>Información del Egresado:</h4>
                    <p><strong>Nombre:</strong> ${data.egresado.nombre}</p>
                    <p><strong>Apellido:</strong> ${data.egresado.apellido}</p>
                    <p><strong>DNI:</strong> ${data.egresado.dni}</p>
                    <p><strong>Email:</strong> ${data.egresado.email}</p>
                </div>`;

            // Agregar efecto visual al overlay
            const overlay = document.querySelector('.qr-overlay');
            if (overlay) {
                overlay.classList.add('detect');
                setTimeout(() => overlay.classList.remove('detect'), 2000);
            }
        } else {
            infoContainer.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <h4>Error:</h4>
                    <p>${data.message || 'Error al procesar el código QR'}</p>
                </div>`;
        }
    } catch (error) {
        console.error('Error al procesar el código QR:', error);
        document.getElementById('qr-info').innerHTML = `
            <div class="alert alert-danger mt-3">
                <h4>Error:</h4>
                <p>No se pudo procesar el código QR: ${error.message}</p>
            </div>`;
    }
}