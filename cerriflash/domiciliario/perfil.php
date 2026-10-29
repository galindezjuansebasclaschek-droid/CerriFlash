<?php
$pageTitle = 'Mi Perfil de Domiciliario - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('domiciliario');

$user = current_user();
$db = Database::getConnection();

// Cargar datos actuales
$stmt = $db->prepare("SELECT * FROM domiciliarios WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$domiciliario = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombre         = trim($_POST['nombre'] ?? '');
        $telefono       = trim($_POST['telefono'] ?? '');
        $direccion      = trim($_POST['direccion'] ?? '');
        $ciudad         = trim($_POST['ciudad'] ?? 'El Cerrito');
        $tipoVehiculo   = trim($_POST['tipo_vehiculo'] ?? 'motocicleta');
        $placaVehiculo  = trim($_POST['placa_vehiculo'] ?? '');
        $modeloVehiculo = trim($_POST['modelo_vehiculo'] ?? '');

        if (empty($nombre))   $errors[] = 'El nombre es obligatorio.';
        if (empty($telefono)) $errors[] = 'El teléfono es obligatorio.';
        if ($tipoVehiculo !== 'bicicleta' && empty($placaVehiculo)) {
            $errors[] = 'La placa del vehículo es requerida para vehículos a motor.';
        }

        // Subidas
        $fotoPerfil = $domiciliario['foto_perfil'] ?? null;
        $licencia   = $domiciliario['licencia_conduccion'] ?? null;
        $soat       = $domiciliario['soat'] ?? null;

        if (!empty($_FILES['foto_perfil']['name'])) {
            $upFoto = upload_file($_FILES['foto_perfil'], FOTOS_PATH, ['jpg', 'jpeg', 'png'], 3);
            if ($upFoto) $fotoPerfil = $upFoto;
        }

        if (!empty($_FILES['licencia_conduccion']['name'])) {
            $upLic = upload_file($_FILES['licencia_conduccion'], DOCS_PATH, ['pdf', 'jpg', 'jpeg', 'png'], 5);
            if ($upLic) $licencia = $upLic;
        }

        if (!empty($_FILES['soat']['name'])) {
            $upSoat = upload_file($_FILES['soat'], DOCS_PATH, ['pdf', 'jpg', 'jpeg', 'png'], 5);
            if ($upSoat) $soat = $upSoat;
        }

        if (empty($errors)) {
            // Actualizar usuario
            $stmtU = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
            $stmtU->execute([$nombre, $telefono, $user['id']]);

            // Actualizar domiciliario
            $stmtD = $db->prepare("
                UPDATE domiciliarios SET
                    direccion = ?,
                    ciudad = ?,
                    tipo_vehiculo = ?,
                    placa_vehiculo = ?,
                    modelo_vehiculo = ?,
                    foto_perfil = ?,
                    licencia_conduccion = ?,
                    soat = ?
                WHERE usuario_id = ?
            ");
            $stmtD->execute([
                $direccion, $ciudad, $tipoVehiculo, $placaVehiculo,
                $modeloVehiculo, $fotoPerfil, $licencia, $soat, $user['id']
            ]);

            $_SESSION['user']['nombre'] = $nombre;
            $_SESSION['user']['telefono'] = $telefono;

            set_flash('success', '¡Perfil de repartidor actualizado correctamente!');
            redirect('domiciliario/perfil.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-person-vcard text-success me-2"></i>Perfil del Repartidor</h2>
            <p class="text-muted small mb-0">Mantén al día la información de tu vehículo y tus documentos personales.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/domiciliario/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Radar
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $e): ?>
                    <li><?= e($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Columna Izquierda: Tarjeta Resumen -->
        <div class="col-lg-4">
            <div class="card-cf bg-white p-4 text-center">
                <?php if (!empty($domiciliario['foto_perfil'])): ?>
                    <img src="<?= UPLOADS_URL ?>/fotos/<?= e($domiciliario['foto_perfil']) ?>" class="rounded-circle img-thumbnail mb-3" style="width: 120px; height: 120px; object-fit: cover;" alt="Foto Perfil">
                <?php else: ?>
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                        <i class="bi bi-person-fill display-4 text-secondary"></i>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-0"><?= e($user['nombre']) ?></h5>
                <p class="text-muted small mb-2">C.C. <?= e($domiciliario['identificacion'] ?? 'N/A') ?></p>
                <div class="mb-3"><?= get_status_badge($user['estado']) ?></div>

                <div class="border-top pt-3 text-start small text-muted">
                    <p class="mb-1"><i class="bi bi-scooter me-1 text-success"></i> <strong class="text-capitalize"><?= e($domiciliario['tipo_vehiculo'] ?? 'N/A') ?></strong> (<?= e($domiciliario['placa_vehiculo'] ?: 'Sin placa') ?>)</p>
                    <p class="mb-1"><i class="bi bi-telephone me-1 text-primary"></i> <?= e($user['telefono']) ?></p>
                    <p class="mb-0"><i class="bi bi-envelope me-1 text-secondary"></i> <?= e($user['email']) ?></p>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Formulario de Actualización -->
        <div class="col-lg-8">
            <div class="card-cf bg-white p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-success"></i>Modificar Información</h5>

                <form method="POST" action="" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre Completo *</label>
                            <input type="text" class="form-control" name="nombre" value="<?= e($user['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono Celular *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($user['telefono']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Dirección de Residencia</label>
                            <input type="text" class="form-control" name="direccion" value="<?= e($domiciliario['direccion'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ciudad</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= e($domiciliario['ciudad'] ?? 'El Cerrito') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo de Vehículo *</label>
                            <select class="form-select" name="tipo_vehiculo">
                                <option value="motocicleta" <?= ($domiciliario['tipo_vehiculo'] ?? '') === 'motocicleta' ? 'selected' : '' ?>>Motocicleta</option>
                                <option value="bicicleta" <?= ($domiciliario['tipo_vehiculo'] ?? '') === 'bicicleta' ? 'selected' : '' ?>>Bicicleta</option>
                                <option value="automovil" <?= ($domiciliario['tipo_vehiculo'] ?? '') === 'automovil' ? 'selected' : '' ?>>Automóvil</option>
                                <option value="otro" <?= ($domiciliario['tipo_vehiculo'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Placa de Vehículo</label>
                            <input type="text" class="form-control text-uppercase" name="placa_vehiculo" value="<?= e($domiciliario['placa_vehiculo'] ?? '') ?>" placeholder="Ej. ABC12D">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Marca / Modelo</label>
                            <input type="text" class="form-control" name="modelo_vehiculo" value="<?= e($domiciliario['modelo_vehiculo'] ?? '') ?>" placeholder="Ej. Yamaha Crypton">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cambiar Foto de Perfil</label>
                            <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Actualizar Licencia</label>
                            <input type="file" class="form-control" name="licencia_conduccion" accept=".pdf,image/*">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Actualizar SOAT</label>
                            <input type="file" class="form-control" name="soat" accept=".pdf,image/*">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-cf px-4 py-2 fw-semibold">
                        <i class="bi bi-floppy-fill me-2"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

