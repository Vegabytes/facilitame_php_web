<?php
// Destruir la cookie de autenticación
if (isset($_COOKIE['auth_token']))
{
    unset($_COOKIE['auth_token']);
    setcookie('auth_token', '', time() - 3600, '/'); // Establecer la cookie con una fecha de expiración en el pasado
}

set_toastr("ok", "Sesión cerrada correctamente");

json_response("ok", "Sesión cerrada correctamente", 4059654054);
