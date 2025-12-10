<?php
// controller/request.php (optimizado)

if (!admin() && !proveedor() && !asesoria() && !comercial() && !cliente()) {
    header("Location:home?r=2724205720");
    exit;
}

global $pdo;
$request_id = IS_MOBILE_APP ? intval($_POST["id"]) : intval($_GET["id"]);
$read_only = false;
$advisory_id = 0;

// =============================================
// BASE SQL - Común para todos los roles
// =============================================
$base_select = "
    SELECT 
        req.*,
        rs.status_name AS status_name,
        cat.id AS cat_id, cat.name AS category_name, cat.phone AS category_phone,
        cat.parent_id AS category_parent_id, cat.description AS category_description,
        u.id AS requestor_id, u.name AS requestor_name, u.lastname AS requestor_lastname,
        u.phone AS requestor_phone, u.email AS requestor_email, u.nif_cif AS requestor_nif_cif,
        CONCAT(sales_rep.name, ' ', COALESCE(sales_rep.lastname, '')) AS sales_rep_name,
        sales_rep.id AS sales_rep_id, sales_rep.email AS sales_rep_email,
        sales_rep.phone AS sales_rep_phone
    FROM requests req
    INNER JOIN categories cat ON cat.id = req.category_id
    INNER JOIN requests_statuses rs ON rs.id = req.status_id
    INNER JOIN users u ON u.id = req.user_id
";

$base_sales_join = "
    LEFT JOIN customers_sales_codes csc ON csc.customer_id = u.id
    LEFT JOIN sales_codes sc ON sc.id = csc.sales_code_id
    LEFT JOIN users sales_rep ON sales_rep.id = sc.user_id
";

// =============================================
// CONSTRUIR QUERY SEGÚN ROL
// =============================================
$params = [':request_id' => $request_id];

if (admin()) {
    $sql = $base_select . $base_sales_join . " WHERE req.id = :request_id";
    
} elseif (proveedor()) {
    $sql = $base_select . $base_sales_join . " 
        WHERE req.id = :request_id 
          AND req.category_id IN (" . USER["categories"] . ")";
    
} elseif (cliente()) {
    $sql = $base_select . $base_sales_join . " 
        WHERE req.id = :request_id 
          AND req.user_id = :user_id";
    $params[':user_id'] = (int) USER['id'];
    
} elseif (comercial()) {
    $read_only = true; // Solo para chat, no para comentarios internos
    $sql = $base_select . "
        INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id 
            AND sc.user_id = :user_id 
            AND sc.deleted_at IS NULL
        LEFT JOIN users sales_rep ON sales_rep.id = sc.user_id
        WHERE req.id = :request_id";
    $params[':user_id'] = (int) USER['id'];
    
} elseif (asesoria()) {
    // Verificar asesoría
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ? LIMIT 1");
    $stmt->execute([USER['id']]);
    $advisory_id = $stmt->fetchColumn();
    
    if (!$advisory_id) {
        header("Location:/home");
        exit;
    }
    
    $sql = $base_select . "
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id AND ca.advisory_id = :advisory_id
        " . $base_sales_join . "
        WHERE req.id = :request_id";
    $params[':advisory_id'] = $advisory_id;
}

// =============================================
// EJECUTAR QUERY
// =============================================
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch();

if (!$row) {
    set_toastr("ko", "No se puede mostrar.");
    header("Location:home");
    exit;
}

// =============================================
// EXTRAER DATOS
// =============================================
$request = $row;

$category = [
    'id' => $row['cat_id'],
    'name' => $row['category_name'],
    'phone' => guest() ? '' : $row['category_phone'],
    'parent_id' => $row['category_parent_id'],
    'description' => $row['category_description']
];

$requestor = [
    'id' => $row['requestor_id'],
    'name' => $row['requestor_name'],
    'lastname' => $row['requestor_lastname'],
    'phone' => $row['requestor_phone'],
    'email' => $row['requestor_email'],
    'nif_cif' => $row['requestor_nif_cif']
];

$sales_rep = !empty($row['sales_rep_id']) ? [
    'id' => $row['sales_rep_id'],
    'name' => trim($row['sales_rep_name']),
    'email' => $row['sales_rep_email'],
    'phone' => $row['sales_rep_phone']
] : [];

// =============================================
// DATOS ADICIONALES
// =============================================

// File types
$file_types_raw = $pdo->query("
    SELECT id, name FROM file_types 
    WHERE id > 0 AND deleted_at IS NULL 
    ORDER BY name ASC
")->fetchAll();
$file_types = $file_types_raw;
$file_types_kp = array_column($file_types_raw, 'name', 'id');

// Form values
$form_values = json_decode($request["form_values"], true);
if (!is_array($form_values)) { 
    $form_values = json_decode($form_values, true) ?: [];
}
$form_values = array_filter($form_values, 'is_array');

// Datos relacionados
$offers = get_offers($request_id);
$messages = get_messages($request_id);
$documents = get_documents($request_id);
$incidents = get_incidents_by_request($request_id);
$commissions = get_commissions();

// Comentarios internos: admin, proveedor, comercial y asesoría pueden ver
// Cliente NO puede ver comentarios internos
$comments = cliente() ? '' : get_comments($request_id);

$offers_commissions = admin() ? get_offers_commissions($request_id) : [];

// =============================================
// EXPORTAR
// =============================================
$info = compact(
    "request",
    "category",
    "offers",
    "messages",
    "documents",
    "file_types",
    "file_types_kp",
    "form_values",
    "requestor",
    "comments",
    "commissions",
    "sales_rep",
    "offers_commissions",
    "incidents",
    "advisory_id",
    "read_only"
);