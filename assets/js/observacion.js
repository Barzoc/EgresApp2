$(document).ready(function () {
    var funcion;
    var edit = false;

    // Llamada para cargar el select de egresados en el modal
    cargar_egresados();

    //----------------------------------------------------------
    // Construccion DataTable
    //----------------------------------------------------------
    var tabla = $('#tabla').DataTable({
        dom: 'Bfrtip',

        "lengthMenu": [[5, 10, 20, 25, 50, -1], [5, 10, 20, 25, 50, "Todos"]],
        "iDisplayLength": 20,
        "responsive": true,
        "autoWidth": false,
        // Parametrizar lenguaje
        "language": {
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
            "sProcessing": "Procesando..."
        },
        "ajax": {
            "url": "../controlador/ObservacionController.php",
            "method": 'POST',
            "data": { funcion:'listar' },
            "dataSrc": ""
        },
        "columns": [
            { "data": "id", "title": "ID" },
            { "data": "observacion", "title": "Observación" },
            { "data": "nombreCompleto", "title": "Identificación" }, // Mostrar nombre completo en lugar de identificación
            {"defaultContent": "<div class='btn-group'><button class='editar btn btn-sm btn-primary' title='Editar' data-toggle='modal' data-target='#crear'><i class='fas fa-pencil-alt'></i></button><button class='eliminar btn btn-sm btn-danger' title='Eliminar'><i class='fas fa-trash'></i></button></div>", "title":"Acciones"}
        ],
        // Configurar COLUMNAS ---- Centrado Acciones
        "columnDefs": [
            {
                "className": "text-center",
                "targets": [0, 1, 2],
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
        $('#tit_ven').html('Nueva observación');
        edit = false;
    });

    //----------------------------------------------------------
    // Funcion que evalua click en EDITAR y obtiene el id
    // en DATATABLES responsives
    //----------------------------------------------------------
    $(document).on('click', '.editar', function () {
        edit = true;
        $('#tit_ven').html('Editar observación');
        if (tabla.row(this).child.isShown()) {
            var data = tabla.row(this).data();
        } else {
            var data = tabla.row($(this).parents("tr")).data();
        }
        const id = data.id; // Capturo el ID
        buscar(id);
    });

    //-------------------------------------------------------------
    // Buscar 
    //-------------------------------------------------------------
    function buscar(dato) {
        funcion = 'buscar';
        $.post('../controlador/ObservacionController.php', {dato, funcion}, (response) => {
            const respuesta = JSON.parse(response);
            $('#id').val(respuesta.id);
            $('#observacion').val(respuesta.observacion);
            $('#identificacion').val(respuesta.identificacion).trigger('change');
        });
    };

    //----------------------------------------------------------
    // Funcion para crear o editar en el formulario
    //----------------------------------------------------------
    $('#form-crear').submit(e => {
        // Cargar los objetos de los formularios en variables JS
        let id = $('#id').val();
        let observacion = $('#observacion').val();
        let identificacion = $('#identificacion').val();

        if (edit == true)
            funcion = 'editar';
        else
            funcion = 'crear';

        $.post('../controlador/ObservacionController.php', { id, observacion, identificacion, funcion }, (response) => {
            response = response.trim(); // Elimina espacios en blanco alrededor de la cadena
            if (response == 'add') {
                Swal.fire({
                    title: 'Éxito!',
                    text: 'La observación fue creada correctamente.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else if (response == 'update') {
                Swal.fire({
                    title: 'Éxito!',
                    text: 'La observación fue actualizada correctamente.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'No se pudo realizar la operación.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
            $('#crear').modal('hide');
            tabla.ajax.reload(null, false);
        });
        e.preventDefault();
    });

    //----------------------------------------------------------
    // Funcion que evalua click en ELIMINAR y obtiene el id
    // navegando a traves de la propiedad parentElement
    //----------------------------------------------------------
    $(document).on('click', '.eliminar', function () {
        if (tabla.row(this).child.isShown()) {
            var data = tabla.row(this).data();
        } else {
            var data = tabla.row($(this).parents("tr")).data();
        }
        const id = data.id; // Capturo el ID
        const observacion = data.observacion; // Capturo la observación
        // Cargo los objetos ocultos obtenidos con javascript y enviarlos al controlador
        buscar(id);
        funcion = 'eliminar';

        Swal.fire({
            title: 'Desea eliminar la observación?',
            text: "Esto no se podrá revertir!",
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, eliminar!'
        }).then((result) => {
            if (result.value) {
                $.post('../controlador/ObservacionController.php', { id, funcion }, (response) => {
                    response = response.trim(); // Elimina espacios en blanco alrededor de la cadena
                    if (response == 'eliminado') {
                        Swal.fire(
                            'Eliminado!',
                            'La observación fue eliminada.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    }
                    else {
                        Swal.fire(
                            'No se pudo eliminar!',
                            'La observación esta utilizada',
                            'error'
                        );
                    }
                    tabla.ajax.reload(null, false);
                });
            }
        });
    });

    //----------------------------------------------------------
    // Función que carga el comboBox de egresados
    //----------------------------------------------------------

    function cargar_egresados() {
        funcion = 'obtener_egresados';
        $.post('../controlador/ObservacionController.php', { funcion }, (response) => {
            const registros = JSON.parse(response);
            let template = '';
            registros.forEach(registro => {
                template += `<option value="${registro.identificacion}">${registro.nombreCompleto}</option>`;
            });
            $('#identificacion').html(template);
        });
    }
});
