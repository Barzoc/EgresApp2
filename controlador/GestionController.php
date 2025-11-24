<?php
    include_once '../modelo/Gestion.php';
    $gestion = new Gestion();
    
    //-------------------------------------------------------------------
    // Funcion para buscar todos los registros DATATABLES
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'listar'){
        $json = Array();
        //LLamado al controlador
        $gestion->BuscarTodos('');
        foreach ($gestion->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->idgestion,
                            'nombre'=>$objeto->nombre
            );
        }
        $jsonstring = json_encode($json);
        echo $jsonstring;

    }

    //-------------------------------------------------------------------
    // Funcion para buscar un gestion
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'buscar'){
        $json = Array();
        //LLamado al controlador
        $gestion->Buscar($_POST['dato']);
        foreach ($gestion->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->idgestion,
                            'nombre'=>$objeto->nombre
            );
        }
        $jsonstring = json_encode($json[0]);
        echo $jsonstring;
        
    }

    //-------------------------------------------------------------------
    // Funcion para crear
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'crear'){
        $gestion->Crear($_POST['id'], $_POST['nombre']);

    }

    //-------------------------------------------------------------------
    // Funcion para editar
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'editar'){
        $gestion->Editar($_POST['id'], $_POST['nombre']);

    }

    //-------------------------------------------------------------------
    // Funcion para eliminar
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'eliminar'){
        $gestion->Eliminar($_POST['id']);

    }

    if ($_POST['funcion'] == 'seleccionar'){
        $json = Array();
        //LLamado al controlador
        $gestion->Seleccionar();
        foreach ($gestion->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->idgestion,
                            'nombre'=>$objeto->nombre
            );
        }
        $jsonstring = json_encode($json);
        echo $jsonstring;

    }



?>
