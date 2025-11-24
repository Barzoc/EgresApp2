// login_rut.js
// Valida RUT en la pantalla de login y consulta nombre asociado via validar.php

$(function(){
  // Crear el contenedor de información si no existe
  if (!$('.info-egresado-container').length) {
    $('.login-container').append('<div class="info-egresado-container"></div>');
  }

  function mostrarMensaje(text, tipo='danger'){
    let $err = $('#rut-error');
    $err.removeClass('text-success text-danger');
    $err.addClass(tipo === 'success' ? 'text-success' : 'text-danger');
    $err.text(text).show();
  }

  function doValidarRut(){
    const rut = $('#rut_login').val().trim();
    if (!rut) {
      $('.info-egresado-container').removeClass('visible').empty();
      mostrarMensaje('Ingrese un RUT para validar');
      return;
    }
    $('#rut-error').hide();
    $.ajax({
      url: 'validar.php',
      method: 'POST',
      dataType: 'json',
      data: { rut: rut },
      success: function(res){
        if (!res.success) {
          mostrarMensaje(res.message || 'Error desconocido');
          $('#nombre_rut').text('');
          return;
        }
        if (!res.valid) {
          mostrarMensaje('RUT inválido');
          $('#nombre_rut').text('');
          return;
        }
        // RUT válido
        if (res.nombre) {
          let html = `<div class="info-egresado">
            <h5 class="mb-3"><i class="fas fa-user"></i> <strong>${res.nombre}</strong></h5>
            
            <div class="info-section">`;
          
          if (res.direccion) {
            html += `<p><i class="fas fa-home"></i> <span class="label">Dirección:</span> ${res.direccion}</p>`;
          }
          
          // Sección de correos
          let correosHtml = '';
          if (res.correo) {
            correosHtml += `<p><i class="fas fa-envelope"></i> <span class="label">Correo principal:</span> ${res.correo}</p>`;
          }
          if (res.correo_secundario) {
            correosHtml += `<p><i class="fas fa-envelope-open"></i> <span class="label">Correo secundario:</span> ${res.correo_secundario}</p>`;
          }
          if (correosHtml) {
            html += correosHtml;
          }
          
          // Sección de títulos
          const tituloPrincipal = res.titulo_obtenido || (res.titulos && res.titulos.length > 0 ? res.titulos[0].nombre : (res.titulo || ''));

          if (tituloPrincipal) {
            html += `<p><i class="fas fa-graduation-cap"></i> <span class="label">Título principal:</span> ${tituloPrincipal}</p>`;
          }

          if (res.titulos && res.titulos.length > 0) {
            html += `<div class="titulos-section mt-2">
              <p><i class="fas fa-graduation-cap"></i> <span class="label">Títulos académicos:</span></p>
              <ul>`;
            res.titulos.forEach(titulo => {
              html += `<li>${titulo.nombre}${titulo.fecha ? 
                ` <small class="text-muted">(${titulo.fecha})</small>` : 
                ''}</li>`;
            });
            html += `</ul>
            </div>`;
          }
          
          // Si hay títulos, añadir botón para generar certificado
          if (tituloPrincipal) {
            // botón con id para enlazar handler después de insertar HTML
            html += `<div class="mt-2"><button id="btn_generar_cert" class="btn btn-sm btn-success"><i class="fas fa-file-pdf"></i> Generar Certificado</button></div>`;
          }
          html += `</div></div>`;
          $('.info-egresado-container').html(html).addClass('visible');
          $('#rut-error').hide();
          // Enlazar handler al botón de generar certificado si existe
          if ($('#btn_generar_cert').length) {
            const tituloObj = (res.titulos && res.titulos.length>0) ? res.titulos[0] : null;
            const genData = {
              nombre: res.nombre || '',
              titulo: res.titulo_obtenido || (tituloObj ? tituloObj.nombre : (res.titulo || '')),
              fechaTitulo: tituloObj ? tituloObj.fecha : (res.fechaTitulo || res.fechaTituloPrincipal || ''),
              numeroRegistro: res.numeroRegistro || ''
            };

            $('#btn_generar_cert').on('click', function(){
              if (window.generarCertificado) {
                window.generarCertificado(JSON.stringify(genData), $('#rut_login').val().trim());
              } else {
                console.warn('generarCertificado no está definido en el scope global');
              }
            });
          }
        } else {
          $('.info-egresado-container').removeClass('visible').empty();
          mostrarMensaje('RUT válido pero no se encontró registro', 'danger');
        }
      },
      error: function(xhr, status, err){
        mostrarMensaje('Error al conectar con el servidor');
        $('.info-egresado-container').removeClass('visible').empty();
      }
    });
  }

  // Click en el botón Validar
  $('#validate_rut_btn').on('click', function(){
    doValidarRut();
  });

  // Permitir Enter en el input para disparar la validación
  $('#rut_login').on('keypress', function(e){
    if (e.which === 13) { // Enter
      e.preventDefault();
      doValidarRut();
    }
  });
});
