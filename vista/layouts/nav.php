<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link">Bienvenido al portal de gestión de egresados</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
                <a href="./inicio.php" class="brand-link">
                <img src="../assets/img/imagenes/icon_white.png" alt="Logo del Aplicativo"
                    class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">EgresApp2</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="./adm_escaner.php" class="nav-link">
                                <i class="nav-icon fas fa-qrcode"></i>
                                <p>Escáner QR</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./adm_egresado.php" class="nav-link">
                                <i class="nav-icon fas fa-table"></i>
                                <p>Añadir Egresados</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./adm_agregarTitulo.php" class="nav-link">
                                <i class="nav-icon fas fa-graduation-cap"></i>
                                <p>Añadir Titulos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./adm_dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-book-reader"></i>
                                <p>Estadísticas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../controlador/LogoutController.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Salir</p>
                            </a>
                        </li>
                        <!-- Añadir más elementos de menú según sea necesario -->
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>