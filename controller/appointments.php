<?php
// Controlador dual: appointments para clientes Y asesorías
$currentPage = 'appointments';
global $pdo;



// CLIENTES: Ver mis citas
if (cliente()) {
    $customer_advisory_id = get_customer_advisory_id(USER['id']);
    $statusTranslations = [
        'solicitado' => 'Pendiente',
        'agendado' => 'Agendada',
        'finalizado' => 'Finalizada',
        'cancelado' => 'Cancelada'
    ];
    $info = compact('customer_advisory_id', 'statusTranslations');
    return; // Carga la vista de cliente
}

// ASESORÍAS: Gestionar citas de sus clientes
if (asesoria()) {
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        echo '<div class="alert alert-danger m-5">Asesoría no encontrada.</div>';
        return;
    }
    
    $advisory_id = $advisory['id'];
    
    // Obtener citas
    $stmt = $pdo->prepare("
        SELECT aa.*, u.name as customer_name, u.lastname as customer_lastname, u.phone, u.email
        FROM advisory_appointments aa
        INNER JOIN users u ON aa.customer_id = u.id
        WHERE aa.advisory_id = ?
        ORDER BY 
            CASE aa.status 
                WHEN 'solicitado' THEN 1 
                WHEN 'agendado' THEN 2 
                ELSE 3 
            END,
            aa.created_at DESC
    ");
    $stmt->execute([$advisory_id]);
    $appointments = $stmt->fetchAll();
    
    // Obtener clientes para el selector
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.lastname 
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        WHERE ca.advisory_id = ?
        ORDER BY u.name, u.lastname
    ");
    $stmt->execute([$advisory_id]);
    $clients = $stmt->fetchAll();
    
    $pending = array_filter($appointments, fn($a) => $a['status'] === 'solicitado');
    $scheduled = array_filter($appointments, fn($a) => $a['status'] === 'agendado');
    
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
    
    $info = compact('advisory_id', 'appointments', 'clients', 'pending', 'scheduled', 'departments', 'types', 'statuses');
    return; // Carga la vista de asesoría
}

// Si no es ni cliente ni asesoría
