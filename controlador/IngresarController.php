<?php
    session_start();
    include_once '../modelo/Ingresar.php';
    $ingreso = new Ingresar();

    //-------------------------------------------------------------------
    // Función para validar ingreso de credenciales
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'ingresar'){
        $ingreso->Validar($_POST['email'], $_POST['contrasena']);
    }

?>