<?php
$pageTitle = 'Entrega en Curso - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('domiciliario');

$user = current_user();
$db = Database::getConnection();

// Cargar domiciliario
$stmtD = $db->prepare("SELECT id FROM domiciliarios WHERE usuario_id = ?");
$stmtD->execute([$user['id']]);
$domiId = (int)$stmtD->fetchColumn();

$domicilioId = (int)($_GET['id'] ?? 0);

// Si no pasa ID, buscar si tiene algún pedido activo actualmente
if (!$domicilioId) {
    $stmtAct = $db->prepare("
        SELECT id FROM domicilios 
        WHERE domiciliario_id = ? AND estado NOT IN ('finalizado', 'cancelado', 'no_entregado') 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtAct->execute([$domiId]);
    $domicilioId = (int)$stmtAct->fetchColumn();

    if (!$domicilioId) {
        set_flash('info', 'No tienes ninguna entrega activa en este momento.');
        redirect('domiciliario/index.php');
    }
}

// Cargar detalles completos del domicilio
$stmt = $db->prepare("
    SELECT d.*, 
           r.nombre_comercial AS restaurante_nombre,
           r.direccion AS restaurante_direccion,
           r.telefono_contacto AS restaurante_telefono,
           r.barrio AS restaurante_barrio,
           r.ciudad AS restaurante_ciudad
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    WHERE d.id = ? AND d.domiciliario_id = ?
");
$stmt->execute([$domicilioId, $domiId]);
$pedido = $stmt->fetch();

if (!$pedido) {
    set_flash('danger', 'Domicilio no encontrado o asignado a otro repartidor.');
    redirect('domiciliario/index.php');
}

// Procesar transición de estados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($token)) {
        $nuevoEstado = $_POST['nuevo_estado'];
        $observacion = trim($_POST['observaciones'] ?? '');

        // Validar transiciones permitidas según la máquina de 10 estados
        $transicionesValidas = [
            'aceptado'           => ['en_camino_recogida'],
            'en_camino_recogida' => ['llego_recogida'],
            'llego_recogida'     => ['pedido_recogido'],
            'pedido_recogido'    => ['en_camino_entrega'],
            'en_camino_entrega'  => ['entregado'],
            'entregado'          => ['finalizado']
        ];

        // Transiciones alternativas de incidencia o no entrega
        $permitidos = $transicionesValidas[$pedido['estado']] ?? [];
        $esIncidencia = in_array($nuevoEstado, ['incidencia', 'no_entregado']);

        if (in_array($nuevoEstado, $permitidos) || $esIncidencia) {
            $camposExtra = "";
            $params = [$nuevoEstado];

            if ($nuevoEstado === 'pedido_recogido') {
                $camposExtra .= ", fecha_recogida = NOW()";
            } elseif ($nuevoEstado === 'entregado') {
                $camposExtra .= ", fecha_entrega = NOW(), estado_pago = 'pagado'";
            } elseif ($nuevoEstado === 'finalizado') {
                $camposExtra .= ", fecha_finalizacion = NOW(), estado_pago = 'pagado'";
            }

            $sqlUp = "UPDATE domicilios SET estado = ? {$camposExtra} WHERE id = ?";
            $params[] = $domicilioId;
            $stmtUp = $db->prepare($sqlUp);
            $stmtUp->execute($params);

            // Registrar en historial_estados
            $stmtH = $db->prepare("INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id) VALUES (?, ?, ?, ?)");
            $stmtH->execute([$domicilioId, $nuevoEstado, $observacion ?: "Cambio a estado {$nuevoEstado}", $user['id']]);

            // Si finalizó, liquidar comisión
            if ($nuevoEstado === 'finalizado') {
                $stmtCom = $db->prepare("
                    INSERT INTO comisiones (domicilio_id, monto_total, ganancia_domiciliario, comision_plataforma, porcentaje_aplicado, liquidado, fecha_liquidacion)
                    VALUES (?, ?, ?, ?, 20.00, 1, NOW())
                    ON DUPLICATE KEY UPDATE liquidado = 1
                ");
                $stmtCom->execute([
                    $domicilioId,
                    $pedido['costo_total_envio'],
                    $pedido['ganancia_domiciliario'],
                    $pedido['comision_cerriflash']
                ]);

                set_flash('success', "¡Servicio finalizado con éxito! Has ganado " . format_currency($pedido['ganancia_domiciliario']) . ".");
                redirect('domiciliario/index.php');
            }

            set_flash('success', "Estado actualizado correctamente.");
            redirect("domiciliario/pedido-activo.php?id={$domicilioId}");
        } else {
            set_flash('danger', 'Transición de estado no válida en este momento.');
        }
    }
}

// Configurar botón principal según el estado actual
$botonAccion = null;
switch ($pedido['estado']) {
    case 'aceptado':
        $botonAccion = [
            'nuevo_estado' => 'en_camino_recogida',
            'texto'        => '🛵 Iniciar Trayecto hacia el Restaurante',
            'clase'        => 'btn-primary-cf',
            'icono'        => 'bi-bicycle',
            'descripcion'  => 'Presiona cuando comiences tu desplazamiento hacia el local.'
        ];
        break;
    case 'en_camino_recogida':
        $botonAccion = [
            'nuevo_estado' => 'llego_recogida',
            'texto'        => '🏪 Llegué al Restaurante / Local',
            'clase'        => 'btn-info text-white',
            'icono'        => 'bi-shop',
            'descripcion'  => 'Presiona cuando estés frente al comercio esperando el pedido.'
        ];
        break;
    case 'llego_recogida':
        $botonAccion = [
            'nuevo_estado' => 'pedido_recogido',
            'texto'        => '📦 Confirmar Pedido Recogido',
            'clase'        => 'btn-warning text-dark fw-bold',
            'icono'        => 'bi-box-seam',
            'descripcion'  => 'Verifica que el pedido esté completo y sellado antes de confirmar.'
        ];
        break;
    case 'pedido_recogido':
        $botonAccion = [
            'nuevo_estado' => 'en_camino_entrega',
            'texto'        => '🚀 En Camino hacia el Cliente',
            'clase'        => 'btn-primary-cf',
            'icono'        => 'bi-scooter',
            'descripcion'  => 'Inicia tu recorrido hacia la dirección de entrega del cliente.'
        ];
        break;
    case 'en_camino_entrega':
        $botonAccion = [
            'nuevo_estado' => 'entregado',
            'texto'        => '✅ Confirmar Pedido Entregado',
            'clase'        => 'btn-success fw-bold',
            'icono'        => 'bi-check-circle-fill',
            'descripcion'  => 'Presiona cuando entregues el paquete en manos del cliente.'
        ];
        break;
    case 'entregado':
        $botonAccion = [
            'nuevo_estado' => 'finalizado',
            'texto'        => '🎉 Finalizar Servicio y Cobrar Ganancia',
            'clase'        => 'btn-success fw-bold fs-5',
            'icono'        => 'bi-patch-check-fill',
            'descripcion'  => 'El dinero será acreditado inmediatamente a tu balance de ganancias.'
        ];
        break;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="fw-bold mb-0 brand-text">Entrega #<?= e($pedido['codigo_seguimiento']) ?></h2>
                <?= get_status_badge($pedido['estado']) ?>
            </div>
            <p class="text-muted small mb-0">Gestiona cada etapa del despacho hasta la entrega final.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/domiciliario/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Ir al Radar
            </a>
            <a href="<?= BASE_URL ?>/seguimiento.php?codigo=<?= urlencode($pedido['codigo_seguimiento']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> Ver Rastreo Público
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Acciones de Progresión de Estado -->
        <div class="col-lg-7">
            <!-- Botón Grande de Transición Principal -->
            <?php if ($botonAccion): ?>
                <div class="card-cf bg-white p-4 mb-4 border-2 border-primary shadow-sm text-center">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase mb-2">
                        Siguiente Acción Requerida
                    </span>
                    <h5 class="fw-bold mb-2">Paso actual: <?= strtoupper(str_replace('_', ' ', $pedido['estado'])) ?></h5>
                    <p class="text-muted small mb-4"><?= e($botonAccion['descripcion']) ?></p>

                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="nuevo_estado" value="<?= e($botonAccion['nuevo_estado']) ?>">
                        <button type="submit" class="btn <?= e($botonAccion['clase']) ?> btn-lg w-100 py-3 fw-bold shadow">
                            <i class="bi <?= e($botonAccion['icono']) ?> me-2"></i> <?= e($botonAccion['texto']) ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-success p-4 text-center rounded-4 mb-4">
                    <i class="bi bi-check-circle-fill display-4 text-success mb-2 d-block"></i>
                    <h4 class="fw-bold">¡Domicilio Finalizado!</h4>
                    <p class="mb-0">Este pedido ya ha sido entregado y finalizado correctamente.</p>
                </div>
            <?php endif; ?>

            <!-- Información del Restaurante (Punto de Recogida) -->
            <div class="card-cf bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-danger">
                        <i class="bi bi-shop me-2"></i>PUNTO DE RECOGIDA
                    </h5>
                    <a href="tel:<?= e($pedido['restaurante_telefono']) ?>" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-telephone-fill me-1"></i> Llamar Comercio
                    </a>
                </div>

                <h6 class="fw-bold mb-1"><?= e($pedido['restaurante_nombre']) ?></h6>
                <p class="mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i><strong><?= e($pedido['direccion_recogida']) ?></strong> (<?= e($pedido['restaurante_ciudad']) ?>)</p>

                <div class="d-flex gap-2 mt-2">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($pedido['direccion_recogida'] . ', ' . $pedido['restaurante_ciudad']) ?>" target="_blank" class="btn btn-sm btn-light border">
                        <i class="bi bi-map me-1 text-primary"></i> Abrir en Maps
                    </a>
                    <a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $pedido['restaurante_telefono']) ?>" target="_blank" class="btn btn-sm btn-light border text-success">
                        <i class="bi bi-whatsapp me-1"></i> WhatsApp Negocio
                    </a>
                </div>
            </div>

            <!-- Información del Cliente (Punto de Entrega) -->
            <div class="card-cf bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="bi bi-pin-map-fill me-2"></i>PUNTO DE ENTREGA
                    </h5>
                    <a href="tel:<?= e($pedido['telefono_cliente']) ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-telephone-fill me-1"></i> Llamar Cliente
                    </a>
                </div>

                <h6 class="fw-bold mb-1"><?= e($pedido['nombre_cliente']) ?></h6>
                <p class="mb-2"><i class="bi bi-geo-alt-fill text-success me-1"></i><strong><?= e($pedido['direccion_entrega']) ?></strong></p>

                <?php if (!empty($pedido['observaciones'])): ?>
                    <div class="p-3 bg-light rounded-3 border small mb-3">
                        <strong>Indicaciones del Cliente / Restaurante:</strong><br>
                        <em>"<?= e($pedido['observaciones']) ?>"</em>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($pedido['direccion_entrega'] . ', ' . $pedido['restaurante_ciudad']) ?>" target="_blank" class="btn btn-sm btn-light border">
                        <i class="bi bi-map me-1 text-primary"></i> Abrir en Maps
                    </a>
                    <a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $pedido['telefono_cliente']) ?>" target="_blank" class="btn btn-sm btn-light border text-success">
                        <i class="bi bi-whatsapp me-1"></i> WhatsApp Cliente
                    </a>
                </div>
            </div>

            <!-- Botón de Reporte de Incidencias -->
            <div class="text-end">
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalIncidencia">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Reportar Novedad o Incidencia
                </button>
            </div>
        </div>

        <!-- Columna Derecha: Resumen Económico y Detalles del Paquete -->
        <div class="col-lg-5">
            <div class="card-cf bg-white p-4 mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-cash-stack text-success me-2"></i>Liquidación de Ganancia
                </h5>

                <div class="p-3 bg-success-subtle text-success rounded-3 mb-3 text-center">
                    <small class="fw-semibold text-uppercase d-block">Tu Ganancia Neta Garantizada</small>
                    <h2 class="fw-black mb-0"><?= format_currency($pedido['ganancia_domiciliario']) ?></h2>
                </div>

                <ul class="list-group list-group-flush small mb-3">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Distancia Total:</span>
                        <strong><?= e($pedido['distancia_km']) ?> km</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tiempo Estimado:</span>
                        <strong><?= e($pedido['tiempo_estimado_min']) ?> minutos</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Valor Declarado del Pedido:</span>
                        <strong><?= format_currency($pedido['valor_declarado_pedido']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Cobro de Envío al Cliente:</span>
                        <strong><?= format_currency($pedido['costo_total_envio']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Modalidad de Pago:</span>
                        <strong class="text-uppercase text-primary"><?= e($pedido['metodo_pago']) ?></strong>
                    </li>
                </ul>

                <div class="p-3 bg-light rounded-3 border small">
                    <div class="fw-bold mb-1"><i class="bi bi-box2 me-1"></i>Contenido a Entregar:</div>
                    <p class="mb-0 text-muted"><?= e($pedido['descripcion_paquete']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Incidencia -->
<div class="modal fade" id="modalIncidencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="nuevo_estado" value="incidencia">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>Reportar Incidencia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Indica el problema ocurrido en la ruta. El soporte de CerriFlash y el restaurante serán notificados de inmediato.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Motivo de la Novedad *</label>
                        <select class="form-select" name="observaciones" required>
                            <option value="Dirección errónea o no encontrada">Dirección errónea o no encontrada</option>
                            <option value="Cliente no contesta llamadas ni puerta">Cliente no contesta llamadas ni puerta</option>
                            <option value="Falla mecánica en el vehículo">Falla mecánica en el vehículo</option>
                            <option value="Demora excesiva en preparación del local">Demora excesiva en preparación del local</option>
                            <option value="Pedido derramado o dañado">Pedido derramado o dañado</option>
                            <option value="Otro problema operativo">Otro problema operativo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Novedad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

