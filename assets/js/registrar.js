$(document).ready(function () {
    var funcion;

    $('#boton_registrar').click(function (event) {
        event.preventDefault(); // Prevenir el comportamiento por defecto del formulario

        let nombre = $('#nombre').val();
        let email = $('#email').val();
        let contrasena = $('#contrasena').val();
        let confirmar_contrasena = $('#confirmar_contrasena').val();
        funcion = 'registrar';


        // Validación para campos vacíos
        if (!contrasena) {
            $('#error-alert').text('El campo de la contraseña no puede estar vacío.').show().delay(3000).fadeOut();
            return;
        }

        if (contrasena !== confirmar_contrasena) {
            $('#error-alert').text('Las contraseñas no coinciden.').show().delay(3000).fadeOut();
            return;
        }

        if (!emailIsValid(email)) {
            $('#error-alert').text('El correo es inválido.').show().delay(3000).fadeOut();
            return;
        }

        // Verificar que el nombre no esté vacío
        if (!nombre) {
            $('#error-alert').text('El campo de nombre no puede estar vacío.').show().delay(3000).fadeOut();
            return;
        }

        // Confirmar que todos los datos sean válidos antes de proceder con la solicitud
        console.log("Datos validados correctamente, realizando la solicitud...");

        $.post('../controlador/RegistrarController.php', {nombre, email, contrasena, funcion}, (response) => {
            console.log("Respuesta del servidor: ", response);
            response = response.trim();

            if(response === 'passwordVacia') {
                $('#error-alert').text('El campo de la contraseña no puede estar vacío.').show().delay(3000).fadeOut();
            } else if(response === 'yaRegistrado'){ 
                $('#error-alert').text('El usuario ya está registrado.').show().delay(3000).fadeOut();
            } else if(response === 'error'){ 
                $('#error-alert').text('Hubo un error al registrar el usuario.').show().delay(3000).fadeOut();
            } else {
                $('#success-alert').text('Usuario creado con éxito.').show().delay(3000).fadeOut(400, function () {
                    window.location.href = '../index.php'; // Redirige al login después de mostrar el mensaje
                });
            } 
        });
    });

    function emailIsValid(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
