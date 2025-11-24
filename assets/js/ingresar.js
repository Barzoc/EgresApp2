$(document).ready(function () {
    var funcion;
    $('#login-form').submit(function (event){
        event.preventDefault();
        let email= $('#email').val();
        let contrasena = $('#contrasena').val();
        funcion = 'ingresar';

        $.post('./controlador/IngresarController.php', {email, contrasena, funcion}, (response) => {
            console.log(response);
            response = response.trim();
  
            if(response === 'ingresoExitoso'){ 
                window.location.href = './vista/inicio.php';
            } else {
                $('#error-alert').text('Las credenciales son incorrectas.').show().delay(5000).fadeOut();
            } 
        });
    });
});

