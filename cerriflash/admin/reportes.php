<?php
$pageTitle = 'Reportes y Estadísticas - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Filtros de fecha
$periodo = $_GET['periodo'] ?? 'mes';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-d');

if ($periodo === 'hoy') {
    $fechaInicio = date('Y-m-d');
    $fechaFin = date('Y-m-d');
} elseif ($periodo === 'semana') {
    $fechaInicio = date('Y-m-d', strtotime('-7 days'));
    $fechaFin = date('Y-m-d');
} elseif ($periodo === 'mes') {
    $fechaInicio = date('Y-m-01');
    $fechaFin = date('Y-m-d');
}

// Exportación CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_cerriflash_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    fputcsv($output, ['Código', 'Fecha', 'Restaurante', 'Domiciliario', 'Cliente', 'Distancia (km)', 'Costo Envío', 'Ganancia Repartidor', 'Comisión CerriFlash', 'Estado']);

    $stmtExp = $db->prepare("
        SELECT d.codigo_seguimiento, d.fecha_creacion, r.nombre_comercial, u_dom.nombre AS domi_nombre,
               d.nombre_cliente, d.distancia_km, d.costo_total_envio, d.ganancia_domiciliario,
               d.comision_cerriflash, d.estado
        FROM domicilios d
        INNER JOIN restaurantes r ON d.restaurante_id = r.id
        LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
        LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
        WHERE DATE(d.fecha_creacion) BETWEEN ? AND ?
        ORDER BY d.fecha_creacion DESC
    ");
    $stmtExp->execute([$fechaInicio, $fechaFin]);
    while ($row = $stmtExp->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Estadísticas para el rango
$stmtStats = $db->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        COUNT(CASE WHEN estado = 'finalizado' THEN 1 END) as completados,
        COUNT(CASE WHEN estado = 'cancelado' THEN 1 END) as cancelados,
        COUNT(CASE WHEN estado = 'incidencia' THEN 1 END) as incidencias,
        COALESCE(SUM(costo_total_envio), 0) as total_ventas,
        COALESCE(SUM(ganancia_domiciliario), 0) as total_domis,
        COALESCE(SUM(comision_cerriflash), 0) as total_comision
    FROM domicilios
    WHERE DATE(fecha_creacion) BETWEEN ? AND ?
");
$stmtStats->execute([$fechaInicio, $fechaFin]);
$stats = $stmtStats->fetch();

// Top Comercios
$stmtTopRest = $db->prepare("
    SELECT r.nombre_comercial, COUNT(*) as total, COALESCE(SUM(d.costo_total_envio), 0) as volumen
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    WHERE DATE(d.fecha_creacion) BETWEEN ? AND ?
    GROUP BY r.id
    ORDER BY total DESC LIMIT 5
");
$stmtTopRest->execute([$fechaInicio, $fechaFin]);
$topRestaurantes = $stmtTopRest->fetchAll();

// Top Repartidores
$stmtTopDomi = $db->prepare("
    SELECT u.nombre, dom.tipo_vehiculo, COUNT(*) as total, COALESCE(SUM(d.ganancia_domiciliario), 0) as ganancias
    FROM domicilios d
    INNER JOIN domiciliarios dom ON d.domiciliario_id = dom.id
    INNER JOIN usuarios u ON dom.usuario_id = u.id
    WHERE d.estado = 'finalizado' AND DATE(d.fecha_creacion) BETWEEN ? AND ?
    GROUP BY dom.id
    ORDER BY total DESC LIMIT 5
");
$stmtTopDomi->execute([$fechaInicio, $fechaFin]);
$topDomiciliarios = $stmtTopDomi->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-bar-chart-line-fill text-danger me-2"></i>Reportes de Operación Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Métricas de volumen, ingresos por comisiones y rendimiento de la red de despachos.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= BASE_URL ?>/admin/reportes.php?export=csv&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a CSV (Excel)
            </a>
            <button onclick="window.print();" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Filtros de Fecha -->
    <div class="card-cf bg-white p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/reportes.php" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período Predefinido</label>
                <select class="form-select" name="periodo" onchange="this.form.submit()">
                    <option value="hoy" <?= $periodo === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                    <option value="semana" <?= $periodo === 'semana' ? 'selected' : '' ?>>Últimos 7 Días</option>
                    <option value="mes" <?= $periodo === 'mes' ? 'selected' : '' ?>>Mes Actual</option>
                    <option value="custom" <?= $periodo === 'custom' ? 'selected' : '' ?>>Rango Personalizado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" value="<?= e($fechaInicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" value="<?= e($fechaFin) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary-cf w-100">
                    <i class="bi bi-funnel me-1"></i> Actualizar Métricas
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-primary">
                <small class="text-muted fw-semibold d-block">Total Domicilios</small>
                <h3 class="fw-bold mb-0"><?= number_format($stats['total_pedidos']) ?></h3>
                <small class="text-success"><?= number_format($stats['completados']) ?> completados</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-success">
                <small class="text-muted fw-semibold d-block">Comisiones CerriFlash</small>
                <h3 class="fw-bold text-success mb-0"><?= format_currency($stats['total_comision']) ?></h3>
                <small class="text-muted">20% de comisión neta</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-warning">
                <small class="text-muted fw-semibold d-block">Pagado a Domiciliarios</small>
                <h3 class="fw-bold text-dark mb-0"><?= format_currency($stats['total_domis']) ?></h3>
                <small class="text-muted">80% ingresos de la flota</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-danger">
                <small class="text-muted fw-semibold d-block">Cancelaciones / Incidencias</small>
                <h3 class="fw-bold text-danger mb-0"><?= number_format($stats['cancelados'] + $stats['incidencias']) ?></h3>
                <small class="text-muted"><?= number_format($stats['cancelados']) ?> cancelados | <?= number_format($stats['incidencias']) ?> incidencias</small>
            </div>
        </div>
    </div>

    <!-- Tablas Top Comercios y Repartidores -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-shop me-2 text-primary"></i>Top Comercios con Más Despachos</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Restaurante</th>
                                <th class="text-center">Envíos</th>
                                <th class="text-end">Volumen Facturado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topRestaurantes)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Sin datos en este rango de fechas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topRestaurantes as $tr): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($tr['nombre_comercial']) ?></td>
                                        <td class="text-center"><span class="badge bg-dark"><?= $tr['total'] ?></span></td>
                                        <td class="text-end fw-bold"><?= format_currency($tr['volumen']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-warning"></i>Top Domiciliarios Más Destacados</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Repartidor</th>
                                <th>Vehículo</th>
                                <th class="text-center">Entregas</th>
                                <th class="text-end">Ganancias</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topDomiciliarios)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Sin entregas completadas en este período.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topDomiciliarios as $td): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($td['nombre']) ?></td>
                                        <td class="text-capitalize"><?= e($td['tipo_vehiculo']) ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?= $td['total'] ?></span></td>
                                        <td class="text-end fw-bold text-success"><?= format_currency($td['ganancias']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

