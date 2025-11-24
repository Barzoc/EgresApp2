<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
include_once './layouts/header.php';
include_once './layouts/nav.php';
$titulo_pag = 'Estadísticas';
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
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="./inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active"><?php echo $titulo_pag; ?></li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!------------------ Main content ------------------------------>
    <!-- ----------------------------------------------------------->
    <!------------------ Main content ------------------------------>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Gráfico de Distribución por Género -->
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Distribución por Género</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="genderChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Gráfico de Cantidad de Egresados por Título -->
                <div class="col-md-6">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Cantidad de Egresados por Título</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="titleChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Gráfico de Distribución por Gestión -->
                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Distribución por Gestión Realizada</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="gestionChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Gráfico de Egresados Fallecidos -->
                <div class="col-md-6">
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Egresados Fallecidos</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="fallecidosChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Gráfico de Cantidad de Egresados por Año de Graduación -->
                <div class="col-md-12">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Cantidad de Egresados por Año de Graduación</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="graduacionChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col  -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid-->
    </section>
    <!-- /.content -->

</div>
<!-- /.content-wrapper -->
<?php
include_once 'layouts/footer.php';
?>
<script src="../assets/js/dashboard.js"></script>