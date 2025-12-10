<?php
if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $user_id = (int)USER["id"];
    
    // Obtener ID de la asesoría
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response('ok', '', 200, [
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 0,
                'per_page' => $limit,
                'from' => 0,
                'to' => 0
            ]
        ]);
        return;
    }
    
    $advisory_id = (int)$advisory["id"];
    
    $params = [$advisory_id];
    $whereConditions = ["aa.advisory_id = ?"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE ?
            OR aa.type LIKE ?
            OR aa.department LIKE ?
            OR aa.reason LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = intval($row['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos
    $dataQuery = "
        SELECT 
            aa.id,
            aa.type,
            aa.department,
            aa.preferred_time,
            aa.specific_time,
            aa.reason,
            aa.status,
            aa.scheduled_date,
            aa.notes_advisory,
            aa.created_at,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_name,
            u.email as customer_email,
            COALESCE(u.phone, '') as customer_phone
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        WHERE $whereClause
        ORDER BY 
            CASE aa.status
                WHEN 'solicitado' THEN 1
                WHEN 'agendado' THEN 2
                WHEN 'finalizado' THEN 3
                ELSE 4
            END,
            aa.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $appointments = $stmt->fetchAll();
    
    // Traducciones
    $typeTranslations = [
        'llamada' => 'Llamada telefónica',
        'reunion_presencial' => 'Reunión presencial',
        'reunion_virtual' => 'Videollamada',
        'reunion_vi' => 'Videollamada' // por si está truncado en el ENUM
    ];
    
    $departmentTranslations = [
        'contabilidad' => 'Contabilidad',
        'fiscalidad' => 'Fiscalidad',
        'laboral' => 'Laboral',
        'gestion' => 'Gestión',
        'ges' => 'Gestión' // por si está truncado
    ];
    
    $statusTranslations = [
        'solicitado' => 'Pendiente',
        'agendado' => 'Agendada',
        'finalizado' => 'Finalizada',
        'cancelado' => 'Cancelada',
        'canc' => 'Cancelada' // por si está truncado
    ];
    
    $timeTranslations = [
        'manana' => 'Por la mañana',
        'tarde' => 'Por la tarde',
        'especifico' => 'Hora específica'
    ];
    
    $formattedAppointments = [];
    foreach ($appointments as $apt) {
        $preferredTimeLabel = $timeTranslations[$apt['preferred_time']] ?? $apt['preferred_time'];
        if ($apt['preferred_time'] === 'especifico' && !empty($apt['specific_time'])) {
            $preferredTimeLabel .= ' (' . $apt['specific_time'] . ')';
        }
        
        $formattedAppointments[] = [
            'id' => $apt['id'],
            'customer_name' => trim($apt['customer_name']),
            'customer_email' => $apt['customer_email'] ?? '',
            'customer_phone' => $apt['customer_phone'] ?? '',
            'type' => $apt['type'] ?? '',
            'type_label' => $typeTranslations[$apt['type']] ?? $apt['type'],
            'department' => $apt['department'] ?? '',
            'department_label' => $departmentTranslations[$apt['department']] ?? $apt['department'],
            'preferred_time' => $apt['preferred_time'] ?? '',
            'preferred_time_label' => $preferredTimeLabel,
            'specific_time' => $apt['specific_time'] ?? '',
            'reason' => $apt['reason'] ?? '',
            'notes_advisory' => $apt['notes_advisory'] ?? '',
            'status' => $apt['status'] ?? 'solicitado',
            'status_label' => $statusTranslations[$apt['status']] ?? $apt['status'],
            'scheduled_date' => $apt['scheduled_date'] ? fdate($apt['scheduled_date']) : null,
            'created_at' => fdate($apt['created_at'])
        ];
    }
    
    $result = [
        'data' => $formattedAppointments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 200, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-requests-paginated: " . $e->getMessage() . " en línea " . $e->getLine());
    json_response("ko", "Error: " . $e->getMessage(), 500);
}