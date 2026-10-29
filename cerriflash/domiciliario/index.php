<?php
$pageTitle = 'Panel Repartidor - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('domiciliario');

$user = current_user();
$db = Database::getConnection();

// Toggle de disponibilidad por POST tradicional (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_disponibilidad') {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $stmtCurr = $db->prepare("SELECT disponible FROM domiciliarios WHERE usuario_id = ?");
        $stmtCurr->execute([$user['id']]);
        $currentDisp = (int)$stmtCurr->fetchColumn();
        $newDisp = $currentDisp === 1 ? 0 : 1;

        $stmtUp = $db->prepare("UPDATE domiciliarios SET disponible = ?, ultima_conexion = NOW() WHERE usuario_id = ?");
        $stmtUp->execute([$newDisp, $user['id']]);

        $statusMsg = $newDisp === 1 ? '¡Ahora estás en línea y disponible para recibir pedidos!' : 'Te has desconectado. No recibirás nuevas solicitudes.';
        set_flash('info', $statusMsg);
        redirect('domiciliario/index.php');
    }
}

// Cargar perfil domiciliario
$stmt = $db->prepare("SELECT * FROM domiciliarios WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$domiciliario = $stmt->fetch();
$estaDisponible = (int)($domiciliario['disponible'] ?? 0) === 1;
$domiId = (int)($domiciliario['id'] ?? 0);

// Comprobar si tiene un pedido en curso
$stmtActivo = $db->prepare("
    SELECT id, codigo_seguimiento, estado, direccion_entrega, costo_total_envio, ganancia_domiciliario 
    FROM domicilios 
    WHERE domiciliario_id = ? AND estado NOT IN ('finalizado', 'cancelado', 'no_entregado') 
    LIMIT 1
");
$stmtActivo->execute([$domiId]);
$pedidoEnCurso = $stmtActivo->fetch();

// Ganancias acumuladas
$stmtGanancias = $db->prepare("
    SELECT 
        COUNT(*) as total_entregas,
        COALESCE(SUM(ganancia_domiciliario), 0) as total_ganancias
    FROM domicilios 
    WHERE domiciliario_id = ? AND estado = 'finalizado'
");
$stmtGanancias->execute([$domiId]);
$statsDomi = $stmtGanancias->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Banner de Pedido Activo en Curso si existe -->
    <?php if ($pedidoEnCurso): ?>
        <div class="card-cf bg-warning-subtle border-warning p-4 mb-4 shadow-sm">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-1 text-warning-emphasis"><i class="bi bi-scooter"></i></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-dark fw-bold"><?= e($pedidoEnCurso['codigo_seguimiento']) ?></span>
                            <?= get_status_badge($pedidoEnCurso['estado']) ?>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Tienes una entrega en curso actualmente</h5>
                        <small class="text-muted">Destino: <?= e($pedidoEnCurso['direccion_entrega']) ?> | Ganancia: <?= format_currency($pedidoEnCurso['ganancia_domiciliario']) ?></small>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/domiciliario/pedido-activo.php?id=<?= $pedidoEnCurso['id'] ?>" class="btn btn-primary-cf btn-lg px-4 fw-bold">
                    Continuar Entrega <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Domiciliario -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="fw-bold mb-0 brand-text"><i class="bi bi-person-walking text-success me-2"></i><?= e($user['nombre']) ?></h2>
                <?= get_status_badge($user['estado']) ?>
            </div>
            <p class="text-muted small mb-0">
                <i class="bi bi-bicycle me-1"></i> Vehículo: <strong class="text-capitalize"><?= e($domiciliario['tipo_vehiculo'] ?? 'N/A') ?></strong>
                <?= !empty($domiciliario['placa_vehiculo']) ? ' (Placa: '.e($domiciliario['placa_vehiculo']).')' : '' ?>
                | Tel: <?= e($user['telefono']) ?>
            </p>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <a href="<?= BASE_URL ?>/domiciliario/perfil.php" class="btn btn-outline-secondary">
                <i class="bi bi-person-gear me-1"></i> Mi Perfil
            </a>

            <!-- Botón de Disponibilidad Principal AJAX -->
            <?php if ($user['estado'] === 'aprobado'): ?>
                <form id="formDisponibilidad" method="POST" action="<?= BASE_URL ?>/domiciliario/index.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_disponibilidad">
                    <button type="button" id="btnToggleDisponibilidad" class="btn <?= $estaDisponible ? 'btn-success' : 'btn-secondary' ?> btn-lg px-4 shadow-sm status-indicator">
                        <span id="dotDisponibilidad" class="pulse-dot <?= $estaDisponible ? 'online' : 'offline' ?>"></span>
                        <span id="textDisponibilidad"><?= $estaDisponible ? '🟢 DISPONIBLE (Activo)' : '⚪ NO DISPONIBLE (Desconectado)' ?></span>
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg px-4" disabled>
                    <i class="bi bi-clock-history me-2"></i> Verificación Pendiente
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerta si está pendiente -->
    <?php if ($user['estado'] === 'pendiente'): ?>
        <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
            <div class="d-flex">
                <i class="bi bi-shield-exclamation fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Tu cuenta de repartidor está en revisión</h5>
                    <p class="mb-0 small">
                        El equipo de CerriFlash está validando tu documentación para activar tu cuenta.
                        Tan pronto esté aprobada, podrás activar tu estado disponible y comenzar a recibir solicitudes de despacho.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Métricas del Repartidor -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Ganancias Acumuladas</small>
                        <h3 class="fw-bold text-success mb-0"><?= format_currency($statsDomi['total_ganancias'] ?? 0) ?></h3>
                    </div>
                    <div class="fs-1 text-success opacity-50"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Entregas Realizadas</small>
                        <h3 class="fw-bold text-primary mb-0"><?= number_format($statsDomi['total_entregas'] ?? 0) ?></h3>
                    </div>
                    <div class="fs-1 text-primary opacity-50"><i class="bi bi-box2-heart-fill"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-cf p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold d-block">Estado Operativo</small>
                        <h4 class="fw-bold mb-0" id="badgeOperativo">
                            <?= $estaDisponible ? '<span class="text-success">Conectado</span>' : '<span class="text-muted">Desconectado</span>' ?>
                        </h4>
                    </div>
                    <div class="fs-1 opacity-50" id="iconOperativo">
                        <?= $estaDisponible ? '<i class="bi bi-radar text-success"></i>' : '<i class="bi bi-moon-stars text-secondary"></i>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bolsa de solicitudes cercanas en tiempo real -->
    <div class="card-cf bg-white p-4" id="contenedorSolicitudes">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-broadcast text-danger me-2"></i>Radar CerriFlash de Solicitudes
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger" id="badgeTotalPedidos">0 Disponibles</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="consultarPedidosDisponibles();">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <!-- Contenedor dinámico de tarjetas NUEVO DOMICILIO -->
        <div id="listaPedidosRow" class="row g-3">
            <div class="col-12 text-center py-5 text-muted" id="radarVacio">
                <?php if (!$estaDisponible): ?>
                    <i class="bi bi-toggle-off fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                    <h6 class="fw-semibold">Te encuentras desconectado</h6>
                    <p class="small text-muted mb-0">Activa tu disponibilidad en <strong>🟢 DISPONIBLE</strong> para recibir pedidos.</p>
                <?php else: ?>
                    <div class="spinner-grow text-success mb-2" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <h6 class="fw-semibold">Radar buscando nuevos pedidos en El Cerrito...</h6>
                    <p class="small text-muted mb-0">Cuando un comercio publique un envío, aparecerá aquí inmediatamente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let estaEnLinea = <?= $estaDisponible ? 'true' : 'false' ?>;
let ultimosPedidosIds = new Set();

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnToggleDisponibilidad');
    const dot = document.getElementById('dotDisponibilidad');
    const text = document.getElementById('textDisponibilidad');
    const badgeOp = document.getElementById('badgeOperativo');
    const iconOp = document.getElementById('iconOperativo');

    if (btn) {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const res = await fetch('<?= BASE_URL ?>/domiciliario/toggle-status.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success) {
                    estaEnLinea = (data.disponible === 1);
                    if (estaEnLinea) {
                        btn.className = 'btn btn-success btn-lg px-4 shadow-sm status-indicator';
                        dot.className = 'pulse-dot online';
                        text.innerText = '🟢 DISPONIBLE (Activo)';
                        badgeOp.innerHTML = '<span class="text-success">Conectado</span>';
                        iconOp.innerHTML = '<i class="bi bi-radar text-success"></i>';
                        CerriSound.playNotification();
                        consultarPedidosDisponibles();
                    } else {
                        btn.className = 'btn btn-secondary btn-lg px-4 shadow-sm status-indicator';
                        dot.className = 'pulse-dot offline';
                        text.innerText = '⚪ NO DISPONIBLE (Desconectado)';
                        badgeOp.innerHTML = '<span class="text-muted">Desconectado</span>';
                        iconOp.innerHTML = '<i class="bi bi-moon-stars text-secondary"></i>';
                        document.getElementById('badgeTotalPedidos').innerText = '0 Disponibles';
                        document.getElementById('listaPedidosRow').innerHTML = `
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="bi bi-toggle-off fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                <h6 class="fw-semibold">Te encuentras desconectado</h6>
                                <p class="small text-muted mb-0">Activa tu disponibilidad en <strong>🟢 DISPONIBLE</strong> para recibir pedidos.</p>
                            </div>
                        `;
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                btn.disabled = false;
            }
        });
    }

    if (estaEnLinea) {
        consultarPedidosDisponibles();
        // Polling cada 4 segundos
        setInterval(() => {
            if (estaEnLinea) {
                consultarPedidosDisponibles();
            }
        }, 4000);
    }
});

async function consultarPedidosDisponibles() {
    if (!estaEnLinea) return;

    try {
        const res = await fetch('<?= BASE_URL ?>/domiciliario/pedidos-disponibles.php');
        const data = await res.json();

        if (data.success && data.disponible) {
            const badge = document.getElementById('badgeTotalPedidos');
            badge.innerText = `${data.total} Disponibles`;

            const contenedor = document.getElementById('listaPedidosRow');

            if (data.total === 0) {
                contenedor.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted">
                        <div class="spinner-grow text-success mb-2" role="status"><span class="visually-hidden">Buscando...</span></div>
                        <h6 class="fw-semibold">Radar buscando nuevos pedidos en El Cerrito...</h6>
                        <p class="small text-muted mb-0">En cuanto un comercio publique un envío, recibirás la tarjeta de inmediato.</p>
                    </div>
                `;
                ultimosPedidosIds.clear();
            } else {
                // Hay pedidos disponibles: Renderizar la tarjeta exacta solicitada
                let html = '';
                let hayNuevo = false;

                data.pedidos.forEach(p => {
                    if (!ultimosPedidosIds.has(p.id)) {
                        hayNuevo = true;
                    }

                    html += `
                        <div class="col-md-6 col-lg-4" id="pedido-card-${p.id}">
                            <div class="card-cf p-4 bg-white border-2 border-danger shadow-sm h-100 position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger text-white fw-bold px-3 py-1 text-uppercase">
                                        <i class="bi bi-lightning-fill me-1"></i>NUEVO DOMICILIO
                                    </span>
                                    <span class="badge bg-dark fw-bold">${p.codigo}</span>
                                </div>

                                <h5 class="fw-bold mb-3 text-secondary">Restaurante: <span class="text-dark">${p.restaurante}</span></h5>

                                <div class="p-3 bg-light rounded-3 mb-3 border small">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Recogida:</span>
                                        <strong>${p.distancia_recogida}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted"><i class="bi bi-pin-map-fill text-success me-1"></i>Entrega:</span>
                                        <strong>${p.distancia_entrega}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-top pt-2">
                                        <span class="fw-semibold">Distancia total:</span>
                                        <strong class="text-primary">${p.distancia_total}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Tiempo estimado:</span>
                                        <strong>${p.tiempo_estimado}</strong>
                                    </div>
                                </div>

                                <div class="p-3 rounded-3 bg-success-subtle text-success text-center mb-3">
                                    <small class="fw-semibold d-block text-uppercase">Ganancia</small>
                                    <h3 class="fw-black mb-0">${p.ganancia_formato}</h3>
                                </div>

                                <div class="d-flex gap-2 mt-auto">
                                    <form method="POST" action="<?= BASE_URL ?>/domiciliario/aceptar-pedido.php" class="flex-grow-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="domicilio_id" value="${p.id}">
                                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> ACEPTAR
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-outline-secondary py-2 px-3" onclick="descartarTarjeta(${p.id})">
                                        RECHAZAR
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                contenedor.innerHTML = html;

                if (hayNuevo) {
                    CerriSound.playNotification();
                    ultimosPedidosIds = new Set(data.pedidos.map(p => p.id));
                }
            }
        }
    } catch (e) {
        console.error('Error al consultar radar:', e);
    }
}

function descartarTarjeta(id) {
    const el = document.getElementById(`pedido-card-${id}`);
    if (el) {
        el.style.opacity = '0.3';
        el.style.pointerEvents = 'none';
        setTimeout(() => el.remove(), 400);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
