<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon para la pestaña del navegador -->
    <link rel="icon" type="image/webp" href="../imagenes/icon_white.png">
    <!-- Metadatos para la descripción y previsualización de la página -->
    <meta name="description"
        content="Establece una nueva contraseña para tu cuenta en el sistema de gestión de egresados.">
    <meta property="og:title" content="Establecer Nueva Contraseña - Gestión de Egresados">
    <meta property="og:description" content="Crea una nueva contraseña para tu cuenta y recupera el acceso al sistema.">
    <meta property="og:image" content="../imagenes/icon.png">
    <meta property="og:type" content="website">
    <title>Establecer Nueva Contraseña - Gestión de Egresados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo" style="display: flex; flex-direction: column; align-items: center;">
            <img src="../imagenes/icon.png" alt="Icono del Sistema de Gestión de Egresados"
                style="width: 100px; height: auto;">
            <a href="#"><b>Establecer</b> Nueva Contraseña</a>
        </div>
        <!-- Tarjeta para establecer nueva contraseña -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Crea una nueva contraseña para tu cuenta.</p>
                <!-- Formulario para establecer nueva contraseña -->
                <form action="/path_to_reset_password" method="post">
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Nueva Contraseña" name="new_password"
                            required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Confirmar Nueva Contraseña"
                            name="confirm_password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Cambiar contraseña</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Scripts necesarios para AdminLTE -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>

</html>