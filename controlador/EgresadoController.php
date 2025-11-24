<?php
include_once '../modelo/Egresado.php';
$egresado = new Egresado();

//-------------------------------------------------------------------
// Función para listar egresados
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'listar') {
    $json = Array();
    $egresado->BuscarTodos('');
    foreach ($egresado->objetos as $objeto) {
        $json[] = array(
            'identificacion' => $objeto->identificacion,
            'nombreCompleto' => $objeto->nombrecompleto,
            'dirResidencia' => $objeto->dirresidencia,
            'telResidencia' => $objeto->telresidencia,
            'telAlternativo' => $objeto->telalternativo,
            'correoPrincipal' => $objeto->correoprincipal,
            'correoSecundario' => $objeto->correosecundario,
            'carnet' => $objeto->carnet,
            'sexo' => $objeto->sexo,
            'fallecido' => $objeto->fallecido,
            'idGestion' => null,
            'nombreGestion' => null,
            'titulo_obtenido' => $objeto->titulo_obtenido ?? null,
            'titulo_catalogo' => $objeto->titulo_catalogo ?? null,
            'titulo' => $objeto->titulo_obtenido ?? ($objeto->titulo_catalogo ?? ''),
            'numeroCertificado' => $objeto->numerocertificado ?? null,
            'avatar' => $objeto->avatar,
            'expediente_pdf' => isset($objeto->expediente_pdf) ? $objeto->expediente_pdf : null,
            'fechaGrado' => isset($objeto->fechagrado) ? $objeto->fechagrado : null,
            'fechaEntregaCertificado' => isset($objeto->fechaentregacertificado) ? $objeto->fechaentregacertificado : null
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}

//-------------------------------------------------------------------
// Función para buscar un egresado
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'buscar') {
    $json = Array();
    $egresado->Buscar($_POST['dato']);
    foreach ($egresado->objetos as $objeto) {
        $json[] = array(
            'identificacion' => $objeto->identificacion,
            'nombreCompleto' => $objeto->nombrecompleto,
            'dirResidencia' => $objeto->dirresidencia,
            'telResidencia' => $objeto->telresidencia,
            'telAlternativo' => $objeto->telalternativo,
            'correoPrincipal' => $objeto->correoprincipal,
            'correoSecundario' => $objeto->correosecundario,
            'carnet' => $objeto->carnet,
            'sexo' => $objeto->sexo,
            'fallecido' => $objeto->fallecido,
            'idGestion' => $objeto->idgestion,
            'nombreGestion' => $objeto->nombre_gestion,
                'avatar' => $objeto->avatar,
                'expediente_pdf' => isset($objeto->expediente_pdf) ? $objeto->expediente_pdf : null,
                'fechaGrado' => isset($objeto->fechagrado) ? $objeto->fechagrado : null,
                'fechaEntregaCertificado' => isset($objeto->fechaentregacertificado) ? $objeto->fechaentregacertificado : null
        );
    }
    $jsonstring = json_encode($json[0]);
    echo $jsonstring;
}

//-------------------------------------------------------------------
// Función para crear
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'crear') {
    $respuesta = $egresado->Crear($_POST['nombreCompleto'], $_POST['dirResidencia'], $_POST['telResidencia'],
        $_POST['telAlternativo'], $_POST['correoPrincipal'], $_POST['correoSecundario'], $_POST['carnet'],
        $_POST['sexo'], $_POST['fallecido'], $_POST['avatar']);
    
    // Si se creó el egresado y se proporcionó título y fecha, crear también el título
    if ($respuesta == 'add' && isset($_POST['titulo']) && isset($_POST['fechaGrado']) && !empty($_POST['titulo']) && !empty($_POST['fechaGrado'])) {
        // Obtener el ID del egresado recién creado
        $sql = "SELECT MAX(identificacion) as last_id FROM egresado";
        $query = $egresado->acceso->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $last_id = $result['last_id'];
        
        // Incluir el modelo de título y crear el registro
        include_once '../modelo/AgregarTituloEgresado.php';
        $tituloEgresado = new AgregarTituloEgresado();
        $tituloEgresado->CrearTituloEgresado($last_id, $_POST['titulo'], $_POST['fechaGrado']);
    }
    
    echo $respuesta; // Este echo debe retornar 'add' si fue añadido y 'noadd' si ya existía
}


//-------------------------------------------------------------------
// Función para editar
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'editar') {
    $egresado->Editar($_POST['identificacion'], $_POST['nombreCompleto'], $_POST['dirResidencia'], $_POST['telResidencia'],
        $_POST['telAlternativo'], $_POST['correoPrincipal'], $_POST['correoSecundario'], $_POST['carnet'],
        $_POST['sexo'], $_POST['fallecido']);
    echo 'update';    
}

//-------------------------------------------------------------------
// Función para eliminar
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'eliminar') {
    $egresado->Eliminar($_POST['id']);
}

//-------------------------------------------------------------------
// Función para cargar select
//-------------------------------------------------------------------
if ($_POST['funcion'] == 'seleccionar') {
    $json = Array();
    $egresado->Seleccionar();
    foreach ($egresado->objetos as $objeto) {
        $json[] = array(
            'id' => $objeto->identificacion,
            'nombre' => $objeto->nombrecompleto
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}

//----------------------------------------------------------------------------------
//Este tipo de funcion solo aplica para el formData (envio de archivos e imagenes)
//----------------------------------------------------------------------------------
if ($_POST['funcion'] == 'cambiar_logo') {
    if (($_FILES['photo']['type'] == 'image/jpeg') || ($_FILES['photo']['type'] == 'image/png') || ($_FILES['photo']['type'] == 'image/gif')) {
        //Se obtiene el nombre del archivo
        $nombre = uniqid() . '-' . $_FILES['photo']['name'];
        //Concatena el directorio con el nombre del archivo
        $ruta = '../assets/img/prod/' . $nombre;
        //Funcion PHP que sube la imagen al servidor
        move_uploaded_file($_FILES['photo']['tmp_name'], $ruta);
        $egresado->CambiarLogo($_POST['id_avatar'], $nombre);

        foreach ($egresado->objetos as $objeto) {
            if ($objeto->avatar != 'default.png')
                unlink('../assets/img/prod/' . $objeto->avatar);
        }
        //Retorno de un Json con dos valores
        $json = array();
        $json[] = array(
            'ruta' => $ruta,
            'alert' => 'editalogo'
        );
    }
    //En caso de una imagen con formato incorrecto
    else {
        $json = array();
        $json[] = array(
            'alert' => 'noeditalogo'
        );
    }
    $jsonstring = json_encode($json[0]);
    echo $jsonstring;
}

//----------------------------------------------------------------------------------
// Subir expediente PDF
//----------------------------------------------------------------------------------
if (isset($_POST['funcion']) && $_POST['funcion'] == 'cambiar_expediente') {
    // Asegurarse de que llega un archivo
    if (isset($_FILES['file']) && $_FILES['file']['type'] == 'application/pdf') {
        // generar nombre único
        $nombre = uniqid() . '-' . $_FILES['file']['name'];
        $dir = '../assets/expedientes/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ruta = $dir . $nombre;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $ruta)) {
            // actualizar campo en modelo
            $egresado->CambiarExpediente($_POST['id_expediente'], $nombre);
            // eliminar expediente anterior si existe (el modelo puede devolver objetos previos)
            foreach ($egresado->objetos as $objeto) {
                if (!empty($objeto->expediente_pdf) && $objeto->expediente_pdf != $nombre)
                    @unlink($dir . $objeto->expediente_pdf);
            }
            $json = array();
            $json[] = array(
                'ruta' => $ruta,
                'alert' => 'editexpediente'
            );
        } else {
            $json = array();
            $json[] = array('alert' => 'noeditexpediente');
        }
    } else {
        $json = array();
        $json[] = array('alert' => 'noeditexpediente');
    }
    $jsonstring = json_encode($json[0]);
    echo $jsonstring;
}
?>
