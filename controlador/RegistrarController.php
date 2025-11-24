<?php

    include_once '../modelo/Registrar.php';
    $registro = new Registrar();


    //-------------------------------------------------------------------
    // Función para registrar usuarios
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'registrar'){
        if ($_POST['contrasena'] == '') {
            echo 'passwordVacia';
            return;
        }
        $registro->Crear($_POST['nombre'], $_POST['email'],$_POST['contrasena']);
    }

?>

