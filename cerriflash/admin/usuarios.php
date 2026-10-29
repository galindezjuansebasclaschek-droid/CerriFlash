<?php
$pageTitle = 'Gestión de Usuarios - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
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

            // Notificación interna al usuario
            $stmtNotif = $db->prepare("
                INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) 
                VALUES (?, 'Actualización de cuenta en CerriFlash', ?, 'info')
            ");
            $stmtNotif->execute([$usuarioId, "Tu cuenta ha pasado a estado: " . strtoupper($nuevoEstado)]);

            set_flash('success', "Estado del usuario #{$usuarioId} actualizado a: {$nuevoEstado}.");
            redirect('admin/usuarios.php?' . http_build_query($_GET));
        }
    }
}

// Filtros
$filtroRol    = $_GET['rol'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$busqueda     = trim($_GET['q'] ?? '');

$sql = "
    SELECT u.*, r.nombre AS rol_nombre,
           COALESCE(rest.nombre_comercial, '') AS comercio_nombre,
           COALESCE(dom.tipo_vehiculo, '') AS vehiculo,
           COALESCE(dom.placa_vehiculo, '') AS placa
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    LEFT JOIN restaurantes rest ON rest.usuario_id = u.id
    LEFT JOIN domiciliarios dom ON dom.usuario_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($filtroRol)) {
    $sql .= " AND r.nombre = ?";
    $params[] = $filtroRol;
}

if (!empty($filtroEstado)) {
    $sql .= " AND u.estado = ?";
    $params[] = $filtroEstado;
}

if (!empty($busqueda)) {
    $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ? OR u.telefono LIKE ? OR rest.nombre_comercial LIKE ?)";
    $like = "%{$busqueda}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Submenú de administración -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-people-fill text-danger me-2"></i>Gestión de Usuarios Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Supervisión, aprobación y control de accesos para comercios, repartidores y clientes.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/domiciliarios-activos.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-radar me-1"></i> Repartidores en Línea
            </a>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card-cf bg-white p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/usuarios.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por nombre, correo o comercio...">
                </div>
            </div>

            <div class="col-md-3">
                <select class="form-select" name="rol">
                    <option value="">-- Todos los Roles --</option>
                    <option value="restaurante" <?= $filtroRol === 'restaurante' ? 'selected' : '' ?>>Restaurante / Empresa</option>
                    <option value="domiciliario" <?= $filtroRol === 'domiciliario' ? 'selected' : '' ?>>Domiciliario</option>
                    <option value="cliente" <?= $filtroRol === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="admin" <?= $filtroRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>

            <div class="col-md-3">
                <select class="form-select" name="estado">
                    <option value="">-- Todos los Estados --</option>
                    <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="aprobado" <?= $filtroEstado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="suspendido" <?= $filtroEstado === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                    <option value="rechazado" <?= $filtroEstado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-cf w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <?php if (!empty($busqueda) || !empty($filtroRol) || !empty($filtroEstado)): ?>
                    <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="card-cf bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Total Encontrados: <span class="text-primary"><?= count($usuarios) ?></span></h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Usuario / Negocio</th>
                        <th>Contacto</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Alta</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No se encontraron usuarios con los criterios de búsqueda especificados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= e($u['nombre']) ?></div>
                                    <?php if ($u['rol_nombre'] === 'restaurante' && !empty($u['comercio_nombre'])): ?>
                                        <small class="text-primary fw-semibold"><i class="bi bi-shop me-1"></i><?= e($u['comercio_nombre']) ?></small>
                                    <?php elseif ($u['rol_nombre'] === 'domiciliario'): ?>
                                        <small class="text-success"><i class="bi bi-bicycle me-1"></i><?= e($u['vehiculo']) ?> <?= !empty($u['placa']) ? '('.e($u['placa']).')' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><i class="bi bi-envelope me-1 text-muted"></i><?= e($u['email']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($u['telefono']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark text-capitalize"><?= e($u['rol_nombre']) ?></span>
                                </td>
                                <td>
                                    <?= get_status_badge($u['estado']) ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= format_datetime($u['created_at']) ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Ver Perfil / Documentos -->
                                        <a href="<?= BASE_URL ?>/admin/usuario-detalle.php?id=<?= $u['id'] ?>" class="btn btn-outline-primary" title="Ver Detalle y Documentos">
                                            <i class="bi bi-eye-fill me-1"></i>Detalles
                                        </a>

                                        <?php if ($u['rol_nombre'] !== 'admin'): ?>
                                            <?php if ($u['estado'] === 'pendiente'): ?>
                                                <form method="POST" action="" class="d-inline ms-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="aprobar">
                                                    <button type="submit" class="btn btn-success" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                                                </form>
                                                <form method="POST" action="" class="d-inline ms-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="rechazar">
                                                    <button type="submit" class="btn btn-outline-danger" title="Rechazar" onclick="return confirm('¿Rechazar este usuario?');"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            <?php elseif ($u['estado'] === 'aprobado'): ?>
                                                <form method="POST" action="" class="d-inline ms-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="suspender">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Suspender" onclick="return confirm('¿Suspender cuenta?');"><i class="bi bi-slash-circle"></i></button>
                                                </form>
                                            <?php elseif (in_array($u['estado'], ['suspendido', 'rechazado'])): ?>
                                                <form method="POST" action="" class="d-inline ms-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="activar">
                                                    <button type="submit" class="btn btn-outline-success" title="Reactivar"><i class="bi bi-arrow-repeat"></i> Reactivar</button>
                                                </form>
                                            <?php endif; ?>
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

