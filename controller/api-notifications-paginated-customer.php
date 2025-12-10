<?php
/**
 * API: Notificaciones paginadas para cliente
 * Endpoint: /api/notifications-paginated-customer
 * 
 * CORREGIDO: Filtrar por n.receiver_id (destinatario) en lugar de req.user_id (dueño solicitud)
 */

if (!cliente()) {
    json_response("ko", "No autorizado", 4031358201);
}

// Helper para tiempo relativo si no existe
if (!function_exists('time_from')) {
    function time_from($datetime) {
        if (empty($datetime)) return '-';
        
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Hace un momento';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "Hace {$mins} " . ($mins == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Hace {$hours} " . ($hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "Hace {$days} " . ($days == 1 ? 'día' : 'días');
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return "Hace {$weeks} " . ($weeks == 1 ? 'semana' : 'semanas');
        } else {
            return date('d/m/Y', $time);
        }
    }
}

global $pdo;

$user_id = (int) USER['id'];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$offset = ($page - 1) * $limit;

try {
    // Construir WHERE - CORREGIDO: filtrar por receiver_id (destinatario de la notificación)
    $whereConditions = ["n.receiver_id = :user_id"];
    $params = [':user_id' => $user_id];
    
    // Filtro de búsqueda
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            cat.name LIKE :search1
            OR sta.status_name LIKE :search2
            OR DATE_FORMAT(n.created_at, '%d/%m/%Y') LIKE :search3
            OR CAST(req.id AS CHAR) LIKE :search4
            OR n.title LIKE :search5
            OR n.description LIKE :search6
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
        $params[':search6'] = $searchTerm;
    }
    
    // Filtro de estado (leído/no leído)
    if ($statusFilter !== '') {
        $whereConditions[] = "n.status = :status_filter";
        $params[':status_filter'] = intval($statusFilter);
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Query de conteo total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM notifications n
        INNER JOIN requests req ON req.id = n.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $row = $stmt->fetch();
    $totalRecords = $row ? intval($row['total']) : 0;
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;

    // Contar no leídas - CORREGIDO: también por receiver_id
    $unreadQuery = "
        SELECT COUNT(*) as total
        FROM notifications n
        WHERE n.receiver_id = :user_id
        AND n.status = 0
    ";
    $stmtUnread = $pdo->prepare($unreadQuery);
    $stmtUnread->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmtUnread->execute();
    $rowUnread = $stmtUnread->fetch();
    $unreadCount = $rowUnread ? intval($rowUnread['total']) : 0;
    
    // Query principal
    $dataQuery = "
        SELECT 
            n.id AS notification_id,
            n.status AS notification_status,
            n.created_at AS notification_created_at,
            n.title AS notification_title,
            n.description AS notification_description,
            req.id,
            req.id AS request_id,
            req.status_id,
            cat.name AS category_name,
            sta.status_name AS status
        FROM notifications n
        INNER JOIN requests req ON req.id = n.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
        ORDER BY n.status ASC, n.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    // Formatear respuesta
    $formattedNotifications = [];
    foreach ($notifications as $notif) {
        $formattedNotifications[] = [
            'id' => $notif['id'],
            'notification_id' => $notif['notification_id'],
            'notification_status' => $notif['notification_status'],
            'notification_title' => $notif['notification_title'] ?? 'Notificación',
            'notification_description' => $notif['notification_description'] ?? '',
            'request_id' => $notif['request_id'],
            'category_name' => $notif['category_name'] ?? '',
            'status' => $notif['status'] ?? '',
            'status_id' => $notif['status_id'],
            'time_from' => time_from($notif['notification_created_at']),
            'created_at' => $notif['notification_created_at'] ? date('d/m/Y H:i', strtotime($notif['notification_created_at'])) : '-',
            'is_unread' => ($notif['notification_status'] == 0),
        ];
    }
    
    json_response("ok", "", 9200101000, [
        'data' => $formattedNotifications,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords),
            'unread_count' => $unreadCount
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-notifications-paginated-customer: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500101000);
}