<?php
/**
 * API: api-advisory-update.php
 * Actualizar asesoría - ADMIN
 * 
 * POST body (JSON):
 * - id (required)
 * - razon_social, cif, email_empresa, direccion, plan, user_id
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358200);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051358200);
}

global $pdo;

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    json_response("ko", "ID requerido", 4001358200);
}

$id = intval($data['id']);

try {
    // Verificar que existe
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        json_response("ko", "Asesoría no encontrada", 4041358200);
    }
    
    // Verificar CIF único (si se cambia)
    if (!empty($data['cif'])) {
        $stmt = $pdo->prepare("SELECT id FROM advisories WHERE cif = :cif AND id != :id AND deleted_at IS NULL");
        $stmt->execute([':cif' => $data['cif'], ':id' => $id]);
        if ($stmt->fetch()) {
            json_response("ko", "Ya existe otra asesoría con ese CIF", 4091358200);
        }
    }
    
    $fields = [];
    $params = [':id' => $id];
    
    $allowedFields = ['razon_social', 'cif', 'email_empresa', 'direccion', 'plan', 'user_id'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'plan' && !in_array($data[$field], ['basico', 'profesional', 'premium'])) {
                continue;
            }
            if ($field === 'user_id') {
                $fields[] = "$field = :$field";
                $params[":$field"] = !empty($data[$field]) ? intval($data[$field]) : null;
            } else {
                $fields[] = "$field = :$field";
                $params[":$field"] = trim($data[$field]);
            }
        }
    }
    
    if (empty($fields)) {
        json_response("ko", "No hay campos para actualizar", 4001358200);
    }
    
    $fields[] = "updated_at = NOW()";
    
    $sql = "UPDATE advisories SET " . implode(', ', $fields) . " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    json_response("ok", "Asesoría actualizada correctamente", 9200002000);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-update: " . $e->getMessage());
    json_response("ko", "Error interno", 9500002000);
}