<?php
/**
 * API: Reportar incidencia
 * POST /api/incident-report
 *
 * Permitido para: clientes (dueño de la solicitud) y comerciales (del cliente)
 */

// Verificar que el usuario tiene acceso (solo clientes pueden reportar incidencias)
if (!cliente() && !admin()) {
    json_response("ko", "No tienes permisos para comunicar incidencias.", 1598357390);
}

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes comunicar una incidencia en esta solicitud.", 1598357393);
}

// Comprobar que la solicitud está en un estado válido para reportar incidencias
// Estados válidos: 3 (Aceptada), 4 (En curso), 7 (Activa)
$query = "SELECT * FROM `requests` WHERE 1
AND deleted_at IS NULL
AND id = :request_id
AND status_id IN (3, 4, 7)";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1) {
    json_response("ko", "No se puede reportar una incidencia en esta solicitud. Solo es posible en solicitudes activas o en curso.", 2408915345);
}

// Comprobar que hay detalles :: inicio
if (empty($_POST["incident_details"])) {
    json_response("ko", "Debes detallar la incidencia.", 3616553444);
}
// Comprobar que hay detalles :: fin

try {
    $pdo->beginTransaction();

    $query = "INSERT INTO `request_incidents` SET
    request_id = :request_id,
    incident_category_id = :incident_category_id,
    details = :details,
    created_by = :created_by";    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->bindValue(":incident_category_id", $_POST["incident_category_id"]);
    $stmt->bindValue(":details", htmlspecialchars($_POST["incident_details"], ENT_QUOTES, 'UTF-8'));    
    $stmt->bindValue(":created_by", USER["id"]);
    $stmt->execute();
    $incident_id = $pdo->lastInsertId();

    app_log("incident", $incident_id, "report", "request", $_POST["request_id"]);

    $request = get_request($_POST["request_id"]);
    $provider = get_request_provider($_POST["request_id"]);
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);

    $sender_id = USER["id"];
    $title = "Incidencia comunicada";
    $message = USER["name"] . " " . USER["lastname"] . " ha comunicado una incidencia";

    // Notificar al proveedor
    notification(
        $sender_id,
        $provider["id"],
        $_POST["request_id"],
        $title,
        $message
    );

    // Notificar al admin
    notification(
        $sender_id,
        ADMIN_ID,
        $_POST["request_id"],
        $title,
        $message
    );

    // Notificar al comercial si existe
    if ($sales_rep_id) {
        notification(
            $sender_id,
            $sales_rep_id,
            $_POST["request_id"],
            $title,
            $message
        );
    }

    $pdo->commit();
    
    define("BYPASS", true);
    $_POST["comment"] = "---- INCIDENCIA NOTIFICADA ----\n" . htmlspecialchars($_POST["incident_details"], ENT_QUOTES, 'UTF-8');
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Incidencia comunicada<br><br>Nuestro equipo la atenderá en breve y contactaremos de nuevo contigo.", 926331890);
}
catch (Exception $e) {
    $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 3453253497);
    }
    json_response("ko", "No se ha podido comunicar la incidencia. Inténtalo de nuevo, por favor.", 1135801050);
}
?>
