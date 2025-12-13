<?php
require_once __DIR__ . "/../bold/functions.php";

// Solo admin puede acceder
if (!admin()) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

global $pdo;

// Definición de todos los items de menú por rol
$MENU_ITEMS = [
    'cliente' => [
        ['key' => 'home', 'label' => 'Inicio', 'icon' => 'ki-home', 'section' => 'Principal'],
        ['key' => 'notifications', 'label' => 'Notificaciones', 'icon' => 'ki-notification-bing', 'section' => 'Principal'],
        ['key' => 'services', 'label' => 'Buscar servicios', 'icon' => 'ki-search-list', 'section' => 'Principal'],
        ['key' => 'my-services', 'label' => 'Mis solicitudes', 'icon' => 'ki-folder', 'section' => 'Mis Gestiones'],
        ['key' => 'my-incidents', 'label' => 'Mis incidencias', 'icon' => 'ki-information-5', 'section' => 'Mis Gestiones'],
        ['key' => 'invoices', 'label' => 'Mis Recibos', 'icon' => 'ki-credit-cart', 'section' => 'Mis Gestiones'],
        ['key' => 'communications', 'label' => 'Comunicaciones', 'icon' => 'ki-sms', 'section' => 'Mi Asesoría'],
        ['key' => 'advisory-invoices', 'label' => 'Facturas', 'icon' => 'ki-document', 'section' => 'Mi Asesoría'],
        ['key' => 'appointments', 'label' => 'Solicitar Cita', 'icon' => 'ki-calendar', 'section' => 'Mi Asesoría'],
    ],
    'administrador' => [
        ['key' => 'home', 'label' => 'Inicio', 'icon' => 'ki-home', 'section' => 'Principal'],
        ['key' => 'log', 'label' => 'Seguimiento', 'icon' => 'ki-time', 'section' => 'Principal'],
        ['key' => 'customers', 'label' => 'Clientes', 'icon' => 'ki-people', 'section' => 'Gestión'],
        ['key' => 'users-sales', 'label' => 'Comerciales', 'icon' => 'ki-briefcase', 'section' => 'Gestión'],
        ['key' => 'users-providers', 'label' => 'Colaboradores', 'icon' => 'ki-handcart', 'section' => 'Gestión'],
        ['key' => 'advisories', 'label' => 'Asesorías', 'icon' => 'ki-chart', 'section' => 'Gestión'],
        ['key' => 'commissions', 'label' => 'Comisiones', 'icon' => 'ki-dollar', 'section' => 'Gestión'],
    ],
    'proveedor' => [
        ['key' => 'home', 'label' => 'Inicio', 'icon' => 'ki-home', 'section' => 'Principal'],
        ['key' => 'log', 'label' => 'Seguimiento', 'icon' => 'ki-time', 'section' => 'Principal'],
        ['key' => 'customers', 'label' => 'Clientes', 'icon' => 'ki-people', 'section' => 'Gestión'],
        ['key' => 'invoices', 'label' => 'Facturas', 'icon' => 'ki-credit-cart', 'section' => 'Gestión'],
    ],
    'comercial' => [
        ['key' => 'home', 'label' => 'Inicio', 'icon' => 'ki-home', 'section' => 'Principal'],
        ['key' => 'notifications', 'label' => 'Notificaciones', 'icon' => 'ki-notification-bing', 'section' => 'Principal'],
        ['key' => 'log', 'label' => 'Seguimiento', 'icon' => 'ki-time', 'section' => 'Principal'],
        ['key' => 'customers', 'label' => 'Clientes', 'icon' => 'ki-people', 'section' => 'Gestión'],
        ['key' => 'commissions', 'label' => 'Comisiones', 'icon' => 'ki-dollar', 'section' => 'Gestión'],
    ],
    'asesoria' => [
        ['key' => 'home', 'label' => 'Inicio', 'icon' => 'ki-home', 'section' => 'Principal'],
        ['key' => 'notifications', 'label' => 'Notificaciones', 'icon' => 'ki-notification-bing', 'section' => 'Principal'],
        ['key' => 'customers', 'label' => 'Mis Clientes', 'icon' => 'ki-people', 'section' => 'Gestión'],
        ['key' => 'invoices', 'label' => 'Facturas Recibidas', 'icon' => 'ki-document', 'section' => 'Gestión'],
        ['key' => 'appointments', 'label' => 'Citas', 'icon' => 'ki-calendar', 'section' => 'Gestión'],
        ['key' => 'communications', 'label' => 'Comunicaciones', 'icon' => 'ki-sms', 'section' => 'Gestión'],
        ['key' => 'inmatic', 'label' => 'Inmatic', 'icon' => 'ki-cloud', 'section' => 'Integraciones'],
    ],
];

$ROLE_IDS = [
    'cliente' => 1,        // particular, autonomo, empresa usan el mismo
    'administrador' => 2,
    'proveedor' => 4,
    'comercial' => 7,
    'asesoria' => 8,
];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Obtener configuración actual
    $role = $_GET['role'] ?? '';

    if (!$role || !isset($MENU_ITEMS[$role])) {
        echo json_encode(["status" => "error", "message" => "Rol no válido"]);
        exit;
    }

    $role_id = $ROLE_IDS[$role];

    // Obtener configuración de BD
    $stmt = $pdo->prepare("SELECT menu_key, is_visible, display_order FROM menu_config WHERE role_id = ?");
    $stmt->execute([$role_id]);
    $dbConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Indexar por menu_key
    $config = [];
    foreach ($dbConfig as $row) {
        $config[$row['menu_key']] = $row;
    }

    // Construir respuesta con valores por defecto
    $items = [];
    $order = 0;
    foreach ($MENU_ITEMS[$role] as $item) {
        $key = $item['key'];
        $items[] = [
            'key' => $key,
            'label' => $item['label'],
            'icon' => $item['icon'],
            'section' => $item['section'],
            'is_visible' => isset($config[$key]) ? (bool)$config[$key]['is_visible'] : true,
            'display_order' => isset($config[$key]) ? (int)$config[$key]['display_order'] : $order,
        ];
        $order++;
    }

    // Ordenar por display_order
    usort($items, fn($a, $b) => $a['display_order'] - $b['display_order']);

    echo json_encode([
        "status" => "ok",
        "data" => [
            "role" => $role,
            "role_id" => $role_id,
            "items" => $items
        ]
    ]);

} elseif ($method === 'POST') {
    // Guardar configuración
    $input = json_decode(file_get_contents('php://input'), true);

    $role = $input['role'] ?? '';
    $items = $input['items'] ?? [];

    if (!$role || !isset($ROLE_IDS[$role])) {
        echo json_encode(["status" => "error", "message" => "Rol no válido"]);
        exit;
    }

    $role_id = $ROLE_IDS[$role];

    try {
        $pdo->beginTransaction();

        // Eliminar configuración anterior
        $stmt = $pdo->prepare("DELETE FROM menu_config WHERE role_id = ?");
        $stmt->execute([$role_id]);

        // Insertar nueva configuración
        $stmt = $pdo->prepare("INSERT INTO menu_config (role_id, menu_key, is_visible, display_order) VALUES (?, ?, ?, ?)");

        foreach ($items as $order => $item) {
            $stmt->execute([
                $role_id,
                $item['key'],
                $item['is_visible'] ? 1 : 0,
                $order
            ]);
        }

        $pdo->commit();

        echo json_encode(["status" => "ok", "message" => "Configuración guardada"]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Método no soportado"]);
}
