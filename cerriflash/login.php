<?php
$pageTitle = 'Iniciar Sesión - CerriFlash';
require_once __DIR__ . '/config/config.php';

// Si ya está logueado, redirigir a su panel
if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error = 'Token de seguridad inválido. Por favor recarga el formulario.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Por favor ingresa tu correo y contraseña.';
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT u.*, r.nombre AS rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['estado'] === 'suspendido') {
                    $error = 'Tu cuenta ha sido suspendida por el administrador de CerriFlash. Por favor contacta a soporte@cerriflash.com.';
                } elseif ($user['estado'] === 'rechazado') {
                    $error = 'Tu solicitud de registro fue rechazada. Si crees que se trata de un error, comunícate con la administración.';
                } else {
                    // Iniciar sesión
                    login_user($user);

                    // Si está pendiente pero no es admin, informar en flash
                    if ($user['estado'] === 'pendiente' && $user['rol_nombre'] !== 'admin') {
                        set_flash('warning', '¡Bienvenido! Tu cuenta está en estado "Pendiente de Aprobación" por la administración.');
                    } else {
                        set_flash('success', '¡Bienvenido a CerriFlash, ' . htmlspecialchars($user['nombre']) . '!');
                    }

                    redirect_to_dashboard();
                }
            } else {
                $error = 'Correo electrónico o contraseña incorrectos.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card-cf p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <span class="brand-badge mb-2 d-inline-block"><i class="bi bi-lightning-charge-fill"></i></span>
                    <h3 class="fw-bold brand-text">Iniciar Sesión</h3>
                    <p class="text-muted small">Accede a tu cuenta de <strong>CerriFlash</strong></p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <small><?= e($error) ?></small>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/login.php" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="nombre@correo.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold small mb-0">Contraseña</label>
                            <a href="#" class="small text-decoration-none text-muted" onclick="alert('Contacta al administrador para restablecer tu clave.'); return false;">¿Olvidaste tu clave?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-cf w-100 py-2 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Ingresar a CerriFlash
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted d-block mb-2">¿Aún no tienes cuenta?</small>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="<?= BASE_URL ?>/registro-restaurante.php" class="btn btn-sm btn-outline-secondary">Soy Comercio</a>
                        <a href="<?= BASE_URL ?>/registro-domiciliario.php" class="btn btn-sm btn-outline-secondary">Soy Repartidor</a>
                        <a href="<?= BASE_URL ?>/registro-cliente.php" class="btn btn-sm btn-outline-secondary">Soy Cliente</a>
                    </div>
                </div>

                <!-- Credenciales demo para facilidad de prueba -->
                <div class="mt-4 p-3 bg-light rounded-3 border">
                    <small class="fw-bold d-block text-secondary mb-1"><i class="bi bi-info-circle me-1"></i> Cuentas Demo de Prueba:</small>
                    <ul class="list-unstyled small mb-0 text-muted" style="font-size: 0.8rem;">
                        <li><strong>Admin:</strong> admin@cerriflash.com | Clave: admin123</li>
                        <li><strong>Restaurante:</strong> pizzas@cerriflash.com | Clave: admin123</li>
                        <li><strong>Domiciliario:</strong> domi@cerriflash.com | Clave: admin123</li>
                        <li><strong>Cliente:</strong> cliente@cerriflash.com | Clave: admin123</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

