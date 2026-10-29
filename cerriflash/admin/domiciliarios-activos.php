<?php
$pageTitle = 'Domiciliarios en Línea - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Consultar domiciliarios con estado
$stmt = $db->prepare("
    SELECT d.*, u.nombre, u.email, u.telefono, u.estado AS estado_usuario
    FROM domiciliarios d
    INNER JOIN usuarios u ON d.usuario_id = u.id
    ORDER BY d.disponible DESC, d.ultima_conexion DESC
");
$stmt->execute();
$domiciliarios = $stmt->fetchAll();

$enLinea = array_filter($domiciliarios, fn($d) => (int)$d['disponible'] === 1);
$desconectados = array_filter($domiciliarios, fn($d) => (int)$d['disponible'] === 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-radar text-success me-2"></i>Monitoreo de Flota Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Disponibilidad en tiempo real de repartidores y cobertura activa.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people me-1"></i> Todos los Usuarios
            </a>
        </div>
    </div>

    <!-- Tarjetas de Conexión -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-cf p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="status-indicator mb-1">
                            <span class="pulse-dot online"></span>
                            <span class="text-success fw-bold">Repartidores Disponibles Ahora</span>
                        </div>
                        <h2 class="fw-bold text-success mb-0"><?= count($enLinea) ?></h2>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="bi bi-scooter"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-cf p-3 border-start border-4 border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="status-indicator mb-1">
                            <span class="pulse-dot offline"></span>
                            <span class="text-muted fw-bold">Repartidores Desconectados</span>
                        </div>
                        <h2 class="fw-bold text-secondary mb-0"><?= count($desconectados) ?></h2>
                    </div>
                    <div class="fs-1 text-secondary opacity-50"><i class="bi bi-moon-stars-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de Domiciliarios -->
    <div class="card-cf bg-white p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Detalle de la Flota de Repartidores</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Repartidor</th>
                        <th>Estado de Red</th>
                        <th>Vehículo</th>
                        <th>Contacto</th>
                        <th>Ciudad</th>
                        <th>Última Conexión</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($domiciliarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No hay domiciliarios registrados en la base de datos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($domiciliarios as $d): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($d['foto_perfil'])): ?>
                                            <img src="<?= UPLOADS_URL ?>/fotos/<?= e($d['foto_perfil']) ?>" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;" alt="Foto">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="bi bi-person text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="fw-bold"><?= e($d['nombre']) ?></span>
                                            <small class="text-muted d-block">CC: <?= e($d['identificacion']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ((int)$d['disponible'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success py-2 px-3 fw-semibold">
                                            <i class="bi bi-broadcast me-1"></i> 🟢 Disponible
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary py-2 px-3 fw-semibold">
                                            ⚪ No disponible
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-capitalize"><?= e($d['tipo_vehiculo']) ?></strong>
                                    <?php if (!empty($d['placa_vehiculo'])): ?>
                                        <small class="badge bg-dark ms-1"><?= e($d['placa_vehiculo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><i class="bi bi-telephone me-1 text-muted"></i><?= e($d['telefono']) ?></div>
                                    <small><a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $d['telefono']) ?>" target="_blank" class="text-success text-decoration-none"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a></small>
                                </td>
                                <td><?= e($d['ciudad']) ?></td>
                                <td><small class="text-muted"><?= format_datetime($d['ultima_conexion']) ?></small></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/admin/usuario-detalle.php?id=<?= $d['usuario_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        Expediente
                                    </a>
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

