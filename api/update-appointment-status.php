<?php
if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';
$scheduled_date = isset($data['scheduled_date']) ? $data['scheduled_date'] : null;

if ($appointment_id <= 0) {
    json_response("ko", "ID de cita inválido", 400);
}

if (!in_array($status, ['solicitado', 'agendado', 'finalizado', 'cancelado'])) {
    json_response("ko", "Estado inválido", 400);
}

try {
    $user_id = (int)USER["id"];
    
    // Verificar que la cita pertenece a esta asesoría
    $stmt = $pdo->prepare("
        SELECT aa.id 
        FROM advisory_appointments aa
        INNER JOIN advisories a ON a.id = aa.advisory_id
        WHERE aa.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$appointment_id, $user_id]);
    
    if (!$stmt->fetch()) {
        json_response("ko", "Cita no encontrada", 404);
    }
    
    // Actualizar estado
    if ($status === 'agendado' && $scheduled_date) {
        $stmt = $pdo->prepare("
            UPDATE advisory_appointments 
            SET status = ?, scheduled_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $scheduled_date, $appointment_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE advisory_appointments 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $appointment_id]);
    }
    
    json_response("ok", "Estado actualizado correctamente");
    
} catch (Throwable $e) {
    error_log("Error en update-appointment-status: " . $e->getMessage());
    json_response("ko", "Error al actualizar: " . $e->getMessage(), 500);
}