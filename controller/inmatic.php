<?php
/**
 * Controller: Inmatic
 * Solo accesible para usuarios de asesoría
 */

if (!asesoria()) {
    header("Location: /home");
    exit;
}

$info = [];
