<?php
/**
 * Controller: Configuración de Menús
 * Solo accesible para administradores
 */

if (!admin()) {
    header("Location: /home");
    exit;
}

$info = [];
