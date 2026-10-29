<?php
$pageTitle = 'Mi Perfil - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('cliente');

$user = current_user();
$db = Database::getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombre   = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($nombre))   $errors[] = 'El nombre es obligatorio.';
        if (empty($telefono)) $errors[] = 'El teléfono es obligatorio.';

        if (!empty($password)) {
            if (strlen($password) < 6) $errors[] = 'La nueva clave debe tener al menos 6 caracteres.';
            if ($password !== $passwordConfirm) $errors[] = 'Las claves no coinciden.';
        }

        if (empty($errors)) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $telefono, $hash, $user['id']]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
                $stmt->execute([$nombre, $telefono, $user['id']]);
            }

            $_SESSION['user']['nombre'] = $nombre;
            $_SESSION['user']['telefono'] = $telefono;

            set_flash('success', '¡Datos de tu cuenta actualizados con éxito!');
            redirect('cliente/perfil.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-person-gear text-primary me-2"></i>Mi Perfil</h2>
            <p class="text-muted small mb-0">Gestiona tu información personal y seguridad de acceso.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/cliente/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Mis Pedidos
            </a>
            <a href="<?= BASE_URL ?>/cliente/direcciones.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-geo-alt me-1"></i> Mis Direcciones
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

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-cf bg-white p-4">
                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre" value="<?= e($user['nombre']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico (No modificable)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono Celular *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($user['telefono']) ?>" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-key me-2 text-warning"></i>Cambiar Contraseña (Opcional)</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nueva Contraseña</label>
                            <input type="password" class="form-control" name="password" placeholder="Dejar en blanco para conservar">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" name="password_confirm" placeholder="Repite la contraseña">
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

