<?php
/**
 * CerriFlash - Endpoint JSON de Domicilios Disponibles para Repartidores
 */
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in() || current_user_role() !== 'domiciliario') {
    json_response(['success' => false, 'error' => 'No autorizado'], 403);
}

$user = current_user();
$db = Database::getConnection();

// Verificar disponibilidad
$stmtDomi = $db->prepare("SELECT id, disponible FROM domiciliarios WHERE usuario_id = ?");
$stmtDomi->execute([$user['id']]);
$domi = $stmtDomi->fetch();

if (!$domi || (int)$domi['disponible'] !== 1) {
    json_response([
        'success'    => true,
        'disponible' => false,
        'pedidos'    => [],
        'mensaje'    => 'Debes activar tu disponibilidad para recibir pedidos.'
    ]);
}

// Consultar domicilios publicados esperando repartidor
$stmt = $db->query("
    SELECT d.id, d.codigo_seguimiento, d.numero_pedido_origen,
           d.direccion_recogida, d.direccion_entrega, d.descripcion_paquete,
           d.distancia_km, d.tiempo_estimado_min, d.costo_total_envio,
           d.ganancia_domiciliario, d.metodo_pago, d.fecha_creacion,
           r.nombre_comercial AS restaurante_nombre,
           r.telefono_contacto AS restaurante_telefono,
           r.direccion AS restaurante_direccion
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    WHERE d.domiciliario_id IS NULL 
      AND d.estado IN ('publicado', 'buscando_domiciliario')
    ORDER BY d.fecha_creacion ASC
");
$pedidos = $stmt->fetchAll();

// Formatear datos para las tarjetas
$pedidosFormateados = array_map(function($p) {
    $kmTotal = (float)$p['distancia_km'];
    $kmRecogida = round(max(0.5, $kmTotal * 0.3), 1);
    $kmEntrega = round(max(0.5, $kmTotal * 0.7), 1);

    return [
        'id'                 => $p['id'],
        'codigo'             => $p['codigo_seguimiento'],
        'restaurante'        => $p['restaurante_nombre'],
        'recogida_dir'       => $p['direccion_recogida'],
        'entrega_dir'        => $p['direccion_entrega'],
        'distancia_recogida' => $kmRecogida . ' km',
        'distancia_entrega'  => $kmEntrega . ' km',
        'distancia_total'    => $kmTotal . ' km',
        'tiempo_estimado'    => $p['tiempo_estimado_min'] . ' min',
        'ganancia_formato'   => format_currency($p['ganancia_domiciliario']),
        'ganancia_monto'     => (float)$p['ganancia_domiciliario'],
        'descripcion'        => $p['descripcion_paquete'],
        'metodo_pago'        => ucfirst($p['metodo_pago']),
        'creado_hace'        => date('h:i A', strtotime($p['fecha_creacion']))
    ];
}, $pedidos);

json_response([
    'success'    => true,
    'disponible' => true,
    'total'      => count($pedidosFormateados),
    'pedidos'    => $pedidosFormateados
]);

