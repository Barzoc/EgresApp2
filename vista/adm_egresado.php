<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}
$titulo_pag = 'Egresados';
include_once './layouts/header.php';
include_once './layouts/nav.php';
?>

<!------------------------------------------------------>
<!--   Ventana Modal para CREAR Y EDITAR              -->
<!------------------------------------------------------>
<div class="modal fade" id="crear" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="tit_ven">Crear Egresado</span> </h5>
                <button data-dismiss="modal" arial-label="close" class="close">
                    <span arial-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success text-center" id="add" style='display:none;'>
                    <i class="fa fa-check-circle m-1"> Operación realizada correctamente</i>
                </div>
                <div class="alert alert-danger text-center" id="noadd" style='display:none;'>
                    <i class="fa fa-times-circle m-1"> El egresado ya existe</i>
                </div>
                <form id="form-crear">
                    <input type="hidden" id="identificacion_hidden" name="identificacion">
                    <div class="form-group">
                        <label for="nombreCompleto">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombreCompleto" name="nombreCompleto">
                    </div>
                                        <div class="form-group" style="display:none;">
                        <label for="telResidencia">Tel Residencia</label>
                        <input type="text" class="form-control" id="telResidencia" name="telResidencia">
                    </div>
                    <div class="form-group" style="display:none;">
                        <label for="telAlternativo">Tel Alternativo</label>
                        <input type="text" class="form-control" id="telAlternativo" name="telAlternativo">
                    </div>
                    <div class="form-group" style="display:none;">
                        <label for="correoSecundario">Correo Secundario</label>
                        <input type="email" class="form-control" id="correoSecundario" name="correoSecundario">
                    </div>
                                        <div class="form-group">
                        <label for="correoPrincipal">Correo Principal</label>
                        <input type="email" class="form-control" id="correoPrincipal" name="correoPrincipal">
                    </div>
                    <div class="form-group">
                        <label for="carnet">Carnet</label>
                        <input type="text" class="form-control" id="carnet" name="carnet">
                    </div>
                    <div class="form-group">
                        <label for="sexo">Sexo</label>
                        <select class="form-control" id="sexo" name="sexo">
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Título del egresado">
                    </div>
                    <div class="form-group">
                        <label for="fechaGrado">Fecha de Graduación</label>
                        <input type="date" class="form-control" id="fechaGrado" name="fechaGrado">
                    </div>
                                        <div class="form-group">
                        <label for="idGestion">ID Gestión</label>
                        <select name="idGestion" id="idGestion" class="form-control select2"
                            style="width: 100%"></select>
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

<!-------------------------------------------------->
<!--   Ventana Modal para subir Expediente (PDF)  -->
<!-------------------------------------------------->
<div class="modal fade" id="cambiarExpediente" tabindex="-1" role="dialog" aria-labelledby="expedienteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expedienteModalLabel">Subir Expediente (PDF)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <b id="nombre_expediente"></b>
                    <div><a id="link_expediente" href="#" target="_blank" style="display:none;">Ver expediente</a></div>
                </div>
                <div class="alert alert-success text-center" id="updateexpediente" style='display:none;'>
                    <i class="fa fa-check-circle m-1"> Expediente subido y datos extraídos correctamente</i>
                </div>
                <div class="alert alert-danger text-center" id="noupdateexpediente" style='display:none;'>
                    <i class="fa fa-times-circle m-1"> Error al procesar el expediente</i>
                </div>
                <form id="form-expediente" enctype="multipart/form-data">
                    <div class="form-group" id="expediente-upload-group">
                        <label for="file">Seleccionar Expediente (PDF)</label>
                        <input type="file" name="file" accept="application/pdf" class="form-control-file" required>
                    </div>
                    

                    <div class="datos-extraidos" style="display:none;">
                        <hr>
                        <h5>Datos Extraídos del PDF</h5>
                        <div class="form-group">
                            <label for="rut_extraido">RUT</label>
                            <input type="text" class="form-control" id="rut_extraido" name="rut_extraido" readonly>
                        </div>
                        <div class="form-group">
                            <label for="nombre_extraido">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre_extraido" name="nombre_extraido" readonly>
                        </div>
                        <div class="form-group">
                            <label for="fecha_egreso_extraido">Fecha de Egresado</label>
                            <input type="text" class="form-control" id="fecha_egreso_extraido" name="fecha_egreso_extraido" readonly>
                        </div>
                        <div class="form-group">
                            <label for="numero_certificado_extraido">Número de Certificado</label>
                            <input type="text" class="form-control" id="numero_certificado_extraido" name="numero_certificado_extraido" readonly>
                        </div>
                        <div class="form-group">
                            <label for="titulo_extraido">Título Obtenido</label>
                            <input type="text" class="form-control" id="titulo_extraido" name="titulo_extraido" readonly>
                        </div>
                        <div class="alert alert-warning text-center" id="noReconocido" style="display:none;">
                            <i class="fa fa-exclamation-triangle m-1"></i> No se reconocen datos, ¿desea agregarlos manualmente?
                            <br>
                            <button type="button" class="btn btn-sm btn-warning mt-2" id="btnManual">Agregar manualmente</button>
                        </div>
                    </div>

                    <input type="hidden" name="funcion" id="funcion_expediente" value="subir_expediente">
                    <input type="hidden" name="id_expediente" id="id_expediente">

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-outline-primary" id="btn-habilitar-edicion">Editar</button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar-expediente">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-------------------------------------------------->
<!-- FIN Ventana Modal para Expediente  -->
<!-------------------------------------------------->

<!-------------------------------------------------->
<!--   Ventana Modal para editar egresado (sin PDF)  -->
<!-------------------------------------------------->
<div class="modal fade" id="editarExpedienteModal" tabindex="-1" role="dialog" aria-labelledby="editarExpedienteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarExpedienteLabel">Editar egresado</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <b id="edit_nombre_expediente"></b>
                    <div><a id="edit_link_expediente" href="#" target="_blank" style="display:none;">Ver expediente</a></div>
                </div>
                <form id="form-editar-egresado">
                    <input type="hidden" id="edit_id_expediente" name="id_expediente">
                    <div class="form-group">
                        <label for="edit_rut">RUT</label>
                        <input type="text" class="form-control" id="edit_rut" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_nombre">Nombre Completo</label>
                        <input type="text" class="form-control" id="edit_nombre" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_correo">Correo Principal</label>
                        <input type="email" class="form-control" id="edit_correo" placeholder="Opcional" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_sexo">Sexo</label>
                        <select class="form-control" id="edit_sexo" disabled>
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_gestion">Gestión</label>
                        <select class="form-control" id="edit_gestion" disabled>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_fecha_egreso">Fecha de Egresado</label>
                        <input type="text" class="form-control" id="edit_fecha_egreso" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_numero_certificado">Número de Certificado</label>
                        <input type="text" class="form-control" id="edit_numero_certificado" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_titulo">Título Obtenido</label>
                        <input type="text" class="form-control" id="edit_titulo" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_fecha_entrega">Fecha de Entrega de Certificado</label>
                        <input type="text" class="form-control" id="edit_fecha_entrega" placeholder="Ej: 2010-06-08" readonly>
                    </div>
                    <div class="alert alert-warning text-center" id="editExpedienteAlert" style="display:none;">
                        <i class="fa fa-exclamation-triangle m-1"></i> No se reconocen datos, ¿desea agregarlos manualmente?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-outline-primary" id="btn-editar-egresado-campos">Editar</button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar-egresado-modal">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-------------------------------------------------->
<!-------- Modal para agregar Titulo Egresado ------>
<!-------------------------------------------------->
<div class="modal fade" id="modalTituloEgresado" tabindex="-1" role="dialog" aria-labelledby="tituloEgresadoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloEgresadoModalLabel">Agregar Título Egresado</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="existe" class="alert alert-warning" role="alert" style="display:none;">
                    <strong>El título ya existe para este egresado.</strong>
                </div>
                <form id="formTituloEgresado">
                    <div class="form-group">
                        <label for="egresado">Egresado</label>
                        <select class="form-control" id="egresado" name="egresado"></select>
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <select class="form-control" id="titulo" name="titulo"></select>
                    </div>
                    <div class="form-group">
                        <label for="fechaGrado">Fecha de Graduación</label>
                        <input type="date" class="form-control" id="fechaGrado" name="fechaGrado">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-------------------------------------------------->
<!-------- Modal para agregar Titulo Egresado ------>
<!-------------------------------------------------->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo $titulo_pag; ?>
                        <button class="btn-crear btn bg-gradient-primary btn-sm m-2" data-toggle="modal"
                            data-target="#crear">Crear Egresado</button>
                        <!-- Botón agregado: Subir Expediente (abre modal de subida) -->
                        <button class="btn-subir-expediente btn btn-secondary btn-sm m-2" type="button">Subir Expediente</button>
                    </h1>
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

    <!------------------ Main content ------------------------------>
    <!-- ----------------------------------------------------------->
    <!------------------ Main content ------------------------------>
    <section class="content">
        <div class="row">
            <div class="col-12">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Egresados</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped table-hover dataTable dtr-inline">
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

<?php // Bust cache: añadir versión por timestamp para forzar recarga del JS en el navegador ?>
<script src="../assets/js/egresado.js?v=<?php echo time(); ?>"></script>