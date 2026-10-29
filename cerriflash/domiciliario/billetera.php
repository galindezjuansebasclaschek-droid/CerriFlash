<?php
$pageTitle = 'Mi Billetera y Ganancias - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('domiciliario');

$user = current_user();
$db = Database::getConnection();

// Cargar domiciliario ID
$stmtD = $db->prepare("SELECT id FROM domiciliarios WHERE usuario_id = ?");
$stmtD->execute([$user['id']]);
$domiId = (int)$stmtD->fetchColumn();

// Métricas de ganancias
$stmtTotal = $db->prepare("SELECT COALESCE(SUM(ganancia_domiciliario), 0) FROM domicilios WHERE domiciliario_id = ? AND estado = 'finalizado'");
$stmtTotal->execute([$domiId]);
$gananciaTotal = (float)$stmtTotal->fetchColumn();

$stmtHoy = $db->prepare("SELECT COALESCE(SUM(ganancia_domiciliario), 0) FROM domicilios WHERE domiciliario_id = ? AND estado = 'finalizado' AND DATE(fecha_entrega) = CURDATE()");
$stmtHoy->execute([$domiId]);
$gananciaHoy = (float)$stmtHoy->fetchColumn();

$stmtEntregas = $db->prepare("SELECT COUNT(*) FROM domicilios WHERE domiciliario_id = ? AND estado = 'finalizado'");
$stmtEntregas->execute([$domiId]);
$totalEntregas = (int)$stmtEntregas->fetchColumn();

// Historial de entregas completadas
$stmtHistorial = $db->prepare("
    SELECT d.*, r.nombre_comercial AS restaurante_nombre 
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    WHERE d.domiciliario_id = ? AND d.estado = 'finalizado'
    ORDER BY d.fecha_entrega DESC
");
$stmtHistorial->execute([$domiId]);
$entregas = $stmtHistorial->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-wallet2 text-success me-2"></i>Mi Billetera Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Control de ganancias netas, historial de entregas y pagos liquidados.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/domiciliario/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Radar de Pedidos
            </a>
        </div>
    </div>

    <!-- Tarjetas de Saldo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-cf p-4 bg-white border-start border-4 border-success">
                <small class="text-muted fw-semibold text-uppercase d-block mb-1">Ganancia Neta Total Acumulada</small>
                <h2 class="fw-black text-success mb-0"><?= format_currency($gananciaTotal) ?></h2>
                <small class="text-muted">Total liquidado por tus servicios.</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-4 bg-white border-start border-4 border-primary">
                <small class="text-muted fw-semibold text-uppercase d-block mb-1">Ganancias de Hoy</small>
                <h2 class="fw-black text-primary mb-0"><?= format_currency($gananciaHoy) ?></h2>
                <small class="text-muted">Producido durante el turno actual.</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-4 bg-white border-start border-4 border-warning">
                <small class="text-muted fw-semibold text-uppercase d-block mb-1">Entregas Completadas</small>
                <h2 class="fw-black text-dark mb-0"><?= number_format($totalEntregas) ?></h2>
                <small class="text-muted">Promedio por servicio: <?= $totalEntregas > 0 ? format_currency($gananciaTotal / $totalEntregas) : '$0' ?></small>
            </div>
        </div>
    </div>

    <!-- Detalle de Entregas Liquidadas -->
    <div class="card-cf bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Historial Detallado de Ganancias</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Código Pedido</th>
                        <th>Fecha Entrega</th>
                        <th>Restaurante</th>
                        <th>Distancia</th>
                        <th>Cobro Cliente</th>
                        <th>Comisión App (20%)</th>
                        <th>Tu Ganancia (80%)</th>
                        <th>Método</th>
                        <th>Estado Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entregas)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                Aún no has completado servicios. Activa tu disponibilidad y toma domicilios para ver reflejadas tus ganancias.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $e): ?>
                            <tr>
                                <td><strong class="text-primary"><?= e($e['codigo_seguimiento']) ?></strong></td>
                                <td><small class="text-muted"><?= format_datetime($e['fecha_entrega']) ?></small></td>
                                <td class="fw-semibold"><?= e($e['restaurante_nombre']) ?></td>
                                <td><?= e($e['distancia_km']) ?> km</td>
                                <td><?= format_currency($e['costo_total_envio']) ?></td>
                                <td class="text-muted">- <?= format_currency($e['comision_cerriflash']) ?></td>
                                <td><strong class="text-success fs-6">+ <?= format_currency($e['ganancia_domiciliario']) ?></strong></td>
                                <td><span class="badge bg-light text-dark border text-uppercase"><?= e($e['metodo_pago']) ?></span></td>
                                <td><span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-check-all me-1"></i>Liquidado</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

