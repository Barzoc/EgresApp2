<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="./assets/img/imagenes/icon_white.png">
    <meta name="description" content="Sistema CRUD para la gestión de Certificados de Egresados. Inicie sesión para acceder al panel de administración y gestionar los datos de los egresados.">
    <meta property="og:title" content="Sistema de Gestión de Egresados">
    <meta property="og:description" content="Acceso al sistema de gestión de egresados para administrar información relevante de los exalumnos.">
    <meta property="og:image" content="./assets/img/imagenes/icon.png">
    <meta property="og:type" content="website">
    <title>EgresApp2 - Sistema de Gestión de Egresados</title>
    <link rel="stylesheet" href="./assets/css/adminlte.min.css">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .info-egresado-container {
            flex: 1;
            max-width: 400px;
            margin-top: 100px;
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .info-egresado-container.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .info-egresado {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-egresado h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
        }
        .info-egresado p {
            margin-bottom: 8px;
            color: #495057;
            font-size: 0.95em;
        }
        .info-egresado i {
            width: 20px;
            color: #6c757d;
            margin-right: 5px;
        }
        .info-egresado .label {
            font-weight: 600;
            color: #495057;
        }
        .info-egresado ul {
            margin: 5px 0 0 0;
            padding-left: 25px;
            list-style-type: none;
        }
        .info-egresado ul li {
            margin-bottom: 5px;
            position: relative;
        }
        .info-egresado ul li:before {
            content: "•";
            color: #6c757d;
            position: absolute;
            left: -15px;
        }
        .info-egresado .text-muted {
            color: #6c757d !important;
        }
        .info-section {
            padding: 0 5px;
        }
        .titulos-section {
            border-top: 1px solid #e9ecef;
            padding-top: 10px;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                align-items: center;
            }
            .info-egresado-container {
                margin-top: 20px;
                width: 100%;
            }
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo" style="display: flex; flex-direction: column; align-items: center;">
                <img src="./assets/img/imagenes/icon.png" alt="Icono del Sistema de Gestión de Egresados" style="width: 100px; height: auto;">
                <a href="#"><b>Egres</b>App2</a>
            </div>
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Ingresa tus credenciales</p>
                    <!-- Panel separado para consulta RUT -->
                    <div id="rut-panel" style="margin-bottom:16px;padding:8px;border:1px solid #e9ecef;border-radius:4px;background:#fafafa;">
                        <label for="rut_login" style="font-weight:600;margin-bottom:6px;display:block">Run solicitante</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="rut_login" placeholder="Ej: 12345678-k" autocomplete="off">
                            <div class="input-group-append">
                                <button id="validate_rut_btn" type="button" class="btn btn-secondary">Validar</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <small id="rut-error" class="text-danger" style="display:none"></small>
                        </div>
                    </div>

                <form id="login-form" method="post">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" name="email" id="email" placeholder="Correo electrónico" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="contrasena" id="contrasena" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-danger" style="display: none;" id="error-alert"></div>
                <br>
                <p class="mb-1">
                    <a href="./vista/recuperar.php">Olvidé mi contraseña</a>
                </p>
                <p class="mb-0">
                    <a href="./vista/registrar.php" class="text-center">Quiero registrarme</a>
                </p>
            </div>
        </div>
    </div>
    <script src="./assets/plugins/jquery/jquery.min.js"></script>
    <script src="./assets/js/adminlte.min.js"></script>
    <script src="./assets/js/ingresar.js"></script>
    <script src="./assets/js/login_rut.js"></script>
    <!-- jsPDF desde CDN (UMD) para generación de certificados en cliente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="./assets/js/autoconsulta.js"></script>
</body>

</html>
