<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
$titulo_pag = 'Escáner QR';
include_once './layouts/header.php';
include_once './layouts/nav.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo $titulo_pag; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="./inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active"><?php echo $titulo_pag; ?></li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <section class="content">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Escáner QR</h3>
            </div>
            <div class="card-body">
                <p>Utiliza la cámara de tu dispositivo para escanear códigos QR. Permite el acceso a la cámara cuando el navegador lo solicite.</p>

                <!-- Controles de cámara -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <button id="btn-request-perm" class="btn btn-primary">
                        <i class="fas fa-camera"></i> Permitir cámara
                    </button>
                    <select id="camera-select" class="form-select" style="max-width:320px;">
                        <option value="">Seleccione cámara</option>
                    </select>
                    <button id="btn-switch-camera" class="btn btn-secondary" disabled>
                        <i class="fas fa-play"></i> Iniciar cámara
                    </button>
                    <button id="btn-restart-camera" class="btn btn-outline-secondary" disabled>
                        <i class="fas fa-sync"></i>
                    </button>
                    <button id="btn-analyze-lens" class="btn btn-success">
                        <i class="fas fa-magic"></i> Analizar con Lens
                    </button>
                </div>

                <!-- Contenedor del escáner -->
                <div class="card">
                    <div class="card-body p-0">
                        <div id="qr-reader" class="bg-light" style="width:100%; max-width:640px; height:480px; position:relative; margin:0 auto;">
                            <div class="qr-overlay"></div>
                        </div>
                    </div>
                </div>

                <!-- Resultado -->
                <div class="mt-3">
                    <b>Resultado:</b>
                    <div id="qr-result" class="alert alert-light">
                        Esperando escaneo...
                    </div>
                    <div id="qr-link" class="alert alert-info" style="display:none;">
                        <a href="#" target="_blank">Abrir enlace</a>
                    </div>
                </div>

                <!-- Opciones de comportamiento -->
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="" id="opt-open-newtab" checked>
                    <label class="form-check-label" for="opt-open-newtab">
                        Abrir en nueva pestaña automáticamente
                    </label>
                </div>

                <!-- Franja inferior para mostrar resultados o errores (estilo rojo) -->
                <div id="qr-error-panel" class="mt-3" style="display:none;">
                    <div class="alert alert-danger">
                        <b>Error:</b>
                        <div id="qr-error-text">No se recibio el codigo</div>
                    </div>
                </div>

                <style>
                    #qr-reader {
                        background: #f8f9fa !important;
                    }
                    #qr-reader video {
                        width: 100% !important;
                        height: 100% !important;
                        object-fit: cover !important;
                    }
                    /* Ocultar la guía hasta que la cámara esté activa */
                    .qr-overlay {
                        display: none;
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: 250px;
                        height: 250px;
                        pointer-events: none;
                        border: 2px solid rgba(0, 123, 255, 0.5);
                        border-style: dashed;
                        border-radius: 8px;
                        transition: all 0.3s ease;
                    }
                    /* Mostrar la guía solo cuando hay video */
                    #qr-reader video ~ .qr-overlay {
                        display: block;
                    }
                    .qr-overlay.detect {
                        border-color: #28a745;
                        border-style: solid;
                        box-shadow: 0 0 20px rgba(40, 167, 69, 0.3);
                    }
                </style>
                
                <p class="text-muted mt-3">
                    Si el lector no se inicia, comprueba que tu navegador permite el acceso a la cámara y que estás en https o localhost.
                </p>
                <!-- Contenedor donde se mostrará la información del egresado -->
                <div id="qr-info"></div>
            </div>
        </div>
    </section>
</div>

<?php
include_once 'layouts/footer.php';
?>

<!-- Carga de bibliotecas (rutas relativas para evitar problemas con espacios en el nombre de carpeta) -->
<script src="../assets/plugins/html5-qrcode/html5-qrcode.min.js"></script>
<script src="../assets/plugins/jsqr/jsQR.js"></script>
<script src="../assets/js/procesar_qr.js"></script>
<script src="../assets/js/escaner_basico.js"></script>
<!-- ZXing JS (decodificación de PDF417/QR en navegador) -->
<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script src="../assets/js/escaner_lens.js"></script>

<style>
/* Overlay para el escáner QR */
.qr-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.qr-overlay::before {
    content: '';
    width: 200px;
    height: 200px;
    border: 3px dashed rgba(255,255,255,0.95);
    border-radius: 8px;
    background: transparent;
    transition: all 0.3s ease;
}

.qr-overlay.detect::before {
    border-color: #2ecc71;
    box-shadow: 0 0 20px rgba(46,204,113,0.4);
}
</style>
