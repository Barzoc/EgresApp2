$(document).ready(function () {
    var funcion;
    var edit = false;

    //----------------------------------------------------------
    // Construccion DataTable
    //----------------------------------------------------------
    var tabla = $('#tabla').DataTable({
        dom: 'Bfrtip',
        
        "lengthMenu": [[5, 10, 20, 25, 50, -1], [5, 10, 20, 25, 50, "Todos"]],
        "iDisplayLength": 20,
        "responsive": true,
        "autoWidth": false,
        //Parametrizar lenguaje
        "language":
        {
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "info": "Registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "sProcessing": "Procesando...",
        },
        "ajax": {
            "url": "../controlador/AgregarTituloController.php",
            "method": 'POST', //usamos el metodo POST
            "data": { funcion: 'listar' }, //enviamos POST
            "dataSrc": ""
        },
        "columns": [
            { "data": "id", "title": "ID" },
            { "data": "nombre", "title": "Nombre" },
            { "defaultContent": "<button class='editar btn btn-sm btn-primary' title='Editar' data-toggle='modal' data-target='#crear'><i class='fas fa-pencil-alt'></i></button><button class='eliminar btn btn-sm btn-danger' title='Eliminar'><i class='fas fa-trash'></i></button>", "title": "Acciones" }
        ],
        //Configurar COLUMNAS ---- Centrado Acciones
        "columnDefs": [
            {
                "className": "text-center",
                "targets": [2],
                "visible": true,
                "searchable": true
            }
        ],
        buttons: ["copy", "excel", "pdf", "print"]
    });

    tabla.buttons().container().appendTo($('.col-md-6:eq(0)', tabla.table().container()));

    //----------------------------------------------------------
    // Funcion que evalua click en CREAR
    // Solo para limpiar el formulario
    //----------------------------------------------------------
    $(document).on('click', '.btn-crear', (e) => {
        $('#form-crear').trigger('reset');
        $('#tit_ven').html('Creacion de Titulos ');
        edit = false;
    });

    //----------------------------------------------------------
    // Funcion que evalua click en EDITAR y obtiene el id
    // en DATATABLES responsives
    //----------------------------------------------------------
    $(document).on('click', '.editar', function () {
        edit = true;
        $('#tit_ven').html('Editar Titulos');
        if (tabla.row(this).child.isShown()) {
            var data = tabla.row(this).data();
        } else {
            var data = tabla.row($(this).parents("tr")).data();
        }
        const id = data.id; //capturo el ID
        buscar(id);
    });

    //-------------------------------------------------------------
    //Buscar 
    //-------------------------------------------------------------
    function buscar(dato) {
        funcion = 'buscar';
        $.post('../controlador/AgregarTituloController.php', { dato, funcion }, (response) => {
            const respuesta = JSON.parse(response);
            $('#id').val(respuesta.id);
            $('#nombre').val(respuesta.nombre);
        })
    };

    //----------------------------------------------------------
    // Funcion para crear o editar  en el formulario
    //----------------------------------------------------------
    $('#form-crear').submit(e => {
        //cargar los objetos de los formularios en variables JS
        let nombre = $('#nombre').val();
        let id = $('#id').val();
        if (edit == true)
            funcion = 'editar';
        else
            funcion = 'crear';

        $.post('../controlador/AgregarTituloController.php', { id, nombre, funcion }, (response) => {

            response = response.trim(); // Elimina espacios en blanco alrededor de la cadena

            if (response === 'add') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Título agregado correctamente'
                });
                $('#crear').modal('hide');
                tabla.ajax.reload(null, false);
            } else if (response === 'update') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Título actualizado correctamente'
                });
                $('#crear').modal('hide');
                tabla.ajax.reload(null, false);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo realizar la operación'
                });
            }
        });

        e.preventDefault();
    });

    //----------------------------------------------------------
    // Funcion que evalua click en ELIMNAR y obtiene el id
    // navegando a traves de la propiedad parentElement
    //----------------------------------------------------------
    $(document).on('click', '.eliminar', function () {
        if (tabla.row(this).child.isShown()) {
            var data = tabla.row(this).data();
        } else {
            var data = tabla.row($(this).parents("tr")).data();
        }
        const id = data.id; //capturo el ID
        const nombre = data.nombre; //capturo el nombre
        //Cargo los objetos ocultos obtenidos con javascript y enviarlos al controlador
        buscar(id);
        funcion = 'eliminar';

        Swal.fire({
            title: 'Desea eliminar ' + nombre + '?',
            text: "Esto no se podra revertir!",
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, eliminar!'
        }).then((result) => {
            if (result.value) {
                $.post('../controlador/AgregarTituloController.php', { id, funcion }, (response) => {
                    response = response.trim(); // Elimina espacios en blanco alrededor de la cadena
                    if (response == 'eliminado') {
                        Swal.fire(
                            'Eliminado!',
                            nombre + ' fue eliminado.',
                            'success'
                        )
                    }
                    else {
                        Swal.fire(
                            'No se pudo eliminar!',
                            nombre + ' esta utilizado',
                            'error'
                        )
                    }
                    tabla.ajax.reload(null, false);
                });
            }
        })
    })
})
