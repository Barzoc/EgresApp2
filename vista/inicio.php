<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
include_once './layouts/header.php';
include_once './layouts/nav.php';
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Inicio</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">

                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-primary card-outline">
                        <div class="card-body">
                            <h5 class="card-title"><b>Visualizar gráficos</b></h5>
                            <br>
                            <p class="card-text">
                                Visualiza gráficos a partir de los estudiantes ingresados en el sistema.
                            </p>

                            <a href="./adm_dashboard.php" class="card-link">Gráficas</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><b>Ingresar carreras universitarias</b></h5>
                            <p class="card-text">
                                Ingresa aquí las carreras universitarias.
                            </p>
                            <a href="./adm_agregarTitulo.php" class="card-link">Carreras universitarias</a>
                        </div>
                    </div><!-- /.card -->

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><b>Añadir gestiones</b></h5>
                            <br>
                            <p class="card-text">
                                Añada la gestión empleada con el egresado.
                            </p>
                            <a href="./adm_gestion.php" class="card-link">Gestiones</a>
                        </div>
                    </div>
                </div>

                <!-- /.col-md-6 -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Tabla de egresados</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Gestione los datos de los estudiantes egresados.</p>
                            <a href="./adm_egresado.php" class="btn btn-primary">Lista de egresados</a>
                        </div>
                    </div>

                    <div class="card ">
                        <div class="card-header">
                            <h5 class="m-0">Insertar observaciones a egresados</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Inserte observaciones a egresados.</p>
                            <a href="./adm_observacion.php" class="btn btn-primary">Visualizar observaciones.</a>
                        </div>
                    </div>
                </div>
                <!-- /.col-md-6 -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
include_once './layouts/footer.php';
?>