<?php
file_put_contents('./cron_prueba.log', date('c') . " - Script iniciado\n", FILE_APPEND);

// Cargar configuración y utilidades del proyecto
try {
    require_once(__DIR__ . '/../bold/vars.php');
    file_put_contents('./cron_prueba.log', date('c') . " - vars.php cargado correctamente\n", FILE_APPEND);

    require_once(__DIR__ . "/../vendor/autoload.php");
    file_put_contents('./cron_prueba.log', date('c') . " - autoload.php cargado correctamente\n", FILE_APPEND);

    require_once(__DIR__ . "/../bold/db.php");
    file_put_contents('./cron_prueba.log', date('c') . " - db.php cargado correctamente\n", FILE_APPEND);

    require_once(__DIR__ . "/../bold/functions.php");
    file_put_contents('./cron_prueba.log', date('c') . " - functions.php cargado correctamente\n", FILE_APPEND);

    require_once(__DIR__ . "/../bold/utils/firebase-console-message.php");
    file_put_contents('./cron_prueba.log', date('c') . " - firebase-console-message.php cargado correctamente\n", FILE_APPEND);

    require_once(__DIR__ . "/../bold/utils/apple-apn.php");
    file_put_contents('./cron_prueba.log', date('c') . " - apple-apn.php cargado correctamente\n", FILE_APPEND);

} catch (Throwable $e) {
    file_put_contents('./cron_prueba.log', date('c') . " - ERROR cargando archivos: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

// Fecha actual (al empezar el día)
$today = date('Y-m-d');
file_put_contents('./cron_prueba.log', date('c') . " - Fecha hoy: $today\n", FILE_APPEND);

// Preparar consulta
try {
    $query = "SELECT * FROM requests WHERE status_id = 10 AND rescheduled_at IS NOT NULL AND DATE(rescheduled_at) <= :today";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':today', $today);
    $stmt->execute();
    file_put_contents('./cron_prueba.log', date('c') . " - Consulta ejecutada correctamente\n", FILE_APPEND);

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('./cron_prueba.log', date('c') . " - Solicitudes encontradas: " . count($requests) . "\n", FILE_APPEND);

} catch (Throwable $e) {
    file_put_contents('./cron_prueba.log', date('c') . " - ERROR en consulta: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

foreach ($requests as $request) {
    file_put_contents('./cron_prueba.log', date('c') . " - Procesando solicitud ID " . $request["id"] . "\n", FILE_APPEND);

    try {
        // Obtener usuario, comercial y proveedor
        $client       = get_user($request["user_id"]);
        $provider     = get_request_provider($request["id"]);
        $sales_rep_id = customer_get_sales_rep($request["user_id"]);

        $rescheduled_at = $request["rescheduled_at"];
        $fecha_formateada = fdate($rescheduled_at);

        // Cliente
        $title = "¡Tu solicitud aplazada ya está lista!";
        $desc  = "La solicitud #" . $request["id"] . " ya está disponible para ser gestionada (fecha aplazamiento: " . $fecha_formateada . ").";
        notification_v2(
       165,
            $client["id"],
            $request["id"],
            $title,
            $desc,
            $title,
            "solicitud_reagendada_cliente",
            [ "nombre" => $client["name"], "fecha" => $fecha_formateada, "id" => $request["id"] ]
        );
        file_put_contents('./cron_prueba.log', date('c') . " - Notificación enviada al cliente " . $client["id"] . "\n", FILE_APPEND);

        // Comercial
        if ($sales_rep_id) {
            $title = "Una solicitud aplazada ya está disponible";
            $desc  = "La solicitud #" . $request["id"] . " ya puede ser gestionada. Fecha de reagendado: " . $fecha_formateada . ".";
            notification_v2(
        165,
                $sales_rep_id,
                $request["id"],
                $title,
                $desc,
                $title,
                "reschedule_notification_generic",
                [ "nombre" => get_user($sales_rep_id)["name"], "fecha" => $fecha_formateada, "id" => $request["id"] ]
            );
            file_put_contents('./cron_prueba.log', date('c') . " - Notificación enviada al comercial " . $sales_rep_id . "\n", FILE_APPEND);
        }

        // Proveedor
        if ($provider) {
            $title = "Una solicitud aplazada ya está disponible";
            $desc  = "La solicitud #" . $request["id"] . " ya puede ser gestionada. Fecha de reagendado: " . $fecha_formateada . ".";
            notification_v2(
      165,
                $provider["id"],
                $request["id"],
                $title,
                $desc,
                $title,
                "reschedule_notification_generic",
                [ "nombre" => $provider["name"], "fecha" => $fecha_formateada, "id" => $request["id"] ]
            );
            file_put_contents('./cron_prueba.log', date('c') . " - Notificación enviada al proveedor " . $provider["id"] . "\n", FILE_APPEND);
        }

    } catch (Throwable $e) {
        file_put_contents('./cron_prueba.log', date('c') . " - ERROR procesando solicitud ID " . $request["id"] . ": " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

file_put_contents('./cron_prueba.log', date('c') . " - Script finalizado\n", FILE_APPEND);
