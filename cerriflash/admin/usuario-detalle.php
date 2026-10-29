<?php
$pageTitle = 'Detalle de Usuario y Documentación - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    set_flash('danger', 'Usuario no especificado.');
    redirect('admin/usuarios.php');
}

// Cargar usuario y rol
$stmt = $db->prepare("
    SELECT u.*, r.nombre AS rol_nombre 
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    set_flash('danger', 'Usuario no encontrado.');
    redirect('admin/usuarios.php');
}

// Procesar cambio de estado o notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $nuevoEstado = $_POST['nuevo_estado'];
        if (in_array($nuevoEstado, ['pendiente', 'aprobado', 'rechazado', 'suspendido'])) {
            $stmtUp = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $stmtUp->execute([$nuevoEstado, $id]);

            // Notificación
            $msg = "Tu cuenta en CerriFlash ha sido actualizada a: " . strtoupper($nuevoEstado);
            $stmtNotif = $db->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (?, 'Actualización de cuenta', ?, 'info')");
            $stmtNotif->execute([$id, $msg]);

            set_flash('success', "Estado actualizado correctamente a: {$nuevoEstado}.");
            redirect("admin/usuario-detalle.php?id={$id}");
        }
    }
}

// Cargar datos complementarios por rol
$perfil = null;
if ($usuario['rol_nombre'] === 'restaurante') {
    $stmtR = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
    $stmtR->execute([$id]);
    $perfil = $stmtR->fetch();
} elseif ($usuario['rol_nombre'] === 'domiciliario') {
    $stmtD = $db->prepare("SELECT * FROM domiciliarios WHERE usuario_id = ?");
    $stmtD->execute([$id]);
    $perfil = $stmtD->fetch();
} elseif ($usuario['rol_nombre'] === 'cliente') {
    $stmtC = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
    $stmtC->execute([$id]);
    $perfil = $stmtC->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="fw-bold mb-0 brand-text">Expediente de Usuario #<?= $usuario['id'] ?></h2>
                <?= get_status_badge($usuario['estado']) ?>
            </div>
            <p class="text-muted small mb-0">Revisión de identidad, documentación adjunta y autorización de actividad.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Datos Principales -->
        <div class="col-lg-5">
            <div class="card-cf bg-white p-4 mb-4">
                <div class="text-center mb-4">
                    <?php if ($usuario['rol_nombre'] === 'domiciliario' && !empty($perfil['foto_perfil'])): ?>
                        <img src="<?= UPLOADS_URL ?>/fotos/<?= e($perfil['foto_perfil']) ?>" class="rounded-circle img-thumbnail mb-2" style="width: 110px; height: 110px; object-fit: cover;" alt="Foto Domiciliario">
                    <?php elseif ($usuario['rol_nombre'] === 'restaurante' && !empty($perfil['logo'])): ?>
                        <img src="<?= UPLOADS_URL ?>/logos/<?= e($perfil['logo']) ?>" class="rounded-3 img-thumbnail mb-2" style="width: 110px; height: 110px; object-fit: contain;" alt="Logo Comercio">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 90px; height: 90px;">
                            <i class="bi bi-person-fill display-5 text-secondary"></i>
                        </div>
                    <?php endif; ?>

                    <h4 class="fw-bold mb-0"><?= e($usuario['nombre']) ?></h4>
                    <span class="badge bg-dark text-capitalize px-3 py-1 mt-1"><?= e($usuario['rol_nombre']) ?></span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Correo:</span>
                        <strong><?= e($usuario['email']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Teléfono:</span>
                        <strong><?= e($usuario['telefono']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Fecha de Registro:</span>
                        <span><?= format_datetime($usuario['created_at']) ?></span>
                    </li>
                </ul>

                <!-- Acciones de Estado -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-2">Modificar Estado de la Cuenta:</h6>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" name="nuevo_estado" required>
                                <option value="aprobado" <?= $usuario['estado'] === 'aprobado' ? 'selected' : '' ?>>🟢 Aprobado</option>
                                <option value="pendiente" <?= $usuario['estado'] === 'pendiente' ? 'selected' : '' ?>>🟡 Pendiente</option>
                                <option value="suspendido" <?= $usuario['estado'] === 'suspendido' ? 'selected' : '' ?>>⚪ Suspendido</option>
                                <option value="rechazado" <?= $usuario['estado'] === 'rechazado' ? 'selected' : '' ?>>🔴 Rechazado</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary-cf">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Información Específica del Rol y Documentos -->
        <div class="col-lg-7">
            <div class="card-cf bg-white p-4">
                <?php if ($usuario['rol_nombre'] === 'restaurante'): ?>
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">
                        <i class="bi bi-shop me-2 text-danger"></i>Información del Establecimiento Comercial
                    </h5>
                    <?php if ($perfil): ?>
                        <div class="row g-3 small mb-4">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Nombre Comercial:</span>
                                <strong><?= e($perfil['nombre_comercial']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Razón Social:</span>
                                <strong><?= e($perfil['razon_social'] ?: 'No especificada') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">NIT / Identificación:</span>
                                <strong><?= e($perfil['nit']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Tipo de Negocio:</span>
                                <strong><?= e($perfil['tipo_negocio']) ?></strong>
                            </div>
                            <div class="col-sm-8">
                                <span class="text-muted d-block">Dirección de Recogida:</span>
                                <strong><?= e($perfil['direccion']) ?>, <?= e($perfil['barrio'] ?? '') ?> (<?= e($perfil['ciudad']) ?>)</strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Horarios:</span>
                                <strong><?= e($perfil['horarios'] ?: 'No definidos') ?></strong>
                            </div>
                        </div>

                        <!-- Documentos adjuntos -->
                        <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Documentos Adjuntos:</h6>
                        <?php if (!empty($perfil['documento_rut'])): ?>
                            <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2"></i>
                                    <strong>Documento RUT / Cámara de Comercio</strong>
                                </div>
                                <a href="<?= UPLOADS_URL ?>/documentos/<?= e($perfil['documento_rut']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Abrir Documento
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">No se adjuntaron documentos adicionales durante el registro.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small">Perfil de restaurante no registrado en la base de datos.</p>
                    <?php endif; ?>

                <?php elseif ($usuario['rol_nombre'] === 'domiciliario'): ?>
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">
                        <i class="bi bi-bicycle me-2 text-success"></i>Información del Domiciliario y Vehículo
                    </h5>
                    <?php if ($perfil): ?>
                        <div class="row g-3 small mb-4">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Identificación (Cédula):</span>
                                <strong><?= e($perfil['identificacion']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Fecha Nacimiento:</span>
                                <strong><?= e($perfil['fecha_nacimiento'] ?: 'No registrada') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Tipo de Vehículo:</span>
                                <strong class="text-capitalize"><?= e($perfil['tipo_vehiculo']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Placa / Matrícula:</span>
                                <strong><?= e($perfil['placa_vehiculo'] ?: 'No aplica (Bicicleta)') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Modelo / Marca:</span>
                                <strong><?= e($perfil['modelo_vehiculo'] ?: 'No especificado') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Estado Operativo:</span>
                                <strong><?= (int)$perfil['disponible'] === 1 ? '🟢 Disponible (En línea)' : '⚪ Desconectado' ?></strong>
                            </div>
                        </div>

                        <!-- Documentos de tránsito -->
                        <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-check text-success me-2"></i>Documentación de Tránsito:</h6>
                        <div class="d-flex flex-column gap-2">
                            <?php if (!empty($perfil['licencia_conduccion'])): ?>
                                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                    <div><i class="bi bi-card-heading text-primary fs-4 me-2"></i><strong>Licencia de Conducción</strong></div>
                                    <a href="<?= UPLOADS_URL ?>/documentos/<?= e($perfil['licencia_conduccion']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Ver
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($perfil['soat'])): ?>
                                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                    <div><i class="bi bi-shield-check text-success fs-4 me-2"></i><strong>SOAT Vigente</strong></div>
                                    <a href="<?= UPLOADS_URL ?>/documentos/<?= e($perfil['soat']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Ver
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($perfil['licencia_conduccion']) && empty($perfil['soat'])): ?>
                                <p class="text-muted small">El repartidor no ha subido documentos aún.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($usuario['rol_nombre'] === 'cliente'): ?>
                    <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">
                        <i class="bi bi-person text-primary me-2"></i>Información del Cliente
                    </h5>
                    <?php if ($perfil): ?>
                        <div class="row g-3 small">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Identificación:</span>
                                <strong><?= e($perfil['identificacion'] ?: 'No registrada') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Dirección Habitual:</span>
                                <strong><?= e($perfil['direccion_frecuente'] ?: 'No registrada') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Ciudad y Barrio:</span>
                                <strong><?= e($perfil['ciudad']) ?> <?= $perfil['barrio'] ? ' - '.e($perfil['barrio']) : '' ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

