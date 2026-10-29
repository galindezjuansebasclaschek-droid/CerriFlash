<?php
$pageTitle = 'Registra tu negocio en CerriFlash';
require_once __DIR__ . '/config/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        // Datos del formulario
        $nombreComercial = trim($_POST['nombre_comercial'] ?? '');
        $razonSocial     = trim($_POST['razon_social'] ?? '');
        $responsable     = trim($_POST['nombre_responsable'] ?? '');
        $nit             = trim($_POST['nit'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $direccion       = trim($_POST['direccion'] ?? '');
        $ciudad          = trim($_POST['ciudad'] ?? 'El Cerrito');
        $barrio          = trim($_POST['barrio'] ?? '');
        $tipoNegocio     = trim($_POST['tipo_negocio'] ?? 'Restaurante');
        $horarios        = trim($_POST['horarios'] ?? '');

        // Validaciones básicas
        if (empty($nombreComercial)) $errors[] = 'El nombre del establecimiento es obligatorio.';
        if (empty($responsable))     $errors[] = 'El nombre del propietario o responsable es obligatorio.';
        if (empty($nit))             $errors[] = 'El NIT o identificación fiscal es obligatorio.';
        if (empty($telefono))        $errors[] = 'El teléfono de contacto es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';
        if (strlen($password) < 6)   $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($password !== $passwordConfirm) $errors[] = 'Las contraseñas no coinciden.';
        if (empty($direccion))       $errors[] = 'La dirección del local es obligatoria.';

        $db = Database::getConnection();

        // Validar correo duplicado
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'El correo electrónico ya se encuentra registrado en CerriFlash.';
            }
        }

        // Subida de archivos (logo y documentos)
        $logoFileName = null;
        $docRutName   = null;

        if (empty($errors)) {
            if (!empty($_FILES['logo']['name'])) {
                $logoFileName = upload_file($_FILES['logo'], LOGOS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 3);
            }
            if (!empty($_FILES['documento_rut']['name'])) {
                $docRutName = upload_file($_FILES['documento_rut'], DOCS_PATH, ['pdf', 'jpg', 'jpeg', 'png'], 5);
            }

            try {
                $db->beginTransaction();

                // 1. Crear usuario con rol 'restaurante' (rol_id = 2) y estado 'pendiente'
                $hashPassword = password_hash($password, PASSWORD_BCRYPT);
                $apiToken = bin2hex(random_bytes(32));

                $stmtUser = $db->prepare("
                    INSERT INTO usuarios (rol_id, email, password, nombre, telefono, estado, api_token) 
                    VALUES (2, ?, ?, ?, ?, 'pendiente', ?)
                ");
                $stmtUser->execute([$email, $hashPassword, $responsable, $telefono, $apiToken]);
                $usuarioId = (int)$db->lastInsertId();

                // 2. Crear registro en restaurantes
                $stmtRest = $db->prepare("
                    INSERT INTO restaurantes (
                        usuario_id, nombre_comercial, razon_social, nit, nombre_responsable, 
                        telefono_contacto, email_contacto, direccion, ciudad, barrio, 
                        tipo_negocio, horarios, logo, documento_rut
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtRest->execute([
                    $usuarioId, $nombreComercial, $razonSocial, $nit, $responsable,
                    $telefono, $email, $direccion, $ciudad, $barrio,
                    $tipoNegocio, $horarios, $logoFileName, $docRutName
                ]);

                // 3. Crear notificación interna para admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, url_accion)
                    VALUES (1, 'Nuevo comercio registrado', ?, 'info', 'admin/usuarios.php')
                ");
                $stmtNotif->execute(["El comercio {$nombreComercial} se ha registrado y espera aprobación."]);

                $db->commit();

                set_flash('success', '¡Registro recibido con éxito! Tu solicitud para "' . htmlspecialchars($nombreComercial) . '" está en proceso de revisión por el administrador de CerriFlash.');
                redirect('login.php');

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Ocurrió un error al procesar el registro: ' . $e->getMessage();
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
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold text-uppercase">Aliados Comerciales</span>
                    <h2 class="fw-bold mt-2 brand-text">Registra tu negocio en Cerri<span>Flash</span></h2>
                    <p class="text-muted small">Conecta tu establecimiento con repartidores disponibles y optimiza tus entregas a domicilio.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Por favor corrige los siguientes inconvenientes:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/registro-restaurante.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-shop me-2 text-danger"></i>1. Datos del Establecimiento
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre Comercial del Establecimiento *</label>
                            <input type="text" class="form-control" name="nombre_comercial" value="<?= e($_POST['nombre_comercial'] ?? '') ?>" placeholder="Ej. Pizzería Bella Italia" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Razón Social (Opcional)</label>
                            <input type="text" class="form-control" name="razon_social" value="<?= e($_POST['razon_social'] ?? '') ?>" placeholder="Ej. Bella Italia SAS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">NIT o Cédula de Identificación *</label>
                            <input type="text" class="form-control" name="nit" value="<?= e($_POST['nit'] ?? '') ?>" placeholder="Ej. 901.234.567-8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tipo de Negocio *</label>
                            <select class="form-select" name="tipo_negocio" required>
                                <option value="Restaurante" selected>Restaurante / Comidas</option>
                                <option value="Pizzería">Pizzería</option>
                                <option value="Comidas Rápidas">Comidas Rápidas / Hamburguesas</option>
                                <option value="Panadería y Repostería">Panadería y Repostería</option>
                                <option value="Farmacia y Droguería">Farmacia y Droguería</option>
                                <option value="Minimarket y Tienda">Minimarket y Tienda</option>
                                <option value="Licores">Licores y Bebidas</option>
                                <option value="Empresa / Envíos">Empresa / Envíos Generales</option>
                                <option value="Otro">Otro Comercio</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Horario Habitual de Atención</label>
                            <input type="text" class="form-control" name="horarios" value="<?= e($_POST['horarios'] ?? '') ?>" placeholder="Ej. Lun-Dom: 11:00 AM - 10:00 PM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Logo del Comercio (Imagen PNG/JPG)</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-geo-alt me-2 text-danger"></i>2. Ubicación del Local
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Dirección Completa de Recogida *</label>
                            <input type="text" class="form-control" name="direccion" value="<?= e($_POST['direccion'] ?? '') ?>" placeholder="Ej. Calle 7 # 11-30" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ciudad *</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= e($_POST['ciudad'] ?? 'El Cerrito') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Barrio / Sector</label>
                            <input type="text" class="form-control" name="barrio" value="<?= e($_POST['barrio'] ?? '') ?>" placeholder="Ej. Centro">
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-badge me-2 text-danger"></i>3. Propietario y Acceso al Panel
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre del Propietario o Responsable *</label>
                            <input type="text" class="form-control" name="nombre_responsable" value="<?= e($_POST['nombre_responsable'] ?? '') ?>" placeholder="Ej. Roberto Gómez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono / WhatsApp de Contacto *</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= e($_POST['telefono'] ?? '') ?>" placeholder="Ej. 3151234567" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Correo Electrónico (Será tu usuario para iniciar sesión) *</label>
                            <input type="email" class="form-control" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="contacto@minegocio.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" class="form-control" name="password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" name="password_confirm" placeholder="Repite la contraseña" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Documento RUT o Cámara de Comercio (Opcional en registro inicial)</label>
                            <input type="file" class="form-control" name="documento_rut" accept=".pdf,image/*">
                            <small class="text-muted">Formatos permitidos: PDF o Imágenes JPG/PNG (máx 5MB).</small>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="bi bi-shield-check text-success me-2"></i>
                        Tu cuenta pasará a estado <strong>Pendiente</strong> y será verificada por el equipo administrativo de CerriFlash para garantizar la seguridad de la red.
                    </div>

                    <button type="submit" class="btn btn-primary-cf w-100 py-3 fw-bold fs-5">
                        <i class="bi bi-send-check me-2"></i> Enviar Registro de Negocio a CerriFlash
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">¿Tu negocio ya está registrado?</small>
                    <a href="<?= BASE_URL ?>/login.php" class="small fw-bold text-decoration-none ms-1">Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

