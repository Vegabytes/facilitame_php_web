<?php
/**
 * API: dashboard-kpis-advisory.php
 * KPIs y métricas del dashboard de asesoría
 *
 * GET: Retorna todas las métricas
 * - KPIs básicos (clientes, citas, facturas, mensajes)
 * - Métricas del mes actual
 * - Comparativa con mes anterior
 * - Facturas por tipo y estado
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

$advisory_id = $advisory['id'];

try {
    // Fechas para comparativas
    $now = new DateTime();
    $currentMonth = $now->format('Y-m');
    $currentMonthStart = $now->format('Y-m-01');
    $currentMonthEnd = $now->format('Y-m-t');

    $lastMonth = (clone $now)->modify('-1 month');
    $lastMonthStart = $lastMonth->format('Y-m-01');
    $lastMonthEnd = $lastMonth->format('Y-m-t');

    $currentYear = $now->format('Y');
    $currentQuarter = ceil($now->format('n') / 3);

    // === KPIs BÁSICOS ===
    // Clientes activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers_advisories WHERE advisory_id = ?");
    $stmt->execute([$advisory_id]);
    $totalClientes = (int) $stmt->fetchColumn();

    // Citas pendientes (futuras, no canceladas)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM advisory_appointments
        WHERE advisory_id = ? AND status IN ('pending', 'confirmed')
        AND appointment_date >= CURDATE()
    ");
    $stmt->execute([$advisory_id]);
    $citasPendientes = (int) $stmt->fetchColumn();

    // Facturas sin procesar
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM advisory_invoices
        WHERE advisory_id = ? AND is_processed = 0
    ");
    $stmt->execute([$advisory_id]);
    $facturasPorProcesar = (int) $stmt->fetchColumn();

    // Mensajes sin leer (de clientes a asesoría)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM advisory_communications
        WHERE advisory_id = ? AND is_read = 0 AND sender_type = 'customer'
    ");
    $stmt->execute([$advisory_id]);
    $mensajesSinLeer = (int) $stmt->fetchColumn();

    // === MÉTRICAS DEL MES ===
    // Clientes nuevos este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM customers_advisories
        WHERE advisory_id = ? AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$advisory_id, $currentMonthStart, $currentMonthEnd . ' 23:59:59']);
    $clientesNuevosMes = (int) $stmt->fetchColumn();

    // Clientes nuevos mes anterior (para comparativa)
    $stmt->execute([$advisory_id, $lastMonthStart, $lastMonthEnd . ' 23:59:59']);
    $clientesNuevosMesAnterior = (int) $stmt->fetchColumn();

    // Facturas recibidas este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM advisory_invoices
        WHERE advisory_id = ? AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$advisory_id, $currentMonthStart, $currentMonthEnd . ' 23:59:59']);
    $facturasRecibidaMes = (int) $stmt->fetchColumn();

    // Facturas mes anterior
    $stmt->execute([$advisory_id, $lastMonthStart, $lastMonthEnd . ' 23:59:59']);
    $facturasRecibidasMesAnterior = (int) $stmt->fetchColumn();

    // Citas realizadas este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM advisory_appointments
        WHERE advisory_id = ? AND status = 'completed'
        AND appointment_date >= ? AND appointment_date <= ?
    ");
    $stmt->execute([$advisory_id, $currentMonthStart, $currentMonthEnd]);
    $citasRealizadasMes = (int) $stmt->fetchColumn();

    // === FACTURAS POR TIPO ===
    $stmt = $pdo->prepare("
        SELECT
            type,
            COUNT(*) as total,
            SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as procesadas,
            SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as pendientes
        FROM advisory_invoices
        WHERE advisory_id = ?
        GROUP BY type
    ");
    $stmt->execute([$advisory_id]);
    $facturasPorTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $facturasGasto = ['total' => 0, 'procesadas' => 0, 'pendientes' => 0];
    $facturasIngreso = ['total' => 0, 'procesadas' => 0, 'pendientes' => 0];

    foreach ($facturasPorTipo as $row) {
        if ($row['type'] === 'gasto') {
            $facturasGasto = [
                'total' => (int) $row['total'],
                'procesadas' => (int) $row['procesadas'],
                'pendientes' => (int) $row['pendientes']
            ];
        } else {
            $facturasIngreso = [
                'total' => (int) $row['total'],
                'procesadas' => (int) $row['procesadas'],
                'pendientes' => (int) $row['pendientes']
            ];
        }
    }

    // === FACTURAS POR MES (últimos 6 meses) ===
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            COUNT(*) as total,
            SUM(CASE WHEN type = 'gasto' THEN 1 ELSE 0 END) as gastos,
            SUM(CASE WHEN type = 'ingreso' THEN 1 ELSE 0 END) as ingresos
        FROM advisory_invoices
        WHERE advisory_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt->execute([$advisory_id]);
    $facturasPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === FACTURAS POR TRIMESTRE (año actual) ===
    $stmt = $pdo->prepare("
        SELECT
            quarter as trimestre,
            COUNT(*) as total,
            SUM(CASE WHEN type = 'gasto' THEN 1 ELSE 0 END) as gastos,
            SUM(CASE WHEN type = 'ingreso' THEN 1 ELSE 0 END) as ingresos,
            SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as procesadas
        FROM advisory_invoices
        WHERE advisory_id = ? AND year = ?
        GROUP BY quarter
        ORDER BY quarter ASC
    ");
    $stmt->execute([$advisory_id, $currentYear]);
    $facturasPorTrimestre = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === CLIENTES POR TIPO ===
    $stmt = $pdo->prepare("
        SELECT
            client_type,
            COUNT(*) as total
        FROM customers_advisories
        WHERE advisory_id = ?
        GROUP BY client_type
    ");
    $stmt->execute([$advisory_id]);
    $clientesPorTipo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // === PRÓXIMAS CITAS ===
    $stmt = $pdo->prepare("
        SELECT
            aa.id,
            aa.appointment_date,
            aa.appointment_time,
            aa.subject,
            aa.status,
            u.name as customer_name
        FROM advisory_appointments aa
        LEFT JOIN users u ON aa.customer_id = u.id
        WHERE aa.advisory_id = ? AND aa.status IN ('pending', 'confirmed')
        AND aa.appointment_date >= CURDATE()
        ORDER BY aa.appointment_date ASC, aa.appointment_time ASC
        LIMIT 5
    ");
    $stmt->execute([$advisory_id]);
    $proximasCitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // === INMATIC STATS (si está configurado) ===
    $inmaticStats = null;
    $stmt = $pdo->prepare("SELECT is_active FROM advisory_inmatic_config WHERE advisory_id = ? AND is_active = 1");
    $stmt->execute([$advisory_id]);
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("
            SELECT
                SUM(CASE WHEN inmatic_status IN ('processed', 'approved', 'exported') THEN 1 ELSE 0 END) as procesadas,
                SUM(CASE WHEN inmatic_status = 'pending' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN inmatic_status = 'error' THEN 1 ELSE 0 END) as errores
            FROM advisory_inmatic_documents aid
            JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
            WHERE ai.advisory_id = ?
        ");
        $stmt->execute([$advisory_id]);
        $inmaticStats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Calcular variaciones porcentuales
    $variacionClientes = $clientesNuevosMesAnterior > 0
        ? round((($clientesNuevosMes - $clientesNuevosMesAnterior) / $clientesNuevosMesAnterior) * 100)
        : ($clientesNuevosMes > 0 ? 100 : 0);

    $variacionFacturas = $facturasRecibidasMesAnterior > 0
        ? round((($facturasRecibidaMes - $facturasRecibidasMesAnterior) / $facturasRecibidasMesAnterior) * 100)
        : ($facturasRecibidaMes > 0 ? 100 : 0);

    // Respuesta
    json_response("ok", "", 200, [
        // KPIs básicos (para la UI actual)
        'clientes' => $totalClientes,
        'citas' => $citasPendientes,
        'facturas' => $facturasPorProcesar,
        'mensajes' => $mensajesSinLeer,

        // Métricas del mes
        'mes_actual' => [
            'clientes_nuevos' => $clientesNuevosMes,
            'facturas_recibidas' => $facturasRecibidaMes,
            'citas_realizadas' => $citasRealizadasMes,
            'variacion_clientes' => $variacionClientes,
            'variacion_facturas' => $variacionFacturas
        ],

        // Facturas detalle
        'facturas_detalle' => [
            'gasto' => $facturasGasto,
            'ingreso' => $facturasIngreso,
            'total' => $facturasGasto['total'] + $facturasIngreso['total'],
            'total_procesadas' => $facturasGasto['procesadas'] + $facturasIngreso['procesadas'],
            'total_pendientes' => $facturasGasto['pendientes'] + $facturasIngreso['pendientes']
        ],

        // Gráficos
        'facturas_por_mes' => $facturasPorMes,
        'facturas_por_trimestre' => $facturasPorTrimestre,
        'clientes_por_tipo' => $clientesPorTipo,

        // Próximas citas
        'proximas_citas' => $proximasCitas,

        // Inmatic
        'inmatic' => $inmaticStats,

        // Meta
        'periodo' => [
            'mes' => $currentMonth,
            'trimestre' => $currentQuarter,
            'year' => $currentYear
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en dashboard-kpis-advisory: " . $e->getMessage());
    json_response("ko", "Error al obtener métricas", 500);
}
