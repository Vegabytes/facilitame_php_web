<?php
if (!admin()) {
    header("HTTP/1.1 404");
    exit;
}

try {
    $sales_rep_id = isset($_POST["sales_rep_id"]) ? (int)$_POST["sales_rep_id"] : 0;
    $commission_percentage = isset($_POST["commission_percentage"]) ? (float)$_POST["commission_percentage"] : 20.00;
    $point_value = isset($_POST["point_value"]) ? (float)$_POST["point_value"] : 20.00;

    if ($sales_rep_id <= 0) {
        json_response("ko", "ID de comercial inválido", 2847561023);
    }

    // Validar que el porcentaje esté entre 0 y 100
    if ($commission_percentage < 0 || $commission_percentage > 100) {
        json_response("ko", "El porcentaje debe estar entre 0 y 100", 3948572016);
    }

    // Validar que el valor por punto sea positivo
    if ($point_value < 0) {
        json_response("ko", "El valor por punto no puede ser negativo", 5829371046);
    }

    // Verificar que el usuario es un comercial (rol 7)
    $stmt = $pdo->prepare("SELECT mhr.role_id FROM model_has_roles mhr WHERE mhr.model_id = :user_id AND mhr.role_id = 7");
    $stmt->bindValue(":user_id", $sales_rep_id);
    $stmt->execute();
    if (!$stmt->fetch()) {
        json_response("ko", "El usuario no es un comercial", 6738291054);
    }

    // Actualizar sales_codes
    $stmt = $pdo->prepare("UPDATE sales_codes SET commission_percentage = :commission_percentage, point_value = :point_value WHERE user_id = :sales_rep_id AND deleted_at IS NULL");
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->bindValue(":commission_percentage", $commission_percentage);
    $stmt->bindValue(":point_value", $point_value);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        json_response("ko", "No se encontró el código de comercial", 7849261035);
    }

    app_log("customer", $sales_rep_id, "sales_rep_update_commissions");

    json_response("ok", "Configuración de comisiones actualizada", 8947361025);
} catch (Throwable $e) {
    if (defined('DEBUG') && DEBUG) {
        json_response("ko", $e->getMessage(), 9847261034);
    }
    json_response("ko", "Error al actualizar la configuración", 9847261034);
}
