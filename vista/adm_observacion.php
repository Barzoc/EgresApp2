<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: ../index.php");
  exit();
}
$_pag = 'Agregar Observaciones';
include_once 'layouts/header.php';
include_once 'layouts/nav.php';
?>

<!------------------------------------------------------>
<!--   Ventana Modal para CREAR Y EDITAR              -->
<!------------------------------------------------------>
<div class="modal fade" id="crear" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><span id="tit_ven">Nueva observación</span> </h5>
        <button data-dismiss="modal" arial-label="close" class="close">
          <span arial-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-success text-center" id="add" style='display:none;'>
          <i class="fa fa-check-circle m-1"> Operación realizada correctamente</i>
        </div>
        <div class="alert alert-danger text-center" id="noadd" style='display:none;'>
          <i class="fa fa-times-circle m-1"> Registro ya usado</i>
        </div>
        <form id="form-crear">
          <div class="form-group">
            <label for="id">ID</label>
            <input type="number" id="id" class="form-control" placeholder="Ingrese ID" required>
          </div>
          <div class="form-group">
            <label for="observacion">Observación</label>
            <textarea id="observacion" class="form-control" placeholder="Ingrese la observación" required></textarea>
          </div>
          <div class="form-group">
            <label for="identificacion">Identificación</label>
            <select name="identificacion" id="identificacion" class="form-control select2" style="width: 100%"></select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn bg-gradient-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-------------------------------------------------->
<!-- FIN Ventana Modal para el crear              -->
<!-------------------------------------------------->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1><?php echo $_pag; ?>
            <button class="btn-crear btn bg-gradient-primary btn-sm m-2" data-toggle="modal"
              data-target="#crear">Insertar Observación</button>
          </h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="./inicio.php">Inicio</a></li>
            <li class="breadcrumb-item active"><?php echo $_pag; ?></li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!------------------ Main content ------------------------------>
  <!-- ----------------------------------------------------------->
  <!------------------ Main content ------------------------------>
  <section class="content">
    <div class="row">
      <div class="col-12">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Observaciones</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="tabla" class="table table-bordered table-striped table-hover dataTable dtr-inline" role="grid">
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
include_once 'layouts/footer.php';
?>
<script src="../assets/js/observacion.js"></script>