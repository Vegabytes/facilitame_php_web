<?php
global $pdo; 
// Formulario para solicitar cita con la asesoría
if (!cliente()) {
    header("Location: /login");
    exit;
}

// Verificar si tiene asesoría vinculada
$customer_advisory_id = get_customer_advisory_id(USER['id']);

if (!$customer_advisory_id) {
    header("Location: /appointments");
    exit;
}

// Obtener datos de la asesoría
$stmt = $pdo->prepare("SELECT razon_social FROM advisories WHERE id = ?");
$stmt->execute([$customer_advisory_id]);
$advisory = $stmt->fetch();

$info = compact('customer_advisory_id', 'advisory');