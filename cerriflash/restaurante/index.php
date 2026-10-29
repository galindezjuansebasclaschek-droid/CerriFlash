<?php
$pageTitle = 'Panel Restaurante - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('restaurante');

$user = current_user();
$db = Database::getConnection();

// Cargar información actualizada del restaurante
$stmt = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$restaurante = $stmt->fetch();

// Métricas de pedidos
$restauranteId = $restaurante['id'] ?? 0;
$pedidosActivos = 0;
$pedidosEntregados = 0;
$gastosTotales = 0;

if ($restauranteId) {
    $stmtActivos = $db->prepare("
        SELECT COUNT(*) FROM domicilios 
        WHERE restaurante_id = ? AND estado NOT IN ('finalizado', 'cancelado', 'no_entregado')
    ");
    $stmtActivos->execute([$restauranteId]);
    $pedidosActivos = $stmtActivos->fetchColumn();

    $stmtEntregados = $db->prepare("
        SELECT COUNT(*) FROM domicilios 
        WHERE restaurante_id = ? AND estado = 'finalizado'
    ");
    $stmtEntregados->execute([$restauranteId]);
    $pedidosEntregados = $stmtEntregados->fetchColumn();

    $stmtGastos = $db->prepare("
        SELECT COALESCE(SUM(costo_total_envio), 0) FROM domicilios 
        WHERE restaurante_id = ? AND estado = 'finalizado'
    ");
    $stmtGastos->execute([$restauranteId]);
    $gastosTotales = $stmtGastos->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header del Restaurante -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="fw-bold mb-0 brand-text"><i class="bi bi-shop text-danger me-2"></i><?= e($restaurante['nombre_comercial'] ?? $user['nombre']) ?></h2>
                <?= get_status_badge($user['estado']) ?>
            </div>
            <p class="text-muted small mb-0">
                <i class="bi bi-geo-alt me-1"></i><?= e($restaurante['direccion'] ?? 'Dirección no definida') ?>, <?= e($restaurante['ciudad'] ?? 'El Cerrito') ?>
                | Tel: <?= e($restaurante['telefono_contacto'] ?? $user['telefono']) ?>
            </p>
        </div>
        <div class="mt-3 mt-md-0">
            <?php if ($user['estado'] === 'aprobado'): ?>
                <a href="<?= BASE_URL ?>/restaurante/crear-domicilio.php" class="btn btn-primary-cf px-4 py-2 fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i> CREAR DOMICILIO
                </a>
            <?php else: ?>
                <button class="btn btn-secondary px-4 py-2" disabled>
                    <i class="bi bi-hourglass-split me-1"></i> Creación de Envíos en Espera
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerta si está pendiente -->
    <?php if ($user['estado'] === 'pendiente'): ?>
        <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
            <div class="d-flex">
                <i class="bi bi-hourglass-split fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Tu comercio está en proceso de verificación</h5>
                    <p class="mb-0 small">
                        El equipo de administración de CerriFlash está revisando los datos de tu establecimiento comercial.
                        Una vez aprobado tu registro, se habilitará el botón para despachar domicilios inmediatamente.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Métricas principales -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Domicilios en Curso</small>
                        <h3 class="fw-bold text-primary mb-0"><?= number_format($pedidosActivos) ?></h3>
                    </div>
                    <div class="fs-1 text-primary opacity-50"><i class="bi bi-scooter"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Entregas Finalizadas</small>
                        <h3 class="fw-bold text-success mb-0"><?= number_format($pedidosEntregados) ?></h3>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="bi bi-check2-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Inversión en Domicilios</small>
                        <h3 class="fw-bold text-dark mb-0"><?= format_currency($gastosTotales) ?></h3>
                    </div>
                    <div class="fs-1 text-warning opacity-50"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Domicilios Activos del Restaurante -->
    <div class="card-cf bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Tus Domicilios Recientes</h5>
        </div>

        <div class="text-center py-5 text-muted">
            <i class="bi bi-bicycle fs-1 text-secondary opacity-50 mb-2 d-block"></i>
            <h6 class="fw-semibold">No tienes domicilios en curso en este momento</h6>
            <p class="small text-muted mb-3">Publica un nuevo domicilio para que los repartidores disponibles tomen el pedido.</p>
            <?php if ($user['estado'] === 'aprobado'): ?>
                <a href="<?= BASE_URL ?>/restaurante/crear-domicilio.php" class="btn btn-sm btn-outline-cf">
                    <i class="bi bi-plus-lg me-1"></i> Publicar Primer Domicilio
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

