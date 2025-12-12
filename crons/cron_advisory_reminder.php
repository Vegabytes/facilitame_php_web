<?php
/**
 * CRON: Recordatorio de comunicaciones importantes no leídas
 *
 * Ejecutar cada hora: 0 * * * * php /path/to/cron_advisory_reminder.php
 *
 * Función: Reenvía email de recordatorio para comunicaciones con importancia
 * "importante" que no han sido leídas después de 24 horas.
 */

// Cargar configuración
require_once(__DIR__ . '/../bold/vars.php');
require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/../bold/db.php");
require_once(__DIR__ . "/../bold/functions.php");

$log_file = __DIR__ . '/logs/advisory_reminder_' . date('Y-m') . '.log';

function cron_log($message) {
    global $log_file;
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

cron_log("=== Iniciando cron advisory_reminder ===");

try {
    // Buscar comunicaciones importantes no leídas después de 24h
    // que no hayan recibido recordatorio aún
    $query = "
        SELECT
            acr.id as recipient_id,
            acr.communication_id,
            acr.customer_id,
            acr.reminder_sent_at,
            ac.subject,
            ac.message,
            ac.importance,
            ac.advisory_id,
            ac.created_at as comm_created_at,
            u.email,
            u.name,
            u.lastname,
            a.razon_social as advisory_name
        FROM advisory_communication_recipients acr
        INNER JOIN advisory_communications ac ON ac.id = acr.communication_id
        INNER JOIN users u ON u.id = acr.customer_id
        INNER JOIN advisories a ON a.id = ac.advisory_id
        WHERE ac.importance = 'importante'
          AND acr.is_read = 0
          AND acr.reminder_sent_at IS NULL
          AND ac.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    cron_log("Comunicaciones pendientes de recordatorio: " . count($pending));

    if (empty($pending)) {
        cron_log("No hay comunicaciones pendientes. Finalizando.");
        exit(0);
    }

    $sent_count = 0;
    $error_count = 0;

    // Preparar statement para marcar recordatorio enviado
    $update_stmt = $pdo->prepare("UPDATE advisory_communication_recipients SET reminder_sent_at = NOW() WHERE id = ?");

    foreach ($pending as $item) {
        try {
            $to_email = $item['email'];
            $to_name = ucwords(trim($item['name'] . ' ' . $item['lastname']));
            $subject = "RECORDATORIO: " . $item['subject'];

            // Construir email de recordatorio
            $email_body = build_reminder_email(
                $to_name,
                $item['subject'],
                $item['message'],
                $item['advisory_name'],
                $item['comm_created_at']
            );

            // Enviar email
            $email_sent = send_mail($to_email, $to_name, $subject, $email_body, $item['communication_id'], [], "Facilítame - Recordatorio");

            if ($email_sent) {
                // Marcar recordatorio como enviado
                $update_stmt->execute([$item['recipient_id']]);
                $sent_count++;
                cron_log("Recordatorio enviado a {$to_email} (comunicación #{$item['communication_id']})");
            } else {
                $error_count++;
                cron_log("ERROR: No se pudo enviar a {$to_email}");
            }

        } catch (Exception $e) {
            $error_count++;
            cron_log("ERROR procesando recipient_id {$item['recipient_id']}: " . $e->getMessage());
        }
    }

    cron_log("Recordatorios enviados: {$sent_count}, Errores: {$error_count}");
    cron_log("=== Cron finalizado ===\n");

} catch (Exception $e) {
    cron_log("ERROR FATAL: " . $e->getMessage());
    exit(1);
}


/**
 * Construye el email de recordatorio
 */
function build_reminder_email($to_name, $subject, $message, $advisory_name, $original_date) {
    $formatted_message = nl2br(htmlspecialchars($message));
    $original_date_formatted = date('d/m/Y H:i', strtotime($original_date));

    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 20px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 24px;">Facilítame</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0;">Recordatorio Importante</p>
        </div>

        <div style="padding: 30px; background: #ffffff;">
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin-bottom: 20px;">
                <span style="color: #ef4444; font-weight: 600; font-size: 14px;">
                    ⚠️ RECORDATORIO - Comunicación no leída
                </span>
            </div>

            <p style="font-size: 16px; color: #1e293b;">
                <b>Hola <?php echo $to_name; ?>,</b>
            </p>

            <p style="color: #475569; font-size: 15px;">
                Tienes una comunicación importante de tu asesoría que aún no has leído.
                Fue enviada el <b><?php echo $original_date_formatted; ?></b>.
            </p>

            <h2 style="color: #1e293b; font-size: 20px; margin: 20px 0 10px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
                <?php echo htmlspecialchars($subject); ?>
            </h2>

            <div style="color: #475569; font-size: 15px; line-height: 1.6; margin: 20px 0; background: #f8fafc; padding: 15px; border-radius: 8px;">
                <?php echo $formatted_message; ?>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">

            <p style="color: #64748b; font-size: 14px;">
                Este recordatorio ha sido enviado por <b><?php echo htmlspecialchars($advisory_name); ?></b> a través de Facilítame.
            </p>

            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo ROOT_URL; ?>/communications" style="background: #ef4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                    Ver comunicación
                </a>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 12px;">
            <p style="margin: 0;">© <?php echo date('Y'); ?> Facilítame. Todos los derechos reservados.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
