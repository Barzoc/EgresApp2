<?php
include_once '../modelo/Egresado.php';

$egresado = new Egresado();
//-------------------------------------------------------------------
// Función para obtener datos de género
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtenerDatosGenero') {
    $datos = $egresado->obtenerDatosGenero();
    echo json_encode($datos);
}

//-------------------------------------------------------------------
// Función para obtener datos de títulos
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtenerDatosTitulo') {
    $datos = $egresado->obtenerDatosTitulo();
    echo json_encode($datos);
}

//-------------------------------------------------------------------
// Función para obtener datos de gestión
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtenerDatosGestion') {
    $datos = $egresado->obtenerDatosGestion();
    echo json_encode($datos);
}

//-------------------------------------------------------------------
// Función para obtener datos de fallecidos
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtenerDatosFallecidos') {
    $datos = $egresado->obtenerDatosFallecidos();
    echo json_encode($datos);
}

//-------------------------------------------------------------------
// Función para obtener datos de año de graduación
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtenerDatosGraduacion') {
    $datos = $egresado->obtenerDatosGraduacion();
    echo json_encode($datos);
}
?>

