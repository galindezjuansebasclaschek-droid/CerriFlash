<?php
$pageTitle = 'Comisiones e Ingresos - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Métricas financieras
$totales = $db->query("
    SELECT 
        COUNT(*) as total_entregas,
        COALESCE(SUM(costo_total_envio), 0) as total_volumen,
        COALESCE(SUM(ganancia_domiciliario), 0) as total_domiciliarios,
        COALESCE(SUM(comision_cerriflash), 0) as total_comisiones
    FROM domicilios 
    WHERE estado = 'finalizado'
")->fetch();

// Consulta detallada
$stmt = $db->query("
    SELECT d.id, d.codigo_seguimiento, d.distancia_km, d.costo_total_envio,
           d.ganancia_domiciliario, d.comision_cerriflash, d.metodo_pago, d.fecha_entrega,
           r.nombre_comercial AS restaurante_nombre,
           u_dom.nombre AS domi_nombre,
           COALESCE(c.liquidado, 1) as liquidado
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
    LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
    LEFT JOIN comisiones c ON c.domicilio_id = d.id
    WHERE d.estado = 'finalizado'
    ORDER BY d.fecha_entrega DESC
");
$liquidaciones = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-cash-stack text-success me-2"></i>Comisiones e Ingresos Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Balance contable, ingresos retenidos por la plataforma y dispersión a repartidores.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/tarifas.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-sliders me-1"></i> Ajustar Tarifas
            </a>
        </div>
    </div>

    <!-- Tarjetas de Balance Financiero -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-primary">
                <small class="text-muted fw-semibold d-block">Volumen Total Transado</small>
                <h3 class="fw-bold text-dark mb-0"><?= format_currency($totales['total_volumen']) ?></h3>
                <small class="text-muted"><?= number_format($totales['total_entregas']) ?> envíos finalizados</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-success">
                <small class="text-muted fw-semibold d-block">Ingresos CerriFlash (20%)</small>
                <h3 class="fw-bold text-success mb-0"><?= format_currency($totales['total_comisiones']) ?></h3>
                <small class="text-success fw-semibold">Ganancia neta plataforma</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-warning">
                <small class="text-muted fw-semibold d-block">Pagado a Repartidores (80%)</small>
                <h3 class="fw-bold text-dark mb-0"><?= format_currency($totales['total_domiciliarios']) ?></h3>
                <small class="text-muted">Total dispersado a la flota</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-info">
                <small class="text-muted fw-semibold d-block">Ticket Promedio Envío</small>
                <h3 class="fw-bold text-info mb-0">
                    <?= $totales['total_entregas'] > 0 ? format_currency($totales['total_volumen'] / $totales['total_entregas']) : '$0' ?>
                </h3>
                <small class="text-muted">Por cada servicio completado</small>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="card-cf bg-white p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-table me-2 text-primary"></i>Libro de Liquidación Económica</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Código</th>
                        <th>Fecha Finalización</th>
                        <th>Restaurante</th>
                        <th>Repartidor</th>
                        <th>Distancia</th>
                        <th>Valor Total</th>
                        <th>Ganancia Domiciliario</th>
                        <th>Comisión CerriFlash</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($liquidaciones)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                No hay liquidaciones financieras registradas aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($liquidaciones as $l): ?>
                            <tr>
                                <td><strong class="text-primary"><?= e($l['codigo_seguimiento']) ?></strong></td>
                                <td><small class="text-muted"><?= format_datetime($l['fecha_entrega']) ?></small></td>
                                <td class="fw-semibold"><?= e($l['restaurante_nombre']) ?></td>
                                <td><?= e($l['domi_nombre'] ?? 'Sin asignar') ?></td>
                                <td><?= e($l['distancia_km']) ?> km</td>
                                <td><strong><?= format_currency($l['costo_total_envio']) ?></strong></td>
                                <td class="text-success fw-semibold"><?= format_currency($l['ganancia_domiciliario']) ?></td>
                                <td class="text-primary fw-bold"><?= format_currency($l['comision_cerriflash']) ?></td>
                                <td><span class="badge bg-success-subtle text-success">Liquidado</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

