<?php
/**
 * CerriFlash REST API - Consulta de Tracking de Pedido
 * GET /api/domicilios/tracking.php?codigo=CF-XXXXXX
 */
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$codigo = trim($_GET['codigo'] ?? '');
if (empty($codigo)) {
    json_response(['success' => false, 'error' => 'Parámetro codigo es requerido.'], 400);
}

$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT d.id, d.codigo_seguimiento, d.numero_pedido_origen,
           d.direccion_recogida, d.lat_recogida, d.lng_recogida,
           d.direccion_entrega, d.lat_entrega, d.lng_entrega,
           d.nombre_cliente, d.descripcion_paquete, d.costo_total_envio,
           d.estado, d.metodo_pago, d.estado_pago, d.fecha_creacion,
           r.nombre_comercial AS restaurante_nombre,
           r.telefono_contacto AS restaurante_telefono,
           dom.latitud_actual AS domi_lat,
           dom.longitud_actual AS domi_lng,
           dom.tipo_vehiculo AS domi_vehiculo,
           dom.placa_vehiculo AS domi_placa,
           dom.foto_perfil AS domi_foto,
           u_dom.nombre AS domi_nombre,
           u_dom.telefono AS domi_telefono
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
    LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
    WHERE d.codigo_seguimiento = ?
");
$stmt->execute([$codigo]);
$dom = $stmt->fetch();

if (!$dom) {
    json_response(['success' => false, 'error' => 'Pedido no encontrado.'], 404);
}

// Historial de eventos
$stmtH = $db->prepare("SELECT estado, observaciones, latitud, longitud, created_at FROM historial_estados WHERE domicilio_id = ? ORDER BY created_at ASC");
$stmtH->execute([$dom['id']]);
$historial = $stmtH->fetchAll();

json_response([
    'success'   => true,
    'domicilio' => $dom,
    'historial' => $historial
]);

