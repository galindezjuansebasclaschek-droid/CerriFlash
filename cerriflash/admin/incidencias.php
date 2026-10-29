<?php
$pageTitle = 'Gestión de Incidencias - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Resolver o cambiar estado de incidencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolver') {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $incId  = (int)($_POST['incidencia_id'] ?? 0);
        $estado = $_POST['estado'] ?? 'resuelta';
        $resol  = trim($_POST['resolucion'] ?? '');

        $stmtUp = $db->prepare("UPDATE incidencias SET estado = ?, resolucion = ? WHERE id = ?");
        $stmtUp->execute([$estado, $resol, $incId]);

        set_flash('success', "Incidencia #{$incId} actualizada a estado: {$estado}.");
        redirect('admin/incidencias.php');
    }
}

// Consultar incidencias
$stmt = $db->query("
    SELECT inc.*, 
           d.codigo_seguimiento, d.nombre_cliente, d.telefono_cliente,
           r.nombre_comercial AS restaurante_nombre,
           u_rep.nombre AS reportante_nombre,
           u_rep.telefono AS reportante_telefono,
           rol_rep.nombre AS reportante_rol
    FROM incidencias inc
    INNER JOIN domicilios d ON inc.domicilio_id = d.id
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    INNER JOIN usuarios u_rep ON inc.reportado_por_usuario_id = u_rep.id
    INNER JOIN roles rol_rep ON u_rep.rol_id = rol_rep.id
    ORDER BY inc.created_at DESC
");
$incidencias = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-flag-fill text-danger me-2"></i>Gestión de Incidencias y Novedades</h2>
            <p class="text-muted small mb-0">Atención a problemas reportados en ruta por domiciliarios o comercios.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Listado de Incidencias -->
    <div class="card-cf bg-white p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID / Fecha</th>
                        <th>Código Pedido</th>
                        <th>Reportado Por</th>
                        <th>Tipo / Motivo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidencias)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check fs-1 text-success mb-2 d-block"></i>
                                No hay incidencias registradas en la plataforma. ¡Operación sin novedades!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($incidencias as $inc): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $inc['id'] ?></strong>
                                    <small class="text-muted d-block"><?= format_datetime($inc['created_at']) ?></small>
                                </td>
                                <td>
                                    <strong class="text-primary"><?= e($inc['codigo_seguimiento']) ?></strong>
                                    <small class="text-muted d-block"><?= e($inc['restaurante_nombre']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($inc['reportante_nombre']) ?></div>
                                    <small class="badge bg-secondary text-capitalize"><?= e($inc['reportante_rol']) ?></small>
                                    <small class="text-muted d-block"><?= e($inc['reportante_telefono']) ?></small>
                                </td>
                                <td><span class="badge bg-danger-subtle text-danger"><?= e($inc['tipo_incidencia']) ?></span></td>
                                <td>
                                    <p class="small mb-1"><?= e($inc['descripcion']) ?></p>
                                    <?php if (!empty($inc['resolucion'])): ?>
                                        <div class="p-2 bg-light rounded small text-success">
                                            <strong>Resolución:</strong> <?= e($inc['resolucion']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeColor = match($inc['estado']) {
                                        'abierta'    => 'danger',
                                        'en_proceso' => 'warning text-dark',
                                        'resuelta'   => 'success',
                                        'cerrada'    => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?> text-capitalize px-3 py-2"><?= e($inc['estado']) ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalResolver<?= $inc['id'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Gestionar
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal para gestionar resolución -->
                            <div class="modal fade" id="modalResolver<?= $inc['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content text-start">
                                        <form method="POST" action="">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="resolver">
                                            <input type="hidden" name="incidencia_id" value="<?= $inc['id'] ?>">

                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Gestionar Incidencia #<?= $inc['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <p class="small text-muted mb-3">
                                                    Pedido: <strong><?= e($inc['codigo_seguimiento']) ?></strong> | Reportado por: <strong><?= e($inc['reportante_nombre']) ?></strong>
                                                </p>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Estado de la Incidencia</label>
                                                    <select class="form-select" name="estado">
                                                        <option value="en_proceso" <?= $inc['estado'] === 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                                                        <option value="resuelta" <?= $inc['estado'] === 'resuelta' ? 'selected' : '' ?>>Resuelta (Solución Aplicada)</option>
                                                        <option value="cerrada" <?= $inc['estado'] === 'cerrada' ? 'selected' : '' ?>>Cerrada / Desestimada</option>
                                                        <option value="abierta" <?= $inc['estado'] === 'abierta' ? 'selected' : '' ?>>Abierta</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Nota de Resolución / Dictamen</label>
                                                    <textarea class="form-control" name="resolucion" rows="3" placeholder="Describe cómo se resolvió la novedad..."><?= e($inc['resolucion'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary-cf">Guardar Resolución</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

