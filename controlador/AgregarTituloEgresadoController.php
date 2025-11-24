<?php
include_once '../modelo/AgregarTituloEgresado.php';
$tituloEgresado = new AgregarTituloEgresado();

if ($_POST['funcion'] == 'agregarTitulo') {
    $egresado = $_POST['egresado'];
    $titulo = $_POST['titulo'];
    $fechaGrado = $_POST['fechaGrado'];

    $existe = $tituloEgresado->BuscarTitulo($egresado, $titulo);

    if (!empty($existe)) {
        echo 'existe';
    } else {
        $tituloEgresado->CrearTituloEgresado($egresado, $titulo, $fechaGrado);
        echo 'add';
    }
}

if ($_POST['funcion'] == 'seleccionarEgresados') {
    $tituloEgresado->seleccionarEgresados();
}

if ($_POST['funcion'] == 'seleccionarTitulos') {
    $tituloEgresado->seleccionarTitulos();
}

if ($_POST['funcion'] == 'obtenerTituloEgresado') {
    $identificacion = $_POST['identificacion'];
    $tituloEgresado->obtenerTituloEgresado($identificacion);
}
?>
