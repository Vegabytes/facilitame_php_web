<?php
// Controlador dual: facturas para clientes Y asesorías
$currentPage = 'advisory-invoices';
global $pdo;

// CLIENTES: Enviar facturas a su asesoría
if (cliente()) {
    // Verificar asesoría vinculada
    $stmt = $pdo->prepare("
        SELECT a.id, a.plan 
        FROM customers_advisories ca
        INNER JOIN advisories a ON ca.advisory_id = a.id
        WHERE ca.customer_id = ?
    ");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    $customer_advisory_id = $advisory ? $advisory['id'] : null;
    $can_send = ($advisory && $advisory['plan'] !== 'gratuito');
    
    $tags = [
        'restaurante' => 'Restaurante',
        'gasolina' => 'Gasolina',
        'proveedores' => 'Proveedores',
        'material_oficina' => 'Material de oficina',
        'viajes' => 'Viajes',
        'servicios' => 'Servicios',
        'otros' => 'Otros'
    ];
    
    $info = compact('customer_advisory_id', 'can_send', 'tags');
    return; // Carga /view/advisory-invoices.php
}

// ASESORÍAS: Ver facturas recibidas
if (asesoria()) {
    // Redirigir a /invoices (ya existe la vista)
    header("Location: /invoices");
    exit;
}

// Si no es cliente ni asesoría
header("Location: /home");
exit;