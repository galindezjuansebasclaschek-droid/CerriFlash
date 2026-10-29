<?php
$pageTitle = 'Conviértete en domiciliario CerriFlash';
require_once __DIR__ . '/config/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombre          = trim($_POST['nombre'] ?? '');
        $identificacion  = trim($_POST['identificacion'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $direccion       = trim($_POST['direccion'] ?? '');
        $ciudad          = trim($_POST['ciudad'] ?? 'El Cerrito');
        $tipoVehiculo    = trim($_POST['tipo_vehiculo'] ?? 'motocicleta');
        $placaVehiculo   = trim($_POST['placa_vehiculo'] ?? '');
        $modeloVehiculo  = trim($_POST['modelo_vehiculo'] ?? '');

        // Validaciones
        if (empty($nombre))         $errors[] = 'El nombre completo es obligatorio.';
        if (empty($identificacion)) $errors[] = 'El número de identificación es obligatorio.';
        if (empty($telefono))       $errors[] = 'El número telefónico es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';
        if (strlen($password) < 6)  $errors[] = 'La contraseña debe tener mínimo 6 caracteres.';
        if ($password !== $passwordConfirm) $errors[] = 'Las contraseñas no coinciden.';
        if ($tipoVehiculo !== 'bicicleta' && empty($placaVehiculo)) {
            $errors[] = 'Para vehículos a motor la placa es obligatoria.';
        }

        $db = Database::getConnection();

        // Validar correo o identificación duplicados
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Este correo electrónico ya se encuentra registrado.';
            }

            $stmtId = $db->prepare("SELECT id FROM domiciliarios WHERE identificacion = ?");
            $stmtId->execute([$identificacion]);
            if ($stmtId->fetch()) {
                $errors[] = 'Este número de identificación ya está registrado como domiciliario.';
            }
        }

        // Subidas
        $fotoName     = null;
        $licenciaName = null;
        $soatName     = null;

        if (empty($errors)) {
            if (!empty($_FILES['foto_perfil']['name'])) {
                $fotoName = upload_file($_FILES['foto_perfil'], FOTOS_PATH, ['jpg', 'jpeg', 'png'], 3);
            }
            if (!empty($_FILES['licencia_conduccion']['name'])) {
                $licenciaName = upload_file($_FILES['licencia_conduccion'], DOCS_PATH, ['pdf', 'jpg', 'jpeg', 'png'], 5);
            }
            if (!empty($_FILES['soat']['name'])) {
                $soatName = upload_file($_FILES['soat'], DOCS_PATH, ['pdf', 'jpg', 'jpeg', 'png'], 5);
            }

            try {
                $db->beginTransaction();

                // 1. Crear usuario con rol 'domiciliario' (rol_id = 3) y estado 'pendiente'
                $hashPassword = password_hash($password, PASSWORD_BCRYPT);
                $apiToken = bin2hex(random_bytes(32));

                $stmtUser = $db->prepare("
                    INSERT INTO usuarios (rol_id, email, password, nombre, telefono, estado, api_token) 
                    VALUES (3, ?, ?, ?, ?, 'pendiente', ?)
                ");
                $stmtUser->execute([$email, $hashPassword, $nombre, $telefono, $apiToken]);
                $usuarioId = (int)$db->lastInsertId();

                // 2. Crear registro en domiciliarios
                $stmtDomi = $db->prepare("
                    INSERT INTO domiciliarios (
                        usuario_id, identificacion, fecha_nacimiento, direccion, ciudad, 
                        foto_perfil, tipo_vehiculo, placa_vehiculo, modelo_vehiculo, 
                        licencia_conduccion, soat, disponible
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $stmtDomi->execute([
                    $usuarioId, $identificacion, !empty($fechaNacimiento) ? $fechaNacimiento : null,
                    $direccion, $ciudad, $fotoName, $tipoVehiculo, $placaVehiculo, $modeloVehiculo,
                    $licenciaName, $soatName
                ]);

                // 3. Notificación a admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, url_accion)
                    VALUES (1, 'Nuevo domiciliario postulado', ?, 'info', 'admin/domiciliarios.php')
                ");
                $stmtNotif->execute(["El repartidor {$nombre} se ha postulado y espera aprobación."]);

                $db->commit();

                set_flash('success', '¡Postulación enviada exitosamente! Tu cuenta de Domiciliario CerriFlash será verificada por el administrador.');
                redirect('login.php');

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Error al registrar domiciliario: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card-cf p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold text-uppercase">Equipo Repartidores</span>
                    <h2 class="fw-bold mt-2 brand-text">Conviértete en Domiciliario Cerri<span>Flash</span></h2>
                    <p class="text-muted small">Genera ingresos conectándote en tus tiempos libres con las mejores tarifas por trayecto.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Por favor revisa estos campos:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/registro-domiciliario.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-person me-2 text-success"></i>1. Información Personal
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre Completo *</label>
                            <input type="text" class="form-control" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" placeholder="Ej. Juan Andrés Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cédula / Documento de Identidad *</label>
                            <input type="text" class="form-control" name="identificacion" value="<?= e($_POST['identificacion'] ?? '') ?>" placeholder="Ej. 1114567890" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" value="<?= e($_POST['fecha_nacimiento'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono Celular / WhatsApp *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($_POST['telefono'] ?? '') ?>" placeholder="Ej. 3181234567" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Foto de Perfil (Rostro claro)</label>
                            <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Dirección de Residencia</label>
                            <input type="text" class="form-control" name="direccion" value="<?= e($_POST['direccion'] ?? '') ?>" placeholder="Ej. Carrera 10 # 8-15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ciudad</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= e($_POST['ciudad'] ?? 'El Cerrito') ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-bicycle me-2 text-success"></i>2. Vehículo y Documentos
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo de Vehículo *</label>
                            <select class="form-select" name="tipo_vehiculo" id="tipoVehiculoSelect" required>
                                <option value="motocicleta" selected>Motocicleta</option>
                                <option value="bicicleta">Bicicleta</option>
                                <option value="automovil">Automóvil</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Placa del Vehículo</label>
                            <input type="text" class="form-control text-uppercase" name="placa_vehiculo" value="<?= e($_POST['placa_vehiculo'] ?? '') ?>" placeholder="Ej. ABC12D">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Marca / Modelo</label>
                            <input type="text" class="form-control" name="modelo_vehiculo" value="<?= e($_POST['modelo_vehiculo'] ?? '') ?>" placeholder="Ej. Bajaj Boxer 100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Licencia de Conducción (PDF o Foto)</label>
                            <input type="file" class="form-control" name="licencia_conduccion" accept=".pdf,image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">SOAT Vigente (PDF o Foto)</label>
                            <input type="file" class="form-control" name="soat" accept=".pdf,image/*">
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-lock me-2 text-success"></i>3. Credenciales de Acceso
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Correo Electrónico (Tu usuario) *</label>
                            <input type="email" class="form-control" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="miemail@correo.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" class="form-control" name="password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" name="password_confirm" placeholder="Repite tu contraseña" required>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Una vez aprobado por administración, podrás activar el estado <strong>🟢 Disponible</strong> en tu panel para empezar a recibir notificaciones de entregas.
                    </div>

                    <button type="submit" class="btn btn-primary-cf w-100 py-3 fw-bold fs-5">
                        <i class="bi bi-check2-circle me-2"></i> Enviar Postulación como Domiciliario
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">¿Ya eres repartidor CerriFlash?</small>
                    <a href="<?= BASE_URL ?>/login.php" class="small fw-bold text-decoration-none ms-1">Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

