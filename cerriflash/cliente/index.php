<?php
$pageTitle = 'Mi Cuenta - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('cliente');

$user = current_user();
$db = Database::getConnection();

// Cargar cliente
$stmtClient = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
$stmtClient->execute([$user['id']]);
$cliente = $stmtClient->fetch();
$clienteId = $cliente['id'] ?? 0;

// Pedidos activos del cliente
$pedidosActivos = [];
if ($clienteId) {
    $stmtAct = $db->prepare("
        SELECT d.*, r.nombre_comercial AS restaurante_nombre, dom.foto_perfil AS foto_domi
        FROM domicilios d
        INNER JOIN restaurantes r ON d.restaurante_id = r.id
        LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
        WHERE d.cliente_id = ? AND d.estado NOT IN ('finalizado', 'cancelado')
        ORDER BY d.fecha_creacion DESC
    ");
    $stmtAct->execute([$clienteId]);
    $pedidosActivos = $stmtAct->fetchAll();
}

// Direcciones guardadas
$direcciones = [];
if ($clienteId) {
    $stmtDir = $db->prepare("SELECT * FROM direcciones WHERE cliente_id = ? ORDER BY es_predeterminada DESC, id DESC");
    $stmtDir->execute([$clienteId]);
    $direcciones = $stmtDir->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Cliente -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-person-heart text-primary me-2"></i>Hola, <?= e($user['nombre']) ?></h2>
            <p class="text-muted small mb-0">Consulta el estado de tus entregas en tiempo real y gestiona tus direcciones.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-patch-check-fill me-1"></i> Cliente CerriFlash
            </span>
        </div>
    </div>

    <!-- Pedidos Activos -->
    <div class="card-cf bg-white p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-radar text-warning me-2"></i>Tus Pedidos en Curso</h5>

        <?php if (empty($pedidosActivos)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-bag-check fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                <h6 class="fw-semibold">No tienes domicilios en camino en este momento</h6>
                <p class="small text-muted mb-0">Cuando un comercio te envíe un pedido por CerriFlash podrás seguir su trayecto aquí.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($pedidosActivos as $ped): ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-dark fw-bold"><?= e($ped['codigo_seguimiento']) ?></span>
                                <?= get_status_badge($ped['estado']) ?>
                            </div>
                            <h6 class="fw-bold mb-1 text-primary"><?= e($ped['restaurante_nombre']) ?></h6>
                            <p class="small text-muted mb-2"><?= e($ped['descripcion_paquete']) ?></p>
                            <div class="d-flex justify-content-between align-items-center small border-top pt-2">
                                <span>Total envío: <strong><?= format_currency($ped['costo_total_envio']) ?></strong></span>
                                <a href="<?= BASE_URL ?>/seguimiento.php?codigo=<?= urlencode($ped['codigo_seguimiento']) ?>" class="btn btn-sm btn-primary-cf py-1 px-3">
                                    Ver Rastreo <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mis Direcciones -->
    <div class="card-cf bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Mis Direcciones Frecuentes</h5>
        </div>

        <?php if (empty($direcciones)): ?>
            <p class="text-muted small mb-0">
                Dirección principal: <strong><?= e($cliente['direccion_frecuente'] ?? 'No registrada aún') ?></strong>
                (<?= e($cliente['ciudad'] ?? 'El Cerrito') ?>)
            </p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($direcciones as $dir): ?>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="small"><i class="bi bi-house-door me-1"></i><?= e($dir['etiqueta']) ?></strong>
                                <?php if ($dir['es_predeterminada']): ?>
                                    <span class="badge bg-success-subtle text-success small">Predeterminada</span>
                                <?php endif; ?>
                            </div>
                            <p class="small mb-1"><?= e($dir['direccion']) ?></p>
                            <small class="text-muted"><?= e($dir['barrio'] ?? '') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

