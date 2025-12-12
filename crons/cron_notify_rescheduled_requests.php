<?php
/**
 * CRON: Notificar solicitudes reagendadas
 *
 * Ejecutar diariamente: 0 8 * * * php /path/to/cron_notify_rescheduled_requests.php
 *
 * Función: Notifica a cliente, comercial y proveedor cuando una solicitud
 * reagendada alcanza su fecha de reactivación.
 */

// Cargar configuración
require_once(__DIR__ . '/../bold/vars.php');
require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/../bold/db.php");
require_once(__DIR__ . "/../bold/functions.php");
require_once(__DIR__ . "/../bold/utils/firebase-console-message.php");
require_once(__DIR__ . "/../bold/utils/apple-apn.php");

$log_file = __DIR__ . '/logs/rescheduled_requests_' . date('Y-m') . '.log';

function cron_log($message) {
    global $log_file;
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

cron_log("=== Iniciando cron rescheduled_requests ===");

try {
    $today = date('Y-m-d');

    // Buscar solicitudes reagendadas cuya fecha ya llegó
    $query = "SELECT * FROM requests WHERE status_id = 10 AND rescheduled_at IS NOT NULL AND DATE(rescheduled_at) <= :today";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':today', $today);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    cron_log("Solicitudes encontradas: " . count($requests));

    if (empty($requests)) {
        cron_log("No hay solicitudes pendientes. Finalizando.");
        exit(0);
    }

    $notified_count = 0;

    foreach ($requests as $request) {
        try {
            // Obtener usuario, comercial y proveedor
            $client = get_user($request["user_id"]);
            $provider = get_request_provider($request["id"]);
            $sales_rep_id = customer_get_sales_rep($request["user_id"]);

            $fecha_formateada = fdate($request["rescheduled_at"]);

            // Notificar al cliente
            $title = "¡Tu solicitud aplazada ya está lista!";
            $desc = "La solicitud #" . $request["id"] . " ya está disponible para ser gestionada (fecha aplazamiento: " . $fecha_formateada . ").";
            notification_v2(
                165, // ID sistema
                $client["id"],
                $request["id"],
                $title,
                $desc,
                $title,
                "solicitud_reagendada_cliente",
                ["nombre" => $client["name"], "fecha" => $fecha_formateada, "id" => $request["id"]]
            );
            cron_log("Notificación enviada al cliente #{$client['id']}");

            // Notificar al comercial
            if ($sales_rep_id) {
                $sales_rep = get_user($sales_rep_id);
                $title = "Una solicitud aplazada ya está disponible";
                $desc = "La solicitud #" . $request["id"] . " ya puede ser gestionada. Fecha de reagendado: " . $fecha_formateada . ".";
                notification_v2(
                    165,
                    $sales_rep_id,
                    $request["id"],
                    $title,
                    $desc,
                    $title,
                    "reschedule_notification_generic",
                    ["nombre" => $sales_rep["name"], "fecha" => $fecha_formateada, "id" => $request["id"]]
                );
                cron_log("Notificación enviada al comercial #{$sales_rep_id}");
            }

            // Notificar al proveedor
            if ($provider) {
                $title = "Una solicitud aplazada ya está disponible";
                $desc = "La solicitud #" . $request["id"] . " ya puede ser gestionada. Fecha de reagendado: " . $fecha_formateada . ".";
                notification_v2(
                    165,
                    $provider["id"],
                    $request["id"],
                    $title,
                    $desc,
                    $title,
                    "reschedule_notification_generic",
                    ["nombre" => $provider["name"], "fecha" => $fecha_formateada, "id" => $request["id"]]
                );
                cron_log("Notificación enviada al proveedor #{$provider['id']}");
            }

            $notified_count++;

        } catch (Exception $e) {
            cron_log("ERROR procesando solicitud #{$request['id']}: " . $e->getMessage());
        }
    }

    cron_log("Solicitudes procesadas: {$notified_count}");
    cron_log("=== Cron finalizado ===\n");

} catch (Exception $e) {
    cron_log("ERROR FATAL: " . $e->getMessage());
    exit(1);
}
