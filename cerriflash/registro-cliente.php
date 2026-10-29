<?php
$pageTitle = 'Registro de Clientes - CerriFlash';
require_once __DIR__ . '/config/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombre          = trim($_POST['nombre'] ?? '');
        $identificacion  = trim($_POST['identificacion'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $direccion       = trim($_POST['direccion_frecuente'] ?? '');
        $barrio          = trim($_POST['barrio'] ?? '');
        $ciudad          = trim($_POST['ciudad'] ?? 'El Cerrito');

        // Validaciones
        if (empty($nombre))   $errors[] = 'El nombre completo es obligatorio.';
        if (empty($telefono)) $errors[] = 'El número de teléfono es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo válido.';
        if (strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($password !== $passwordConfirm) $errors[] = 'Las contraseñas no coinciden.';

        $db = Database::getConnection();

        // Validar correo duplicado
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Este correo electrónico ya se encuentra registrado.';
            }
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $hashPassword = password_hash($password, PASSWORD_BCRYPT);
                $apiToken = bin2hex(random_bytes(32));

                // 1. Usuario con rol 'cliente' (rol_id = 4) y estado 'aprobado'
                $stmtUser = $db->prepare("
                    INSERT INTO usuarios (rol_id, email, password, nombre, telefono, estado, api_token) 
                    VALUES (4, ?, ?, ?, ?, 'aprobado', ?)
                ");
                $stmtUser->execute([$email, $hashPassword, $nombre, $telefono, $apiToken]);
                $usuarioId = (int)$db->lastInsertId();

                // 2. Cliente
                $stmtClient = $db->prepare("
                    INSERT INTO clientes (usuario_id, identificacion, direccion_frecuente, ciudad, barrio) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtClient->execute([$usuarioId, $identificacion, $direccion, $ciudad, $barrio]);
                $clienteId = (int)$db->lastInsertId();

                // 3. Dirección predeterminada si la suministró
                if (!empty($direccion)) {
                    $stmtDir = $db->prepare("
                        INSERT INTO direcciones (cliente_id, etiqueta, direccion, barrio, es_predeterminada) 
                        VALUES (?, 'Casa', ?, ?, 1)
                    ");
                    $stmtDir->execute([$clienteId, $direccion, $barrio]);
                }

                $db->commit();

                // Autologin del cliente
                $stmtLoad = $db->prepare("
                    SELECT u.*, r.nombre AS rol_nombre 
                    FROM usuarios u 
                    INNER JOIN roles r ON u.rol_id = r.id 
                    WHERE u.id = ?
                ");
                $stmtLoad->execute([$usuarioId]);
                $newUser = $stmtLoad->fetch();

                login_user($newUser);
                set_flash('success', '¡Cuenta creada con éxito! Bienvenido a CerriFlash.');
                redirect('cliente/index.php');

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Error al registrar tu cuenta de cliente: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card-cf p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase">Clientes</span>
                    <h2 class="fw-bold mt-2 brand-text">Regístrate en Cerri<span>Flash</span></h2>
                    <p class="text-muted small">Haz seguimiento a tus domicilios y pedidos en tiempo real.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/registro-cliente.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" placeholder="Ej. Camila Morales" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono Celular *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($_POST['telefono'] ?? '') ?>" placeholder="Ej. 3123456789" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cédula (Opcional)</label>
                            <input type="text" class="form-control" name="identificacion" value="<?= e($_POST['identificacion'] ?? '') ?>" placeholder="Ej. 111222333">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Correo Electrónico *</label>
                        <input type="email" class="form-control" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="micorreo@ejemplo.com" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" class="form-control" name="password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" name="password_confirm" placeholder="Repite contraseña" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dirección Habitual de Entrega</label>
                        <input type="text" class="form-control" name="direccion_frecuente" value="<?= e($_POST['direccion_frecuente'] ?? '') ?>" placeholder="Ej. Calle 10 # 5-20">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ciudad</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= e($_POST['ciudad'] ?? 'El Cerrito') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Barrio</label>
                            <input type="text" class="form-control" name="barrio" value="<?= e($_POST['barrio'] ?? '') ?>" placeholder="Ej. El Jardín">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-cf w-100 py-3 fw-bold">
                        <i class="bi bi-person-check me-2"></i> Crear mi Cuenta en CerriFlash
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">¿Ya tienes cuenta?</small>
                    <a href="<?= BASE_URL ?>/login.php" class="small fw-bold text-decoration-none ms-1">Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

