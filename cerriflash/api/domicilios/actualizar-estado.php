<?php
/**
 * CerriFlash REST API - Actualización de Estado y Posición GPS
 * POST /api/domicilios/actualizar-estado.php
 * Header: Authorization: Bearer <token>
 * Body: { "domicilio_id": 1, "nuevo_estado": "en_camino_entrega", "lat": 3.684, "lng": -76.313 }
 */
require_once __DIR__ . '/../middleware.php';

$user = api_authenticate('domiciliario');
$db = Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$domicilioId = (int)($input['domicilio_id'] ?? 0);
$nuevoEstado = $input['nuevo_estado'] ?? '';
$lat         = isset($input['lat']) ? (float)$input['lat'] : null;
$lng         = isset($input['lng']) ? (float)$input['lng'] : null;
$nota        = trim($input['observaciones'] ?? 'Actualizado vía API');

if (!$domicilioId || empty($nuevoEstado)) {
    json_response(['success' => false, 'error' => 'domicilio_id y nuevo_estado son requeridos.'], 400);
}

// Cargar repartidor ID
$stmtD = $db->prepare("SELECT id FROM domiciliarios WHERE usuario_id = ?");
$stmtD->execute([$user['id']]);
$domiId = (int)$stmtD->fetchColumn();

// Verificar pertenencia del domicilio
$stmtDom = $db->prepare("SELECT * FROM domicilios WHERE id = ? AND domiciliario_id = ?");
$stmtDom->execute([$domicilioId, $domiId]);
$dom = $stmtDom->fetch();

if (!$dom) {
    json_response(['success' => false, 'error' => 'Domicilio no encontrado o no asignado a tu usuario.'], 404);
}

$estadosPermitidos = [
    'en_camino_recogida', 'llego_recogida', 'pedido_recogido',
    'en_camino_entrega', 'entregado', 'finalizado', 'incidencia'
];

if (!in_array($nuevoEstado, $estadosPermitidos)) {
    json_response(['success' => false, 'error' => 'Estado no válido.'], 400);
}

$camposExtra = "";
if ($nuevoEstado === 'pedido_recogido') $camposExtra .= ", fecha_recogida = NOW()";
if ($nuevoEstado === 'entregado')       $camposExtra .= ", fecha_entrega = NOW(), estado_pago = 'pagado'";
if ($nuevoEstado === 'finalizado')      $camposExtra .= ", fecha_finalizacion = NOW(), estado_pago = 'pagado'";

// Actualizar pedido
$stmtUp = $db->prepare("UPDATE domicilios SET estado = ? {$camposExtra} WHERE id = ?");
$stmtUp->execute([$nuevoEstado, $domicilioId]);

// Actualizar ubicación GPS del repartidor si se envió
if ($lat !== null && $lng !== null) {
    $stmtGPS = $db->prepare("UPDATE domiciliarios SET latitud_actual = ?, longitud_actual = ?, ultima_conexion = NOW() WHERE id = ?");
    $stmtGPS->execute([$lat, $lng, $domiId]);
}

// Registrar en historial
$stmtH = $db->prepare("INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id, latitud, longitud) VALUES (?, ?, ?, ?, ?, ?)");
$stmtH->execute([$domicilioId, $nuevoEstado, $nota, $user['id'], $lat, $lng]);

// Si finalizó, registrar comisión
if ($nuevoEstado === 'finalizado') {
    $db->prepare("
        INSERT INTO comisiones (domicilio_id, monto_total, ganancia_domiciliario, comision_plataforma, porcentaje_aplicado, liquidado, fecha_liquidacion)
        VALUES (?, ?, ?, ?, 20.00, 1, NOW())
        ON DUPLICATE KEY UPDATE liquidado = 1
    ")->execute([$domicilioId, $dom['costo_total_envio'], $dom['ganancia_domiciliario'], $dom['comision_cerriflash']]);
}

json_response([
    'success'      => true,
    'message'      => "Estado actualizado a: {$nuevoEstado}",
    'domicilio_id' => $domicilioId,
    'nuevo_estado' => $nuevoEstado
]);

