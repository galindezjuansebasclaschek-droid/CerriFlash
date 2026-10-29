<?php
$pageTitle = 'Panel de Administración - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Procesar aprobaciones / rechazos / suspensiones de usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        $action    = $_POST['action'];

        if (in_array($action, ['aprobar', 'rechazar', 'suspender', 'activar'])) {
            $nuevoEstado = match($action) {
                'aprobar', 'activar' => 'aprobado',
                'rechazar'           => 'rechazado',
                'suspender'          => 'suspendido'
            };

            $stmtUpdate = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $stmtUpdate->execute([$nuevoEstado, $usuarioId]);

            // Notificación al usuario afectado
            $stmtNotif = $db->prepare("
                INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) 
                VALUES (?, 'Estado de tu cuenta en CerriFlash', ?, 'info')
            ");
            $msg = "Tu cuenta en CerriFlash ha cambiado al estado: " . strtoupper($nuevoEstado);
            $stmtNotif->execute([$usuarioId, $msg]);

            set_flash('success', "Usuario #{$usuarioId} actualizado a estado: {$nuevoEstado}.");
            redirect('admin/index.php');
        }
    }
}

// Estadísticas para el Administrador
$stats = [
    'total_usuarios'    => $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'restaurantes'      => $db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 2")->fetchColumn(),
    'domiciliarios'     => $db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 3")->fetchColumn(),
    'pendientes'        => $db->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'pendiente'")->fetchColumn(),
    'domicilios_hoy'    => $db->query("SELECT COUNT(*) FROM domicilios WHERE DATE(fecha_creacion) = CURDATE()")->fetchColumn(),
    'ingresos_comision' => $db->query("SELECT COALESCE(SUM(comision_plataforma), 0) FROM comisiones")->fetchColumn()
];

// Usuarios pendientes de aprobación
$stmtPendientes = $db->prepare("
    SELECT u.id, u.nombre, u.email, u.telefono, u.estado, u.created_at, r.nombre AS rol_nombre,
           COALESCE(rest.nombre_comercial, '') AS comercio_nombre,
           COALESCE(dom.tipo_vehiculo, '') AS vehiculo,
           COALESCE(dom.placa_vehiculo, '') AS placa
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    LEFT JOIN restaurantes rest ON rest.usuario_id = u.id
    LEFT JOIN domiciliarios dom ON dom.usuario_id = u.id
    WHERE u.estado = 'pendiente'
    ORDER BY u.created_at DESC
");
$stmtPendientes->execute();
$usuariosPendientes = $stmtPendientes->fetchAll();

// Todos los usuarios recientes
$stmtRecientes = $db->prepare("
    SELECT u.id, u.nombre, u.email, u.telefono, u.estado, u.created_at, r.nombre AS rol_nombre
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    ORDER BY u.created_at DESC
    LIMIT 10
");
$stmtRecientes->execute();
$usuariosRecientes = $stmtRecientes->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Encabezado de bienvenida Admin -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-shield-shaded text-danger me-2"></i>Panel Administrativo Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Gestión global de la plataforma, aprobación de cuentas y monitoreo operativo.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-person-fill-gear me-1"></i> Sesión SuperAdmin
            </span>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Total Usuarios</small>
                        <h3 class="fw-bold mb-0"><?= number_format($stats['total_usuarios']) ?></h3>
                    </div>
                    <div class="fs-1 text-primary opacity-50"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Pendientes de Aprobación</small>
                        <h3 class="fw-bold text-warning mb-0"><?= number_format($stats['pendientes']) ?></h3>
                    </div>
                    <div class="fs-1 text-warning opacity-50"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Comercios Afiliados</small>
                        <h3 class="fw-bold mb-0"><?= number_format($stats['restaurantes']) ?></h3>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="bi bi-shop"></i></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card-cf p-3 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Domiciliarios Registrados</small>
                        <h3 class="fw-bold mb-0"><?= number_format($stats['domiciliarios']) ?></h3>
                    </div>
                    <div class="fs-1 text-info opacity-50"><i class="bi bi-scooter"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Solicitudes Pendientes de Aprobación -->
    <div class="card-cf bg-white p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-person-check-fill text-warning me-2"></i> Solicitudes Pendientes de Aprobación
            </h5>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?= count($usuariosPendientes) ?> Pendientes</span>
        </div>

        <?php if (empty($usuariosPendientes)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                <p class="mb-0">No hay comercios ni domiciliarios pendientes de revisión. ¡Todo al día!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Usuario / Contacto</th>
                            <th>Tipo / Rol</th>
                            <th>Detalles Adicionales</th>
                            <th>Fecha Registro</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuariosPendientes as $pend): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($pend['nombre']) ?></div>
                                    <small class="text-muted"><?= e($pend['email']) ?> | Tel: <?= e($pend['telefono']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-capitalize"><?= e($pend['rol_nombre']) ?></span>
                                </td>
                                <td>
                                    <?php if ($pend['rol_nombre'] === 'restaurante'): ?>
                                        <small class="fw-semibold text-primary"><i class="bi bi-shop me-1"></i><?= e($pend['comercio_nombre']) ?></small>
                                    <?php elseif ($pend['rol_nombre'] === 'domiciliario'): ?>
                                        <small class="fw-semibold text-success"><i class="bi bi-bicycle me-1"></i><?= e($pend['vehiculo']) ?> <?= $pend['placa'] ? '('.e($pend['placa']).')' : '' ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= format_datetime($pend['created_at']) ?></small></td>
                                <td><?= get_status_badge($pend['estado']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="<?= BASE_URL ?>/admin/index.php" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="usuario_id" value="<?= $pend['id'] ?>">
                                            <input type="hidden" name="action" value="aprobar">
                                            <button type="submit" class="btn btn-success" title="Aprobar"><i class="bi bi-check-lg me-1"></i>Aprobar</button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/index.php" class="d-inline ms-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="usuario_id" value="<?= $pend['id'] ?>">
                                            <input type="hidden" name="action" value="rechazar">
                                            <button type="submit" class="btn btn-outline-danger" title="Rechazar" onclick="return confirm('¿Seguro que deseas rechazar este usuario?');"><i class="bi bi-x-lg me-1"></i>Rechazar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lista de Usuarios Registrados Recientes -->
    <div class="card-cf bg-white p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>Usuarios Registrados Recientes</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acción Rápida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuariosRecientes as $usr): ?>
                        <tr>
                            <td>#<?= $usr['id'] ?></td>
                            <td class="fw-semibold"><?= e($usr['nombre']) ?></td>
                            <td><?= e($usr['email']) ?></td>
                            <td><?= e($usr['telefono']) ?></td>
                            <td><span class="badge bg-dark text-capitalize"><?= e($usr['rol_nombre']) ?></span></td>
                            <td><?= get_status_badge($usr['estado']) ?></td>
                            <td><small class="text-muted"><?= format_datetime($usr['created_at']) ?></small></td>
                            <td class="text-end">
                                <?php if ($usr['rol_nombre'] !== 'admin'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/index.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="usuario_id" value="<?= $usr['id'] ?>">
                                        <?php if ($usr['estado'] === 'suspendido'): ?>
                                            <input type="hidden" name="action" value="activar">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Reactivar</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="suspender">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('¿Deseas suspender este usuario?');">Suspender</button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inmutable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

