<?php
/**
 * API: Actualizar asesoría (Admin)
 * POST /api/advisories-update
 *
 * Parámetros POST:
 * - id (required): ID de la asesoría
 * - razon_social, cif, email_empresa, direccion, telefono, plan, estado
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031359200);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051359200);
}

global $pdo;

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    json_response("ko", "ID de asesoría no válido", 4001359200);
}

try {
    // Verificar que existe
    $stmt = $pdo->prepare("SELECT id, cif FROM advisories WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute([':id' => $id]);
    $advisory = $stmt->fetch();

    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 4041359200);
    }

    // Verificar CIF único si se cambia
    if (!empty($_POST['cif']) && $_POST['cif'] !== $advisory['cif']) {
        $newCif = strtoupper(trim($_POST['cif']));

        // Validar formato CIF
        if (!preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $newCif)) {
            json_response("ko", "Formato de CIF no válido", 4001359201);
        }

        $stmt = $pdo->prepare("SELECT id FROM advisories WHERE cif = :cif AND id != :id AND deleted_at IS NULL");
        $stmt->execute([':cif' => $newCif, ':id' => $id]);
        if ($stmt->fetch()) {
            json_response("ko", "Ya existe otra asesoría con ese CIF", 4091359200);
        }
    }

    // Validar email si se proporciona
    if (!empty($_POST['email_empresa']) && !filter_var($_POST['email_empresa'], FILTER_VALIDATE_EMAIL)) {
        json_response("ko", "Email de empresa no válido", 4001359202);
    }

    // Campos permitidos
    $allowedFields = ['razon_social', 'cif', 'email_empresa', 'direccion', 'telefono', 'plan', 'estado', 'user_id'];
    $allowedPlans = ['gratuito', 'basic', 'estandar', 'pro', 'premium', 'enterprise'];
    $allowedStates = ['pendiente', 'activo', 'suspendido'];

    $fields = [];
    $params = [':id' => $id];
    $changes = [];

    foreach ($allowedFields as $field) {
        if (isset($_POST[$field])) {
            $value = trim($_POST[$field]);

            // Validaciones específicas
            if ($field === 'cif') {
                $value = strtoupper($value);
            } elseif ($field === 'email_empresa') {
                $value = strtolower($value);
            } elseif ($field === 'plan' && !in_array($value, $allowedPlans)) {
                continue;
            } elseif ($field === 'estado' && !in_array($value, $allowedStates)) {
                continue;
            } elseif ($field === 'user_id') {
                $value = !empty($value) ? intval($value) : null;
            }

            $fields[] = "$field = :$field";
            $params[":$field"] = $value;
            $changes[$field] = $value;
        }
    }

    if (empty($fields)) {
        json_response("ko", "No hay campos para actualizar", 4001359203);
    }

    $fields[] = "updated_at = NOW()";

    $sql = "UPDATE advisories SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Log
    app_log('advisory', $id, 'advisory_update', 'advisory', $id, USER['id'], $changes);

    json_response("ok", "Asesoría actualizada correctamente", 2001359200);

} catch (Exception $e) {
    error_log("Error en advisories-update: " . $e->getMessage());
    json_response("ko", "Error al actualizar la asesoría", 5001359200);
}
