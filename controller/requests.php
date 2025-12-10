<?php
global $pdo;

if (!asesoria()) {
    set_toastr("ko", "Acceso denegado");
    header("Location: /home");
    exit;
}

// Obtener el ID de la asesoría del usuario logueado
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    set_toastr("ko", "No tienes una asesoría asociada");
    header("Location: /home");
    exit;
}

$advisory_id = $advisory['id'];

// Obtener filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Query base
$sql = "
    SELECT 
        aa.*,
        u.name,
        u.lastname,
        u.email,
        u.phone
    FROM advisory_appointments aa
    INNER JOIN users u ON u.id = aa.customer_id
    WHERE aa.advisory_id = ?
";

$params = [$advisory_id];

// Aplicar filtro de estado
if ($status_filter !== 'all') {
    $sql .= " AND aa.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY aa.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Contar por estados
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM advisory_appointments 
    WHERE advisory_id = ? 
    GROUP BY status
");
$stmt->execute([$advisory_id]);
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Traducciones
$typeTranslations = [
    'llamada' => 'Llamada telefónica',
    'reunion_presencial' => 'Reunión presencial',
    'reunion_virtual' => 'Videollamada',
    'reunion_vi' => 'Videollamada'
];

$departmentTranslations = [
    'contabilidad' => 'Contabilidad',
    'fiscalidad' => 'Fiscalidad',
    'laboral' => 'Laboral',
    'gestion' => 'Gestión',
    'ges' => 'Gestión'
];

$statusTranslations = [
    'solicitado' => 'Pendiente',
    'agendado' => 'Agendada',
    'finalizado' => 'Finalizada',
    'cancelado' => 'Cancelada',
    'canc' => 'Cancelada'
];

$info = [
    'appointments' => $appointments,
    'advisory_id' => $advisory_id,
    'status_filter' => $status_filter,
    'status_counts' => $status_counts,
    'typeTranslations' => $typeTranslations,
    'departmentTranslations' => $departmentTranslations,
    'statusTranslations' => $statusTranslations
];