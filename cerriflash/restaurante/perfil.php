<?php
$pageTitle = 'Perfil del Restaurante - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('restaurante');

$user = current_user();
$db = Database::getConnection();

// Cargar restaurante
$stmt = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$restaurante = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombreComercial = trim($_POST['nombre_comercial'] ?? '');
        $razonSocial     = trim($_POST['razon_social'] ?? '');
        $responsable     = trim($_POST['nombre_responsable'] ?? '');
        $nit             = trim($_POST['nit'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $direccion       = trim($_POST['direccion'] ?? '');
        $ciudad          = trim($_POST['ciudad'] ?? 'El Cerrito');
        $barrio          = trim($_POST['barrio'] ?? '');
        $tipoNegocio     = trim($_POST['tipo_negocio'] ?? 'Restaurante');
        $horarios        = trim($_POST['horarios'] ?? '');

        if (empty($nombreComercial)) $errors[] = 'El nombre comercial es obligatorio.';
        if (empty($responsable))     $errors[] = 'El nombre del responsable es obligatorio.';
        if (empty($telefono))        $errors[] = 'El teléfono de contacto es obligatorio.';
        if (empty($direccion))       $errors[] = 'La dirección de recogida es obligatoria.';

        // Logo upload opcional
        $logoName = $restaurante['logo'] ?? null;
        if (!empty($_FILES['logo']['name'])) {
            $uploadedLogo = upload_file($_FILES['logo'], LOGOS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 3);
            if ($uploadedLogo) {
                $logoName = $uploadedLogo;
            }
        }

        if (empty($errors)) {
            // Actualizar tabla usuarios (nombre y telefono)
            $stmtU = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
            $stmtU->execute([$responsable, $telefono, $user['id']]);

            // Actualizar tabla restaurantes
            $stmtR = $db->prepare("
                UPDATE restaurantes SET
                    nombre_comercial = ?,
                    razon_social = ?,
                    nit = ?,
                    nombre_responsable = ?,
                    telefono_contacto = ?,
                    direccion = ?,
                    ciudad = ?,
                    barrio = ?,
                    tipo_negocio = ?,
                    horarios = ?,
                    logo = ?
                WHERE usuario_id = ?
            ");
            $stmtR->execute([
                $nombreComercial, $razonSocial, $nit, $responsable,
                $telefono, $direccion, $ciudad, $barrio,
                $tipoNegocio, $horarios, $logoName, $user['id']
            ]);

            $_SESSION['user']['nombre'] = $responsable;
            $_SESSION['user']['telefono'] = $telefono;

            set_flash('success', '¡Información del establecimiento comercial actualizada exitosamente!');
            redirect('restaurante/perfil.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-gear-wide-connected text-danger me-2"></i>Perfil del Establecimiento</h2>
            <p class="text-muted small mb-0">Gestiona los datos de contacto, dirección de recogida y horarios de tu negocio en CerriFlash.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/restaurante/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
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
        <div class="col-lg-4">
            <div class="card-cf bg-white p-4 text-center">
                <?php if (!empty($restaurante['logo'])): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= e($restaurante['logo']) ?>" class="img-thumbnail rounded-3 mb-3" style="max-height: 140px; max-width: 100%; object-fit: contain;" alt="Logo Negocio">
                <?php else: ?>
                    <div class="p-4 bg-light rounded-3 mb-3 border">
                        <i class="bi bi-shop display-3 text-secondary"></i>
                        <small class="text-muted d-block mt-2">Sin logotipo cargado</small>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-1"><?= e($restaurante['nombre_comercial'] ?? 'Mi Negocio') ?></h5>
                <p class="text-muted small mb-2"><?= e($restaurante['tipo_negocio'] ?? 'Restaurante') ?></p>
                <div class="mb-3"><?= get_status_badge($user['estado']) ?></div>

                <div class="border-top pt-3 text-start small text-muted">
                    <p class="mb-1"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= e($restaurante['direccion'] ?? 'N/A') ?></p>
                    <p class="mb-1"><i class="bi bi-telephone me-1 text-success"></i> <?= e($restaurante['telefono_contacto'] ?? $user['telefono']) ?></p>
                    <p class="mb-0"><i class="bi bi-clock me-1 text-primary"></i> <?= e($restaurante['horarios'] ?: 'Horario flexible') ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-cf bg-white p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>Actualizar Datos del Comercio</h5>

                <form method="POST" action="" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre Comercial *</label>
                            <input type="text" class="form-control" name="nombre_comercial" value="<?= e($restaurante['nombre_comercial'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Razón Social</label>
                            <input type="text" class="form-control" name="razon_social" value="<?= e($restaurante['razon_social'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">NIT o Cédula *</label>
                            <input type="text" class="form-control" name="nit" value="<?= e($restaurante['nit'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tipo de Negocio *</label>
                            <select class="form-select" name="tipo_negocio">
                                <?php
                                $tipos = ['Restaurante', 'Pizzería', 'Comidas Rápidas', 'Panadería y Repostería', 'Farmacia y Droguería', 'Minimarket y Tienda', 'Licores', 'Empresa / Envíos', 'Otro'];
                                foreach ($tipos as $t): ?>
                                    <option value="<?= $t ?>" <?= ($restaurante['tipo_negocio'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre del Propietario / Responsable *</label>
                            <input type="text" class="form-control" name="nombre_responsable" value="<?= e($restaurante['nombre_responsable'] ?? $user['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono de Contacto *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($restaurante['telefono_contacto'] ?? $user['telefono']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Dirección de Recogida *</label>
                            <input type="text" class="form-control" name="direccion" value="<?= e($restaurante['direccion'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ciudad</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= e($restaurante['ciudad'] ?? 'El Cerrito') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Barrio</label>
                            <input type="text" class="form-control" name="barrio" value="<?= e($restaurante['barrio'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Horarios de Atención</label>
                            <input type="text" class="form-control" name="horarios" value="<?= e($restaurante['horarios'] ?? '') ?>" placeholder="Ej. 11:00 AM - 10:00 PM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cambiar Logo (Imagen PNG/JPG)</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
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

