<?php
$pageTitle = 'Calificar Servicio - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('cliente');

$user = current_user();
$db = Database::getConnection();

$codigo = trim($_GET['codigo'] ?? '');
if (empty($codigo)) {
    set_flash('danger', 'Pedido no especificado.');
    redirect('cliente/index.php');
}

// Cargar cliente ID
$stmtC = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmtC->execute([$user['id']]);
$clienteId = (int)$stmtC->fetchColumn();

// Cargar pedido
$stmtP = $db->prepare("
    SELECT d.*, 
           r.nombre_comercial AS restaurante_nombre,
           dom.id AS domi_id,
           u_dom.nombre AS domi_nombre,
           dom.foto_perfil AS domi_foto
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
    LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
    WHERE d.codigo_seguimiento = ?
");
$stmtP->execute([$codigo]);
$pedido = $stmtP->fetch();

if (!$pedido || !$pedido['domiciliario_id']) {
    set_flash('danger', 'Pedido no válido para calificación.');
    redirect('cliente/index.php');
}

// Comprobar si ya fue calificado
$stmtCheck = $db->prepare("SELECT * FROM calificaciones WHERE domicilio_id = ?");
$stmtCheck->execute([$pedido['id']]);
$calificacionExistente = $stmtCheck->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$calificacionExistente) {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $puntuacion = (int)($_POST['puntuacion'] ?? 5);
        $puntuacion = max(1, min(5, $puntuacion));
        $comentario = trim($_POST['comentario'] ?? '');

        $stmtIns = $db->prepare("
            INSERT INTO calificaciones (domicilio_id, cliente_id, domiciliario_id, puntuacion, comentario)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtIns->execute([$pedido['id'], $clienteId, $pedido['domiciliario_id'], $puntuacion, $comentario]);

        set_flash('success', '¡Muchas gracias por calificar a tu repartidor CerriFlash!');
        redirect('cliente/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card-cf bg-white p-4 p-md-5 text-center">
                <span class="brand-badge mb-2 d-inline-block"><i class="bi bi-star-fill text-warning"></i></span>
                <h3 class="fw-bold brand-text mb-1">Califica tu Servicio</h3>
                <p class="text-muted small mb-4">Pedido #<?= e($pedido['codigo_seguimiento']) ?> de <?= e($pedido['restaurante_nombre']) ?></p>

                <!-- Repartidor -->
                <div class="p-3 bg-light rounded-4 border mb-4 d-flex align-items-center gap-3 text-start">
                    <?php if (!empty($pedido['domi_foto'])): ?>
                        <img src="<?= UPLOADS_URL ?>/fotos/<?= e($pedido['domi_foto']) ?>" class="rounded-circle" style="width: 55px; height: 55px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center border" style="width: 55px; height: 55px;"><i class="bi bi-person fs-3 text-secondary"></i></div>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted d-block">Tu Repartidor CerriFlash</small>
                        <h5 class="fw-bold mb-0"><?= e($pedido['domi_nombre']) ?></h5>
                    </div>
                </div>

                <?php if ($calificacionExistente): ?>
                    <div class="alert alert-success rounded-4 p-4">
                        <i class="bi bi-check-circle-fill fs-1 text-success mb-2 d-block"></i>
                        <h5 class="fw-bold">¡Ya has calificado este pedido!</h5>
                        <p class="mb-2">Tu calificación registrada:</p>
                        <div class="fs-4 text-warning mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= $calificacionExistente['puntuacion'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($calificacionExistente['comentario'])): ?>
                            <p class="small fst-italic text-muted mb-0">"<?= e($calificacionExistente['comentario']) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/cliente/index.php" class="btn btn-outline-secondary">Volver a Mis Pedidos</a>
                <?php else: ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted d-block mb-2">¿Cómo calificarías la rapidez y amabilidad del servicio?</label>
                            <div class="d-flex justify-content-center gap-3 fs-2 text-warning" id="starContainer">
                                <i class="bi bi-star-fill star-item cursor-pointer" data-val="1"></i>
                                <i class="bi bi-star-fill star-item cursor-pointer" data-val="2"></i>
                                <i class="bi bi-star-fill star-item cursor-pointer" data-val="3"></i>
                                <i class="bi bi-star-fill star-item cursor-pointer" data-val="4"></i>
                                <i class="bi bi-star-fill star-item cursor-pointer" data-val="5"></i>
                            </div>
                            <input type="hidden" name="puntuacion" id="inpPuntuacion" value="5">
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label small fw-semibold">Deja un comentario u opinión (Opcional)</label>
                            <textarea class="form-control" name="comentario" rows="3" placeholder="Ej. ¡Llegó muy rápido y el pedido en perfecto estado!"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-cf w-100 py-3 fw-bold fs-5 shadow">
                            <i class="bi bi-send-check me-2"></i> Enviar Calificación
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const stars = document.querySelectorAll('.star-item');
    const inp = document.getElementById('inpPuntuacion');

    if (stars.length > 0) {
        stars.forEach(s => {
            s.style.cursor = 'pointer';
            s.addEventListener('click', () => {
                const val = parseInt(s.getAttribute('data-val'));
                inp.value = val;
                stars.forEach(st => {
                    const stVal = parseInt(st.getAttribute('data-val'));
                    if (stVal <= val) {
                        st.className = 'bi bi-star-fill star-item';
                    } else {
                        st.className = 'bi bi-star star-item';
                    }
                });
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

