<?php
include '../modelo/Observacion.php';
include '../modelo/Egresado.php';
$observacion = new Observacion();
$egresado = new Egresado();

//-------------------------------------------------------------------
// Función para listar observaciones
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'listar') {
    $json = Array();
    $observacion->BuscarTodos();
    foreach ($observacion->objetos as $objeto) {
        $json[] = array(
            'id' => $objeto->id,      
            'observacion' => $objeto->observacion,
            'identificacion' => $objeto->identificacion,
            'nombreCompleto' => $objeto->nombrecompleto // Mostrar nombre completo en lugar de identificación
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}

//-------------------------------------------------------------------
// Función para buscar una observación
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'buscar') {
    $json = Array();
    $observacion->Buscar($_POST['dato']);
    foreach ($observacion->objetos as $objeto) {
        $json[]=array(
                    'id'=>$objeto->id,
                    'observacion'=>$objeto->observacion,
                    'identificacion'=>$objeto->identificacion
        );
    }
    $jsonstring = json_encode($json[0]);
    echo $jsonstring;
}

//-------------------------------------------------------------------
// Función para crear
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'crear'){
    $observacion->Crear($_POST['id'], $_POST['observacion'], $_POST['identificacion']);
}

//-------------------------------------------------------------------
// Función para editar
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'editar'){
    $observacion->Editar($_POST['id'], $_POST['observacion'], $_POST['identificacion']);
}

//-------------------------------------------------------------------
// Función para eliminar
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'eliminar'){
    $observacion->Eliminar($_POST['id']);
}

//-------------------------------------------------------------------
// Función para cargar select de egresados
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'obtener_egresados'){
    $json = Array();
    $egresado->Seleccionar();
    foreach ($egresado->objetos as $objeto) {
        $json[]=array(
            'identificacion'=>$objeto->identificacion,
            'nombreCompleto'=>$objeto->nombrecompleto
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
?>
