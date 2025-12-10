<?php
// Ver detalle de una cita específica
$currentPage = 'appointments';
global $pdo;

// Obtener ID de la cita
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    header("Location: /appointments");
    exit;
}

// ASESORÍAS: Ver detalle de cita de sus clientes
if (asesoria()) {
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        header("Location: /appointments");
        exit;
    }
    
    $advisory_id = $advisory['id'];
    
    // Obtener cita con información del cliente
    $stmt = $pdo->prepare("
        SELECT aa.*, 
               u.name as customer_name, 
               u.lastname as customer_lastname, 
               u.phone as customer_phone, 
               u.email as customer_email
        FROM advisory_appointments aa
        INNER JOIN users u ON aa.customer_id = u.id
        WHERE aa.id = ? AND aa.advisory_id = ?
    ");
    $stmt->execute([$appointment_id, $advisory_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        header("Location: /appointments");
        exit;
    }
    
    $departments = [
        'contabilidad' => 'Contabilidad',
        'fiscalidad' => 'Fiscalidad', 
        'laboral' => 'Laboral',
        'gestion' => 'Gestión'
    ];
    
    $types = [
        'llamada' => 'Llamada',
        'reunion_presencial' => 'Presencial',
        'reunion_virtual' => 'Virtual'
    ];
    
    $statuses = [
        'solicitado' => ['label' => 'Solicitado', 'class' => 'warning'],
        'agendado' => ['label' => 'Agendado', 'class' => 'info'],
        'finalizado' => ['label' => 'Finalizado', 'class' => 'success'],
        'cancelado' => ['label' => 'Cancelado', 'class' => 'danger']
    ];
    
    // Traducciones adicionales
    $typeTranslations = [
        'llamada' => 'Llamada telefónica',
        'reunion_presencial' => 'Reunión presencial',
        'reunion_virtual' => 'Videollamada'
    ];
    
    $departmentTranslations = [
        'contabilidad' => 'Contabilidad',
        'fiscalidad' => 'Fiscalidad',
        'laboral' => 'Laboral',
        'gestion' => 'Gestión'
    ];
    
    $timeTranslations = [
        'manana' => 'Por la mañana',
        'tarde' => 'Por la tarde',
        'especifico' => 'Hora específica'
    ];
    
    $info = compact('appointment', 'departments', 'types', 'statuses', 'typeTranslations', 'departmentTranslations', 'timeTranslations');
    return; // Carga /view/appointment.php por defecto
}

// CLIENTES: Ver detalle de sus propias citas
if (cliente()) {
    // Obtener cita del cliente con nombre de la asesoría
    $stmt = $pdo->prepare("
        SELECT aa.*, 
               adv.razon_social as advisory_name
        FROM advisory_appointments aa
        INNER JOIN advisories adv ON aa.advisory_id = adv.id
        WHERE aa.id = ? AND aa.customer_id = ?
    ");
    $stmt->execute([$appointment_id, USER['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        header("Location: /appointments");
        exit;
    }
    
    $statusTranslations = [
        'solicitado' => 'Pendiente',
        'agendado' => 'Agendada',
        'finalizado' => 'Finalizada',
        'cancelado' => 'Cancelada'
    ];
    
    $info = compact('appointment', 'statusTranslations');
    return; // Carga la vista de cliente
}

// Si no es cliente ni asesoría
header("Location: /home");
exit;