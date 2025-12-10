<?php
/**
 * Controller: Notificaciones
 * - Cliente, Comercial y Asesoría: Vista paginada (datos vía API)
 */
if (comercial() || cliente() || asesoria()) {
    // Todos usan vista paginada - datos vienen por API
    $info = [];

} else {
    // Otros roles no tienen acceso
    header("Location:home?r=2724205720");
    exit;
}