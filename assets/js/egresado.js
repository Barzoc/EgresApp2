$(document).ready(function () {
    var funcion;
    var edit = false;

    const expedienteFieldSelectors = {
        rut: '#rut_extraido',
        nombre: '#nombre_extraido',
        fecha_egreso: '#fecha_egreso_extraido',
        numero_certificado: '#numero_certificado_extraido',
        titulo: '#titulo_extraido'
    };

    const setEditButtonEnabled = (enabled) => {
        $('#btn-habilitar-edicion').prop('disabled', !enabled);
    };

    const setExpedienteReadonly = (readonly) => {
        Object.values(expedienteFieldSelectors).forEach(selector => {
            $(selector).prop('readonly', readonly);
        });
    };

    const evaluateExpedienteFields = () => {
        const missingKeys = [];
        Object.entries(expedienteFieldSelectors).forEach(([key, selector]) => {
            const value = ($(selector).val() || '').trim();
            if (!value) {
                missingKeys.push(key);
            }
        });

        const needsManual = missingKeys.length > 0;
        setExpedienteReadonly(!needsManual);

        if (needsManual) {
            $('#noReconocido').show();
            $('#btnManual').show();
            $('#btn-guardar-expediente').text('Subir y Procesar');
            $('#form-expediente').data('modo', 'process');
        } else {
            $('#noReconocido').hide();
            $('#btnManual').hide();
            $('#btn-guardar-expediente').text('Guardar');
            $('#form-expediente').data('modo', 'save');
        }

        return needsManual;
    };

    const enableManualEditing = () => {
        setExpedienteReadonly(false);
        $('#noReconocido').hide();
        $('#btnManual').hide();
        $('#btn-guardar-expediente').text('Guardar');
        $('#form-expediente').data('modo', 'save');
    };

    setEditButtonEnabled(false);

    // Cargar egresados y catálogos al iniciar
    cargar_egresados();
    cargar_titulos();
    cargar_titulos_form();

    const pdfExportColumns = [1, 4, 5, 6, 8, 9, 10];

    // Inicializar la tabla DataTable con configuraciones personalizadas
    var tabla = $('#tabla').DataTable({
        dom: 'Bfrtip',

        "lengthMenu": [[5, 10, 20, 25, 50, -1], [5, 10, 20, 25, 50, "Todos"]],
        "iDisplayLength": 10,
        "responsive": true,
        "autoWidth": false,
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
            "url": "../controlador/EgresadoController.php",
            "method": 'POST',
            "data": { funcion: 'listar' },
            "dataSrc": ""
        },
        "columns": [
            { "data": "identificacion", "title": "Identificación", "visible": false },
            { "data": "nombreCompleto", "title": "Nombre Completo" },
            { "data": "dirResidencia", "title": "Dir Residencia", "visible": false },
            { "data": "telResidencia", "title": "Tel Residencia", "visible": false },
            { "data": "correoPrincipal", "title": "Correo Principal" },
            { "data": "carnet", "title": "Carnet" },
            { "data": "sexo", "title": "Sexo" },
            { "data": "fallecido", "title": "Fallecido", "visible": false },
            {
                "data": null,
                "title": "Título",
                "render": function(data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        return row.titulo_catalogo || row.titulo || '';
                    }
                    return row.titulo_catalogo || row.titulo || '';
                }
            },
            { "data": "numeroCertificado", "title": "N° Certificado" },
            { 
                "data": null, 
                "title": "Año Egresado", 
                "render": function(data, type, row) {
                    const formatYMD = value => {
                        if (!value) return null;
                        const parts = value.split('-');
                        if (parts.length === 3) {
                            const [year, month, day] = parts;
                            return `${day}/${month}/${year}`;
                        }
                        return null;
                    };

                    const fechaGrado = formatYMD(row.fechaGrado);
                    if (fechaGrado) {
                        return fechaGrado;
                    }

                    const fechaEntrega = formatYMD(row.fechaEntregaCertificado);
                    if (fechaEntrega) {
                        return fechaEntrega;
                    }

                    return 'Sin fecha';
                }
            },
            {
                "data": null,
                "title": "Acciones",
                "orderable": false,
                "render": function (data, type, row) {
                    const tieneExpediente = !!row.expediente_pdf;
                    const viewBtn = tieneExpediente
                        ? `<button class='ver-expediente btn bg-teal btn-sm' title='Ver expediente' data-file='${row.expediente_pdf}'><i class='fas fa-file-pdf'></i></button>`
                        : `<button class='ver-expediente btn bg-teal btn-sm' title='Sin expediente' disabled><i class='fas fa-file-pdf'></i></button>`;

                    return "<div class='btn-group'>"
                        + viewBtn
                        + "<button type='button' class='editar btn btn-sm btn-primary' title='Editar'><i class='fas fa-pencil-alt'></i></button>"
                        + "<button class='eliminar btn btn-sm btn-danger' title='Eliminar'><i class='fas fa-trash'></i></button>"
                        + "</div>";
                }
            }
        ],
        "columnDefs": [
            {
                "className": "text-center",
                "targets": [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                "visible": true,
                "searchable": true
            }
        ],
        buttons: [
            {
                extend: 'copy',
                text: 'Copiar',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'csv',
                text: 'CSV',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Excel',
                title: 'Listado de Egresados',
                exportOptions: {
                    columns: [1, 4, 5, 6, 9, 10, 11]
                },
                customize: function (xlsx) {
                    const sheet = xlsx.xl.worksheets['sheet1.xml'];
                    const $sheet = $(sheet);
                    const styles = xlsx.xl['styles.xml'];
                    const $styles = $(styles);

                    const fonts = $styles.find('fonts');
                    let fontCount = parseInt(fonts.attr('count'), 10);
                    const titleFontId = fontCount;
                    fonts.append('<font><sz val="18"/><color theme="1"/><name val="Calibri"/></font>');
                    fontCount++;
                    const headerFontId = fontCount;
                    fonts.append('<font><sz val="14"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>');
                    fontCount++;
                    fonts.attr('count', fontCount);

                    const fills = $styles.find('fills');
                    let fillCount = parseInt(fills.attr('count'), 10);
                    const titleFillId = fillCount;
                    fills.append('<fill><patternFill patternType="solid"><fgColor rgb="FF5B82B8"/><bgColor indexed="64"/></patternFill></fill>');
                    fillCount++;
                    const headerFillId = fillCount;
                    fills.append('<fill><patternFill patternType="solid"><fgColor rgb="FF1F497D"/><bgColor indexed="64"/></patternFill></fill>');
                    fillCount++;
                    const rowAltFillId = fillCount;
                    fills.append('<fill><patternFill patternType="solid"><fgColor rgb="FFEFF4FB"/><bgColor indexed="64"/></patternFill></fill>');
                    fillCount++;
                    fills.attr('count', fillCount);

                    const cellXfs = $styles.find('cellXfs');
                    let styleCount = parseInt(cellXfs.attr('count'), 10);
                    const titleStyleId = styleCount;
                    cellXfs.append(`<xf numFmtId="0" fontId="${titleFontId}" fillId="${titleFillId}" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>`);
                    styleCount++;
                    const headerStyleId = styleCount;
                    cellXfs.append(`<xf numFmtId="0" fontId="${headerFontId}" fillId="${headerFillId}" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>`);
                    styleCount++;
                    const rowAltStyleId = styleCount;
                    cellXfs.append(`<xf numFmtId="0" fontId="0" fillId="${rowAltFillId}" borderId="0" xfId="0" applyFill="1"/>`);
                    styleCount++;
                    cellXfs.attr('count', styleCount);

                    const getColumnLetter = (index) => {
                        let letter = '';
                        let temp = index;
                        while (temp > 0) {
                            const modulo = (temp - 1) % 26;
                            letter = String.fromCharCode(65 + modulo) + letter;
                            temp = Math.floor((temp - modulo) / 26);
                        }
                        return letter;
                    };

                    const shiftRowNumbers = () => {
                        const rows = $sheet.find('sheetData row');
                        rows.each(function () {
                            const r = parseInt($(this).attr('r'), 10);
                            const newR = r + 1;
                            $(this).attr('r', newR);
                            $(this).find('c').each(function () {
                                const cellRef = $(this).attr('r');
                                const col = cellRef.replace(/[0-9]/g, '');
                                const row = parseInt(cellRef.replace(/[^0-9]/g, ''), 10);
                                $(this).attr('r', `${col}${row + 1}`);
                            });
                        });
                    };

                    shiftRowNumbers();

                    const columnCount = $('col', $sheet).length || 7;
                    const lastCol = getColumnLetter(columnCount);

                    const sheetData = $sheet.find('sheetData');
                    const columns = Array.from({ length: columnCount }, (_, i) => getColumnLetter(i + 1));
                    const titleCells = columns.map((col, idx) => {
                        if (idx === 0) {
                            return `<c t="inlineStr" s="${titleStyleId}" r="${col}1"><is><t>Egresados - EgresApp2</t></is></c>`;
                        }
                        return `<c s="${titleStyleId}" r="${col}1"/>`;
                    }).join('');
                    const titleRow = $(`<row r="1">${titleCells}</row>`);
                    sheetData.prepend(titleRow);

                    const headerRow = sheetData.find('row[r="2"]');
                    headerRow.find('c').each(function () {
                        $(this).attr('s', headerStyleId);
                    });

                    sheetData.find('row').each(function () {
                        const r = parseInt($(this).attr('r'), 10);
                        if (r >= 3 && r % 2 === 1) {
                            $(this).find('c').each(function () {
                                $(this).attr('s', rowAltStyleId);
                            });
                        }
                    });

                    // Anchuras amigables
                    const widths = [28, 30, 18, 12, 30, 32, 16];
                    $('col', $sheet).each(function (i) {
                        if (widths[i]) {
                            $(this).attr('width', widths[i]);
                            $(this).attr('customWidth', 1);
                        }
                    });

                    // Aplicar autofiltro
                    const rowCount = $('row', $sheet).length;
                    $sheet.find('autoFilter').remove();
                    sheetData.after(`<autoFilter ref="A2:${lastCol}${rowCount}"/>`);
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                title: 'Listado de Egresados',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                margin: [20, 20, 20, 20],
                exportOptions: {
                    columns: pdfExportColumns
                },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 9;
                    doc.defaultStyle.margin = [0, 1, 0, 1];

                    doc.styles.tableHeader = {
                        fillColor: '#1F4E79',
                        color: '#FFFFFF',
                        fontSize: 11,
                        bold: true,
                        alignment: 'center'
                    };

                    doc.styles.tableBodyEven = { fillColor: '#EFF4FB' };
                    doc.styles.tableBodyOdd = { fillColor: '#FFFFFF' };

                    doc.content.splice(0, 1);
                    doc.content.unshift(
                        {
                            text: 'Egresados - EgresApp2',
                            style: 'title',
                            alignment: 'center',
                            margin: [0, 0, 0, 2]
                        },
                        {
                            text: 'Reporte generado el ' + new Date().toLocaleDateString('es-CL', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }),
                            style: 'subtitle',
                            alignment: 'center',
                            margin: [0, 0, 0, 6]
                        }
                    );

                    doc.styles.title = {
                        fontSize: 16,
                        bold: true,
                        color: '#1F4E79'
                    };
                    doc.styles.subtitle = {
                        fontSize: 9,
                        italics: true,
                        color: '#5B5B5B'
                    };

                    const tableNode = doc.content.find(item => item.table);
                    if (!tableNode || !tableNode.table) {
                        return;
                    }

                    const body = tableNode.table.body;
                    body.forEach(function(row, rowIndex) {
                        if (rowIndex === 0) return;
                        row.forEach(function(cell, cellIndex) {
                            cell.margin = [3, 2, 3, 2];
                            if (cellIndex === 2 || cellIndex === 3 || cellIndex === 5) {
                                cell.alignment = 'center';
                            } else {
                                cell.alignment = 'left';
                            }
                        });
                    });

                    const dt = $('#tabla').DataTable();
                    const columnWidthsPx = pdfExportColumns.map(idx => {
                        const headerCell = $(dt.column(idx).header());
                        const width = headerCell.length ? headerCell.outerWidth() : null;
                        return width && width > 0 ? width : 80;
                    });
                    const totalWidthPx = columnWidthsPx.reduce((sum, val) => sum + val, 0) || 1;
                    const pageWidth = (doc.pageSize && doc.pageSize.width)
                        ? doc.pageSize.width
                        : (doc.pageOrientation === 'landscape' ? 841.89 : 595.28);
                    const marginLeft = Array.isArray(doc.pageMargins) ? doc.pageMargins[0] : 20;
                    const marginRight = Array.isArray(doc.pageMargins) ? doc.pageMargins[2] : 20;
                    const availableWidth = Math.max(pageWidth - marginLeft - marginRight, 400);
                    const scaleFactor = availableWidth / totalWidthPx;
                    const tableWidths = columnWidthsPx.map(width => parseFloat((width * scaleFactor).toFixed(2)));
                    tableNode.table.widths = tableWidths.length ? tableWidths : ['*'];
                    tableNode.layout = {
                        paddingLeft: function () { return 3; },
                        paddingRight: function () { return 3; },
                        paddingTop: function () { return 2; },
                        paddingBottom: function () { return 2; },
                        hLineWidth: function () { return 0.3; },
                        vLineWidth: function () { return 0.3; },
                        hLineColor: function () { return '#B0C4DE'; },
                        vLineColor: function () { return '#B0C4DE'; }
                    };
                }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                exportOptions: {
                    columns: ':visible'
                }
            },
            'colvis'
        ]
    });

    tabla.buttons().container().appendTo($('.col-md-6:eq(0)', tabla.table().container()));

    // Ver expediente en nueva pestaña
    $(document).on('click', '.ver-expediente', function () {
        const file = $(this).data('file');
        if (!file) {
            if (window.Swal) Swal.fire('Información', 'Este egresado aún no tiene expediente cargado.', 'info');
            return;
        }

        const url = `../assets/expedientes/expedientes_subidos/${file}`;
        window.open(url, '_blank');
    });

    // Enviar el formulario para subir y procesar el expediente (PDF)
    $('#form-expediente').submit(e => {
        const formMode = $('#form-expediente').data('modo') || 'process';
        const onlySave = formMode === 'save';

        if (onlySave) {
            e.preventDefault();

            const payload = {
                id_expediente: $('#id_expediente').val(),
                rut: $('#rut_extraido').val(),
                nombre: $('#nombre_extraido').val(),
                fecha_egreso: $('#fecha_egreso_extraido').val(),
                numero_certificado: $('#numero_certificado_extraido').val(),
                titulo: $('#titulo_extraido').val(),
                fecha_entrega: $('#fecha_entrega_extraido').val() || $('#fecha_egreso_extraido').val(),
                correo: $('#correo_extraido').val(),
                sexo: $('#sexo_extraido').val(),
                gestion: $('#gestion_extraido').val()
            };

            Swal.fire({
                title: 'Guardando...',
                text: 'Actualizando datos del expediente',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.post('../controlador/GuardarExpedienteManualController.php', payload, function(res) {
                Swal.close();
                if (!res || !res.success) {
                    Swal.fire('Error', res?.mensaje || 'No se pudieron guardar los cambios.', 'error');
                    return;
                }

                Swal.fire('Guardado', res.mensaje || 'Datos actualizados correctamente', 'success');
                if (typeof tabla !== 'undefined') {
                    tabla.ajax.reload(null, false);
                }
            }, 'json').fail(function() {
                Swal.close();
                Swal.fire('Error', 'No se pudieron guardar los cambios.', 'error');
            });

            return;
        }

        e.preventDefault();

        // Mostrar loader o spinner
        Swal.fire({
            title: 'Procesando...',
            text: 'Subiendo y analizando el expediente',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        let formData = new FormData($('#form-expediente')[0]);
        
        $.ajax({
            url: '../controlador/ProcesarExpedienteController.php',
            type: 'POST',
            data: formData,
            cache: false,
            processData: false,
            contentType: false
        }).done(function (response) {
            try {
                // Convertir a JSON solo si es una cadena
                const json = typeof response === 'string' ? JSON.parse(response) : response;
                Swal.close();
                
                // Mostrar información de depuración en consola
                console.log('Respuesta completa:', json);
                
                if (json.success) {
                    if (json.estado === 'pending') {
                        $('.datos-extraidos').hide();
                        $('#updateexpediente').hide();
                        $('#noupdateexpediente').hide();

                        if (json.queue_id) {
                            $('#form-expediente').data('queue-id', json.queue_id);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Expediente procesado',
                            text: json.mensaje || 'Datos extraídos correctamente.',
                            timer: 3000,
                            showConfirmButton: false
                        });

                        return;
                    }

                    if (json.debug) {
                        console.log('Texto extraído:', json.debug.texto_extraido);
                        console.log('Longitud del texto:', json.debug.texto_largo);
                        console.log('Datos crudos:', json.debug.datos_crudos);
                    }

                    const datos = json.datos || {};
                    // Mostrar datos extraídos
                    if (!$('#cambiarExpediente').hasClass('show')) {
                        $('#cambiarExpediente').modal('show');
                    }

                    $('.datos-extraidos').show();
                    $('#rut_extraido').val(datos.rut || '');
                    $('#nombre_extraido').val(datos.nombre || '');
                    $('#fecha_egreso_extraido').val(datos.fecha_egreso || '');
                    $('#numero_certificado_extraido').val(datos.numero_certificado || '');
                    $('#titulo_extraido').val(datos.titulo || '');
                    $('#id_expediente').val(json.egresado_id || '');
                    setEditButtonEnabled(true);

                    evaluateExpedienteFields();

                    // Mostrar mensaje de éxito
                    $('#updateexpediente').hide('slow').show(1000).text('Expediente subido y datos extraídos correctamente');
                    
                    // Actualizar link para ver el PDF
                    $('#link_expediente')
                        .attr('href', '..\/assets\/expedientes\/expedientes_subidos\/' + json.archivo)
                        .show();

                    if (typeof tabla !== 'undefined') {
                        tabla.ajax.reload(null, false);
                    }
                } else {
                    throw new Error(json.mensaje);
                }
            } catch (err) {
                Swal.close();
                console.error('Error al procesar expediente:', err);
                $('#noupdateexpediente').hide('slow').show(1000).text('Los datos no fueron ingresados.');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message || 'Los datos no fueron ingresados.'
                });
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            Swal.close();
            console.error('Error en la petición AJAX:', textStatus, errorThrown);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Los datos no fueron ingresados.'
            });
        });
    });

        // Habilitar edición manual si no se reconocen datos
        $(document).on('click', '#btnManual, #btn-habilitar-edicion', function () {
            enableManualEditing();
        });

        // Re-evaluar cuando se muestra el modal
        $(document).on('shown.bs.modal', '#cambiarExpediente', function () {
            evaluateExpedienteFields();
        });

    // Botón para generar certificado desde la vista admin
    $(document).on('click', '#btn_generar_cert_admin', function () {
        const carnet = $('#form-expediente').data('carnet');
        if (!carnet) {
            if (window.Swal) Swal.fire('Error', 'No se encontró el carnet del egresado', 'error');
            return;
        }
        // Llamar al endpoint de autoconsulta para obtener los datos necesarios
        $.post('../controlador/AutoconsultaController.php', { rut: carnet }, function (res) {
            try {
                // res ya viene como JSON desde el endpoint
                if (!res || !res.success) {
                    if (window.Swal) Swal.fire('Error', res.message || 'No fue posible obtener datos del egresado', 'error');
                    return;
                }
                // Pasar los datos al generador global
                const jsonData = JSON.stringify(res);
                // cerrar modal y mostrar loader en generarCertificado
                $('#cambiarExpediente').modal('hide');
                if (typeof window.generarCertificado === 'function') {
                    window.generarCertificado(jsonData, carnet);
                } else {
                    if (window.Swal) Swal.fire('Info', 'La generación está disponible desde la herramienta de autoconsulta.', 'info');
                }
            } catch (err) {
                console.error('Error al procesar datos de autoconsulta', err);
                if (window.Swal) Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
            }
        }, 'json').fail(function () {
            if (window.Swal) Swal.fire('Error', 'Error al conectar con el servidor de autoconsulta', 'error');
        });
    });

    // Evento para mostrar el formulario de creación de egresado
    $(document).on('click', '.btn-crear', (e) => {
        $('#form-crear').trigger('reset');
        $('#tit_ven').html('Nuevo egresado');
        // Reiniciar identificacion oculta
        $('#identificacion_hidden').val('');
        edit = false;
        $('#titulo').prop('disabled', false);
    });

    // Evento para abrir modal de Subir Expediente desde el header (global)
    $(document).on('click', '.btn-subir-expediente', (e) => {
        // Resetear formulario y UI del modal
        $('#form-expediente').trigger('reset');
        $('.datos-extraidos').hide();
        $('#updateexpediente').hide();
        $('#noupdateexpediente').hide();
        $('#id_expediente').val('');
        $('#nombre_expediente').text('');
        $('#link_expediente').hide();
        $('#form-expediente').data('modo', 'process');
        setEditButtonEnabled(false);
        $('#cambiarExpediente').modal('show');
    });

    // Controlador del modal de edición independiente
    const editModalSelectors = {
        rut: '#edit_rut',
        nombre: '#edit_nombre',
        correo: '#edit_correo',
        sexo: '#edit_sexo',
        fecha_egreso: '#edit_fecha_egreso',
        numero_certificado: '#edit_numero_certificado',
        titulo: '#edit_titulo',
        fecha_entrega: '#edit_fecha_entrega'
    };

    const setEditModalReadonly = (readonly) => {
        Object.entries(editModalSelectors).forEach(([key, selector]) => {
            const $element = $(selector);
            if ($element.is('select')) {
                $element.prop('disabled', readonly);
            } else {
                $element.prop('readonly', readonly);
            }
        });
    };

    const openEditModal = (data) => {
        $('#form-editar-egresado')[0].reset();
        $('#edit_id_expediente').val(data.identificacion || '');
        $('#edit_nombre_expediente').text(data.nombreCompleto || '');
        if (data.expediente_pdf) {
            $('#edit_link_expediente').attr('href', '../assets/expedientes/expedientes_subidos/' + data.expediente_pdf).show();
        } else {
            $('#edit_link_expediente').hide();
        }

        $('#edit_rut').val(data.carnet || '');
        $('#edit_nombre').val(data.nombreCompleto || '');
        $('#edit_correo').val(data.correoPrincipal || '');
        $('#edit_sexo').val(data.sexo || '');
        $('#edit_fecha_egreso').val(data.fechaEntregaCertificado || data.fechaGrado || '');
        $('#edit_numero_certificado').val(data.numeroCertificado || '');
        $('#edit_titulo').val(data.titulo || '');
        $('#edit_fecha_entrega').val(data.fechaEntregaCertificado || '');

        setEditModalReadonly(true);
        $('#btn-editar-egresado-campos').prop('disabled', false).text('Editar');
        $('#btn-guardar-egresado-modal').prop('disabled', false);
        $('#editExpedienteAlert').hide();
        $('#editarExpedienteModal').modal('show');
    };

    $(document).on('click', '.editar', function () {
        let data;
        if (tabla.row(this).child.isShown()) {
            data = tabla.row(this).data();
        } else {
            data = tabla.row($(this).parents('tr')).data();
        }

        if (!data) {
            return;
        }

        openEditModal(data);
    });

    $('#btn-editar-egresado-campos').on('click', function () {
        setEditModalReadonly(false);
        $(this).prop('disabled', true).text('Editando');
    });

    $('#editarExpedienteModal').on('hidden.bs.modal', function () {
        setEditModalReadonly(true);
        $('#btn-editar-egresado-campos').prop('disabled', false).text('Editar');
    });

    $('#form-editar-egresado').on('submit', function (e) {
        e.preventDefault();
        const payload = {
            id_expediente: $('#edit_id_expediente').val(),
            rut: $('#edit_rut').val(),
            nombre: $('#edit_nombre').val(),
            correo: $('#edit_correo').val(),
            sexo: $('#edit_sexo').val(),
            fecha_egreso: $('#edit_fecha_egreso').val(),
            numero_certificado: $('#edit_numero_certificado').val(),
            titulo: $('#edit_titulo').val(),
            fecha_entrega: $('#edit_fecha_entrega').val()
        };

        Swal.fire({
            title: 'Guardando...',
            text: 'Actualizando datos del expediente',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.post('../controlador/GuardarExpedienteManualController.php', payload, function(res) {
            Swal.close();
            if (!res || !res.success) {
                Swal.fire('Error', res?.mensaje || 'No se pudieron guardar los cambios.', 'error');
                return;
            }

            Swal.fire('Guardado', res.mensaje || 'Datos actualizados correctamente', 'success');
            $('#editarExpedienteModal').modal('hide');
            if (typeof tabla !== 'undefined') {
                tabla.ajax.reload(null, false);
            }
        }, 'json').fail(function() {
            Swal.close();
            Swal.fire('Error', 'No se pudieron guardar los cambios.', 'error');
        });
    });

    // Función para buscar un egresado por identificación
    function buscar(dato) {
        funcion = 'buscar';
        $.post('../controlador/EgresadoController.php', { dato, funcion }, (response) => {
            const respuesta = JSON.parse(response);
            $('#identificacion_hidden').val(respuesta.identificacion);
            $('#nombreCompleto').val(respuesta.nombreCompleto);
            $('#dirResidencia').val(respuesta.dirResidencia);
            $('#telResidencia').val(respuesta.telResidencia);
            $('#telAlternativo').val(respuesta.telAlternativo);
            $('#correoPrincipal').val(respuesta.correoPrincipal);
            $('#correoSecundario').val(respuesta.correoSecundario);
            $('#carnet').val(respuesta.carnet);
            $('#sexo').val(respuesta.sexo);
            $('#fallecido').val(respuesta.fallecido);
            $('#titulo').val(respuesta.titulo || '');

            $('#nombre_avatar').html(respuesta.nombreCompleto);
            $('#id_avatar').val(respuesta.identificacion);
            $('#avataractual').attr('src', '../assets/img/prod/' + respuesta.avatar);
            // Mostrar enlace al expediente si existe
            if (respuesta.expediente_pdf) {
                $('#link_expediente').attr('href', '../assets/expedientes/expedientes_subidos/' + respuesta.expediente_pdf).show();
            } else {
                $('#link_expediente').hide();
            }
        });
    }

    // Enviar el formulario para crear o editar un egresado
    $('#form-crear').submit(e => {
        let nombreCompleto = $('#nombreCompleto').val();
        let dirResidencia = $('#dirResidencia').val() || 'Sin dirección';
        let telResidencia = $('#telResidencia').val() || 'Sin teléfono';
        let telAlternativo = $('#telAlternativo').val() || 'Sin teléfono alternativo';
        let correoPrincipal = $('#correoPrincipal').val();
        let correoSecundario = $('#correoSecundario').val() || 'sin@correo.com';
        let carnet = $('#carnet').val();
        let sexo = $('#sexo').val();
        let fallecido = $('#fallecido').val() || 'No';
        let titulo = $('#titulo').val();
        let fechaGrado = $('#fechaGrado').val();
        let avatar = 'default.png';

        if (edit == true)
            funcion = 'editar';
        else
            funcion = 'crear';

        // No enviar identificacion al crear
        let data = { nombreCompleto, dirResidencia, telResidencia, telAlternativo, correoPrincipal, correoSecundario, carnet, sexo, fallecido, avatar, funcion };
        if (edit == true) {
            data.identificacion = $('#identificacion_hidden').val();
        }
        // Agregar título y fecha solo si se están creando
        if (edit == false && titulo && fechaGrado) {
            data.titulo = titulo;
            data.fechaGrado = fechaGrado;
        }

        $.post('../controlador/EgresadoController.php', data, (response) => {
            response = response.trim();
            if (response == 'add') {
                Swal.fire({
                    title: 'Egresado creado!',
                    text: 'El egresado ha sido creado exitosamente.',
                    icon: 'success'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else if (response == 'noadd') {
                Swal.fire({
                    title: 'Error!',
                    text: 'El egresado ya existe.',
                    icon: 'error'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else if (response == 'update') {
                Swal.fire({
                    title: 'Egresado actualizado!',
                    text: 'El egresado ha sido actualizado exitosamente.',
                    icon: 'success'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'No se pudo realizar la operación.',
                    icon: 'error'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            }
            $('#crear').modal('hide');
            tabla.ajax.reload(null, false);
        });
        e.preventDefault();
    });

    // Evento para agregar un título a un egresado
    $(document).on('click', '.titulo', function () {
        let data;
        if (tabla.row(this).child.isShown())
            data = tabla.row(this).data();
        else
            data = tabla.row($(this).parents("tr")).data();

        $('#egresado').val(data.identificacion);

        // Cargar títulos disponibles
        $.post('../controlador/AgregarTituloEgresadoController.php', { funcion: 'seleccionarTitulos' }, (response) => {
            let titulos = JSON.parse(response);
            $('#titulo').empty();
            titulos.forEach(titulo => {
                $('#titulo').append(`<option value="${titulo.id}">${titulo.nombre}</option>`);
            });
        });

        // Cargar título y fecha de graduación del egresado
        $.post('../controlador/AgregarTituloEgresadoController.php', { funcion: 'obtenerTituloEgresado', identificacion: data.identificacion }, (response) => {
            let tituloEgresado = JSON.parse(response);
            if (tituloEgresado) {
                $('#titulo').val(tituloEgresado.id);

                // Convertir la fecha de dd/mm/yyyy a yyyy-mm-dd
                let fechaArray = tituloEgresado.fechagrado.split('/');
                let fechaGrado = `${fechaArray[2]}-${fechaArray[1]}-${fechaArray[0]}`;

                $('#fechaGrado').val(fechaGrado);
            } else {
                $('#titulo').val('');
                $('#fechaGrado').val('');
            }
        });

        $('#modalTituloEgresado').modal('show');
    });

// Enviar el formulario para agregar un título a un egresado
$('#formTituloEgresado').submit(e => {
    let egresado = $('#egresado').val();
    let titulo = $('#titulo').val();
    let fechaGrado = $('#fechaGrado').val();

    funcion = 'agregarTitulo';
    $.post('../controlador/AgregarTituloEgresadoController.php', { egresado, titulo, fechaGrado, funcion }, (response) => {
        if (response.trim() === 'add') {
            Swal.fire({
                icon: 'success',
                title: 'Título agregado',
                text: 'El título se ha agregado correctamente',
            });
            $('#modalTituloEgresado').modal('hide');
            tabla.ajax.reload(null, false);
        } else if (response.trim() === 'existe') {
            Swal.fire({
                icon: 'error',
                title: 'Título ya asignado',
                text: 'Este título ya ha sido asignado a este egresado',
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo agregar el título',
            });
        }
    });
    e.preventDefault();
});




    // Evento para eliminar un egresado
    $(document).on('click', '.eliminar', function () {
        if (tabla.row(this).child.isShown()) {
            var data = tabla.row(this).data();
        } else {
            var data = tabla.row($(this).parents("tr")).data();
        }
        const id = data.identificacion;
        const nombre = data.nombreCompleto;
        buscar(id);
        funcion = 'eliminar';

        Swal.fire({
            title: 'Desea eliminar ' + nombre + '?',
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
                $.post('../controlador/EgresadoController.php', { id, funcion }, (response) => {
                    response = response.trim();
                    if (response == 'eliminado') {
                        Swal.fire(
                            'Eliminado!',
                            nombre + ' fue eliminado.',
                            'success'
                        );
                    }
                    else {
                        Swal.fire(
                            'No se pudo eliminar!',
                            nombre + ' está utilizado',
                            'error'
                        );
                    }
                    tabla.ajax.reload(null, false);
                });
            }
        });
    });

    // Cargar las opciones de egresados en el formulario de observaciones
    function cargar_egresados() {
        funcion = 'obtener_egresados';
        $.post('../controlador/ObservacionController.php', { funcion }, (response) => {
            const registros = JSON.parse(response);
            let template = '';
            registros.forEach(registro => {
                template += `<option value="${registro.identificacion}">${registro.nombreCompleto}</option>`;
            });
            $('#egresado').html(template);
        });
    }

    // Cargar las opciones de títulos en el formulario de agregar título
    function cargar_titulos() {
        funcion = 'seleccionar';
        $.post('../controlador/AgregarTituloController.php', { funcion }, (response) => {
            const registros = JSON.parse(response);
            let template = '';
            registros.forEach(registro => {
                template += `<option value="${registro.id}">${registro.nombre}</option>`;
            });
            $('#titulo').html(template);
        });
    }

    // Cargar las opciones de gestión en el formulario de crear/editar egresado
    function cargar_gestiones() {
        funcion = 'seleccionar';
        $.post('../controlador/GestionController.php', { funcion }, (response) => {
            const registros = JSON.parse(response);
            let template = '<option value="">Seleccione...</option>';
            registros.forEach(registro => {
                template += `<option value="${registro.id}">${registro.nombre}</option>`;
            });
            $('#idGestion').html(template);
            $('#edit_gestion').html(template);
        });
    }

    // Cargar las opciones de títulos en el formulario de crear egresado
    function cargar_titulos_form() {
        funcion = 'seleccionar';
        $.post('../controlador/AgregarTituloController.php', { funcion }, (response) => {
            const registros = JSON.parse(response);
            let template = '';
            registros.forEach(registro => {
                template += `<option value="${registro.id}">${registro.nombre}</option>`;
            });
            $('#titulo').html(template);
        });
    }
});
