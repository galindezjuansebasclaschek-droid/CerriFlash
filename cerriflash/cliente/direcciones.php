<?php
$pageTitle = 'Mis Direcciones - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('cliente');

$user = current_user();
$db = Database::getConnection();

// Cargar cliente ID
$stmtC = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmtC->execute([$user['id']]);
$clienteId = (int)$stmtC->fetchColumn();

// Acciones POST (Crear, Eliminar, Predeterminada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $action = $_POST['action'];

        if ($action === 'crear') {
            $etiqueta     = trim($_POST['etiqueta'] ?? 'Casa');
            $direccion    = trim($_POST['direccion'] ?? '');
            $barrio       = trim($_POST['barrio'] ?? '');
            $indicaciones = trim($_POST['indicaciones'] ?? '');
            $predet       = isset($_POST['es_predeterminada']) ? 1 : 0;

            if (!empty($direccion)) {
                if ($predet === 1) {
                    $db->prepare("UPDATE direcciones SET es_predeterminada = 0 WHERE cliente_id = ?")->execute([$clienteId]);
                }

                $stmtIns = $db->prepare("
                    INSERT INTO direcciones (cliente_id, etiqueta, direccion, barrio, indicaciones, es_predeterminada)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([$clienteId, $etiqueta, $direccion, $barrio, $indicaciones, $predet]);

                // Actualizar dirección en clientes si es predeterminada
                if ($predet === 1) {
                    $db->prepare("UPDATE clientes SET direccion_frecuente = ?, barrio = ? WHERE id = ?")->execute([$direccion, $barrio, $clienteId]);
                }

                set_flash('success', 'Dirección agregada a tu libreta correctamente.');
                redirect('cliente/direcciones.php');
            }
        } elseif ($action === 'eliminar') {
            $dirId = (int)($_POST['direccion_id'] ?? 0);
            $stmtDel = $db->prepare("DELETE FROM direcciones WHERE id = ? AND cliente_id = ?");
            $stmtDel->execute([$dirId, $clienteId]);

            set_flash('info', 'Dirección eliminada.');
            redirect('cliente/direcciones.php');
        } elseif ($action === 'marcar_predeterminada') {
            $dirId = (int)($_POST['direccion_id'] ?? 0);
            $db->prepare("UPDATE direcciones SET es_predeterminada = 0 WHERE cliente_id = ?")->execute([$clienteId]);
            $db->prepare("UPDATE direcciones SET es_predeterminada = 1 WHERE id = ? AND cliente_id = ?")->execute([$dirId, $clienteId]);

            set_flash('success', 'Dirección marcada como predeterminada.');
            redirect('cliente/direcciones.php');
        }
    }
}

// Cargar direcciones del cliente
$stmtList = $db->prepare("SELECT * FROM direcciones WHERE cliente_id = ? ORDER BY es_predeterminada DESC, id DESC");
$stmtList->execute([$clienteId]);
$direcciones = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-geo-alt text-danger me-2"></i>Libreta de Direcciones</h2>
            <p class="text-muted small mb-0">Guarda tus lugares frecuentes para agilizar la entrega de tus pedidos.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/cliente/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
            </a>
            <button type="button" class="btn btn-primary-cf btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaDireccion">
                <i class="bi bi-plus-circle me-1"></i> Nueva Dirección
            </button>
        </div>
    </div>

    <!-- Listado de Direcciones -->
    <div class="row g-4">
        <?php if (empty($direcciones)): ?>
            <div class="col-12">
                <div class="card-cf bg-white p-5 text-center text-muted">
                    <i class="bi bi-pin-map display-4 text-secondary opacity-50 mb-3 d-block"></i>
                    <h5>No tienes direcciones registradas en tu libreta</h5>
                    <p class="small mb-3">Agrega tu casa, oficina o dirección preferida para recibir tus entregas.</p>
                    <button type="button" class="btn btn-primary-cf" data-bs-toggle="modal" data-bs-target="#modalNuevaDireccion">
                        <i class="bi bi-plus-lg me-1"></i> Agregar Primera Dirección
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($direcciones as $dir): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-cf bg-white p-4 h-100 position-relative <?= $dir['es_predeterminada'] ? 'border-primary border-2 shadow-sm' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary-subtle text-secondary fw-bold text-uppercase">
                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= e($dir['etiqueta']) ?>
                            </span>
                            <?php if ($dir['es_predeterminada']): ?>
                                <span class="badge bg-success-subtle text-success small fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Predeterminada
                                </span>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold mb-1"><?= e($dir['direccion']) ?></h6>
                        <p class="small text-muted mb-2">Barrio: <?= e($dir['barrio'] ?: 'No especificado') ?></p>
                        <?php if (!empty($dir['indicaciones'])): ?>
                            <div class="p-2 bg-light rounded small text-muted mb-3">
                                <em>"<?= e($dir['indicaciones']) ?>"</em>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                            <?php if (!$dir['es_predeterminada']): ?>
                                <form method="POST" action="" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="marcar_predeterminada">
                                    <input type="hidden" name="direccion_id" value="<?= $dir['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 text-primary">Marcar favorita</button>
                                </form>
                            <?php else: ?>
                                <span class="small text-success fw-semibold"><i class="bi bi-star-fill me-1"></i>Principal</span>
                            <?php endif; ?>

                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('¿Eliminar esta dirección?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="direccion_id" value="<?= $dir['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nueva Dirección -->
<div class="modal fade" id="modalNuevaDireccion" tabindex="-1" aria-labelledby="modalNuevaDirLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="crear">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalNuevaDirLabel">
                        <i class="bi bi-geo-alt-fill text-danger me-2"></i>Nueva Dirección de Entrega
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre / Etiqueta *</label>
                        <select class="form-select" name="etiqueta">
                            <option value="Casa" selected>Casa</option>
                            <option value="Trabajo">Trabajo / Oficina</option>
                            <option value="Pareja">Casa de mi Pareja</option>
                            <option value="Familiar">Familiar</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dirección Completa *</label>
                        <input type="text" class="form-control" name="direccion" placeholder="Ej. Calle 8 # 12-45 Apto 201" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Barrio / Sector</label>
                        <input type="text" class="form-control" name="barrio" placeholder="Ej. San Antonio">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Indicaciones de Entrega</label>
                        <textarea class="form-control" name="indicaciones" rows="2" placeholder="Ej. Casa blanca con rejas negras, tocar timbre 2."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="es_predeterminada" id="checkPredet" value="1" checked>
                        <label class="form-check-label small" for="checkPredet">
                            Guardar como dirección predeterminada
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-cf">Guardar Dirección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

