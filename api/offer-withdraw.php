<?php
// Validación de parámetros
$offer_id = filter_input(INPUT_POST, 'offer_id', FILTER_VALIDATE_INT);
$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

if (!$offer_id || !$request_id) {
    json_response("ko", "Parámetros inválidos", 829621510);
}

if (!proveedor() && !admin()) {
    json_response("ko", "No puedes retirar ofertas", 829621513);
}

if (!offer_belongs_to_provider($offer_id)) {
    json_response("ko", "No puedes retirar esta oferta", 850434905);
}

try {
    $pdo->beginTransaction();
    
    // Soft delete de la oferta (1 query)
    $stmt = $pdo->prepare("
        UPDATE offers 
        SET deleted_at = CURRENT_TIMESTAMP() 
        WHERE id = ? 
          AND request_id = ? 
          AND deleted_at IS NULL
    ");
    $stmt->execute([$offer_id, $request_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("La oferta ya fue retirada o no existe");
    }
    
    app_log("offer", $offer_id, "delete", "request", $request_id);
    
    // Si no quedan ofertas disponibles, volver a estado "Iniciado" (1 query condicional)
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET status_id = 1 
        WHERE id = ? 
          AND status_id = 2
          AND NOT EXISTS (
              SELECT 1 FROM offers 
              WHERE request_id = ? 
                AND deleted_at IS NULL 
                AND status_id = 2
              LIMIT 1
          )
    ");
    $stmt->execute([$request_id, $request_id]);
    
    if ($stmt->rowCount() > 0) {
        app_log("request", $request_id, "status_initial", "request", $request_id);
    }
    
    $pdo->commit();
    set_toastr("ok", "Oferta retirada correctamente.");
    json_response("ok", "", 3689810648);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response("ko", DEBUG ? $e->getMessage() : "Ha ocurrido un error", 245147184);
}