<?php
    include_once '../modelo/AgregarTitulo.php';
    $titulo = new AgregarTitulo();
    
    //-------------------------------------------------------------------
    // Funcion para buscar todos los registros DATATABLES
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'listar'){
        $json = Array();
        //LLamado al controlador
        $titulo->BuscarTodos('');
        foreach ($titulo->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->id,
                            'nombre'=>$objeto->nombre
            );
        }
        $jsonstring = json_encode($json);
        echo $jsonstring;

    }

    //-------------------------------------------------------------------
    // Funcion para buscar un titulo
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'buscar'){
        $json = Array();
        //LLamado al controlador
        $titulo->Buscar($_POST['dato']);
        foreach ($titulo->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->id,
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
        $titulo->Crear($_POST['id'], $_POST['nombre']);

    }

    //-------------------------------------------------------------------
    // Funcion para editar
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'editar'){
        $titulo->Editar($_POST['id'], $_POST['nombre']);

    }

    //-------------------------------------------------------------------
    // Funcion para eliminar
    //-------------------------------------------------------------------
    if ($_POST['funcion'] == 'eliminar'){
        $titulo->Eliminar($_POST['id']);

    }

    if ($_POST['funcion'] == 'seleccionar'){
        $json = Array();
        //LLamado al controlador
        $titulo->Seleccionar();
        foreach ($titulo->objetos as $objeto) {
            $json[]=array(
                            'id'=>$objeto->id,
                            'nombre'=>$objeto->nombre
            );
        }
        $jsonstring = json_encode($json);
        echo $jsonstring;

    }



?>
