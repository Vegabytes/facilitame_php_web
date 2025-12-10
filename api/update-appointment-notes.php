<?php
if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;
$notes = isset($data['notes']) ? trim($data['notes']) : '';

if ($appointment_id <= 0) {
    json_response("ko", "ID de cita inválido", 400);
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
    
    // Actualizar notas
    $stmt = $pdo->prepare("
        UPDATE advisory_appointments 
        SET notes_advisory = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$notes, $appointment_id]);
    
    json_response("ok", "Notas guardadas correctamente");
    
} catch (Throwable $e) {
    error_log("Error en update-appointment-notes: " . $e->getMessage());
    json_response("ko", "Error al guardar notas: " . $e->getMessage(), 500);
}