<?php
$pageTitle = 'Mis Domicilios - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('restaurante');

$user = current_user();
$db = Database::getConnection();

// Cargar restaurante
$stmtR = $db->prepare("SELECT id, nombre_comercial FROM restaurantes WHERE usuario_id = ?");
$stmtR->execute([$user['id']]);
$restaurante = $stmtR->fetch();
$restauranteId = (int)($restaurante['id'] ?? 0);

// Cancelar domicilio si está en estado inicial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancelar') {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $domId = (int)($_POST['domicilio_id'] ?? 0);
        $motivo = trim($_POST['motivo_cancelacion'] ?? 'Cancelado por el comercio');

        // Solo permitir si no ha sido recogido
        $stmtCheck = $db->prepare("SELECT estado, codigo_seguimiento FROM domicilios WHERE id = ? AND restaurante_id = ?");
        $stmtCheck->execute([$domId, $restauranteId]);
        $dom = $stmtCheck->fetch();

        if ($dom && in_array($dom['estado'], ['creado', 'publicado', 'buscando_domiciliario', 'aceptado'])) {
            $stmtCan = $db->prepare("UPDATE domicilios SET estado = 'cancelado' WHERE id = ?");
            $stmtCan->execute([$domId]);

            $stmtH = $db->prepare("INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id) VALUES (?, 'cancelado', ?, ?)");
            $stmtH->execute([$domId, $motivo, $user['id']]);

            set_flash('info', "El domicilio #{$dom['codigo_seguimiento']} ha sido cancelado.");
            redirect('restaurante/domicilios.php');
        } else {
            set_flash('danger', 'No es posible cancelar este pedido porque ya se encuentra en camino o ha sido completado.');
        }
    }
}

// Filtro por pestaña
$tab = $_GET['tab'] ?? 'activos';

$condicionEstado = match($tab) {
    'completados' => "AND d.estado IN ('entregado', 'finalizado')",
    'cancelados'  => "AND d.estado IN ('cancelado', 'no_entregado', 'incidencia')",
    default       => "AND d.estado NOT IN ('finalizado', 'cancelado', 'no_entregado')"
};

$sql = "
    SELECT d.*, 
           dom.usuario_id AS domi_user_id,
           u_dom.nombre AS domi_nombre,
           u_dom.telefono AS domi_telefono,
           dom.tipo_vehiculo AS domi_vehiculo,
           dom.placa_vehiculo AS domi_placa,
           dom.foto_perfil AS domi_foto
    FROM domicilios d
    LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
    LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
    WHERE d.restaurante_id = ? {$condicionEstado}
    ORDER BY d.fecha_creacion DESC
";
$stmt = $db->prepare($sql);
$stmt->execute([$restauranteId]);
$domicilios = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-box-seam text-danger me-2"></i>Historial de Domicilios</h2>
            <p class="text-muted small mb-0">Control y monitoreo de los despachos de <?= e($restaurante['nombre_comercial'] ?? '') ?>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/restaurante/crear-domicilio.php" class="btn btn-primary-cf btn-sm fw-bold">
                <i class="bi bi-plus-circle-fill me-1"></i> CREAR DOMICILIO
            </a>
            <a href="<?= BASE_URL ?>/restaurante/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Pestañas de Filtro -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'activos' ? 'active bg-primary-cf' : 'bg-white border text-dark' ?> fw-semibold" href="<?= BASE_URL ?>/restaurante/domicilios.php?tab=activos">
                <i class="bi bi-radar me-1"></i> En Curso / Activos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'completados' ? 'active bg-success' : 'bg-white border text-dark' ?> fw-semibold" href="<?= BASE_URL ?>/restaurante/domicilios.php?tab=completados">
                <i class="bi bi-check-circle me-1"></i> Entregados y Finalizados
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'cancelados' ? 'active bg-danger' : 'bg-white border text-dark' ?> fw-semibold" href="<?= BASE_URL ?>/restaurante/domicilios.php?tab=cancelados">
                <i class="bi bi-x-circle me-1"></i> Cancelados / Incidencias
            </a>
        </li>
    </ul>

    <!-- Listado de Domicilios -->
    <div class="card-cf bg-white p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Código / Fecha</th>
                        <th>Destino y Cliente</th>
                        <th>Contenido Pedido</th>
                        <th>Repartidor Asignado</th>
                        <th>Tarifa</th>
                        <th>Estado Actual</th>
                        <th class="text-end">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($domicilios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                No hay pedidos en esta categoría.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($domicilios as $d): ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-primary"><?= e($d['codigo_seguimiento']) ?></strong>
                                    <small class="text-muted"><?= format_datetime($d['fecha_creacion']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($d['nombre_cliente']) ?></div>
                                    <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i><?= e($d['telefono_cliente']) ?></small>
                                    <small class="text-secondary"><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($d['direccion_entrega']) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($d['descripcion_paquete']) ?></div>
                                    <?php if (!empty($d['observaciones'])): ?>
                                        <small class="text-muted fst-italic">"<?= e($d['observaciones']) ?>"</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($d['domi_nombre'])): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($d['domi_foto'])): ?>
                                                <img src="<?= UPLOADS_URL ?>/fotos/<?= e($d['domi_foto']) ?>" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-person"></i></div>
                                            <?php endif; ?>
                                            <div>
                                                <strong class="small d-block"><?= e($d['domi_nombre']) ?></strong>
                                                <small class="text-muted"><?= e($d['domi_telefono']) ?> (<?= e($d['domi_vehiculo']) ?> <?= e($d['domi_placa'] ?? '') ?>)</small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis">
                                            <i class="bi bi-radar me-1"></i> Buscando repartidor...
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= format_currency($d['costo_total_envio']) ?></strong>
                                    <small class="text-muted d-block"><?= e($d['distancia_km']) ?> km</small>
                                </td>
                                <td>
                                    <?= get_status_badge($d['estado']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/seguimiento.php?codigo=<?= urlencode($d['codigo_seguimiento']) ?>" target="_blank" class="btn btn-outline-primary" title="Seguimiento en Vivo">
                                            <i class="bi bi-eye"></i> Rastrear
                                        </a>

                                        <?php if (in_array($d['estado'], ['creado', 'publicado', 'buscando_domiciliario', 'aceptado'])): ?>
                                            <form method="POST" action="" class="d-inline ms-1" onsubmit="return confirm('¿Seguro que deseas cancelar este domicilio?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="cancelar">
                                                <input type="hidden" name="domicilio_id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Cancelar Pedido"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

