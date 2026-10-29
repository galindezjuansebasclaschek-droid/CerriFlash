<?php
/**
 * CerriFlash REST API - Listado de Domicilios Disponibles
 * GET /api/domicilios/disponibles.php
 * Header: Authorization: Bearer <token>
 */
require_once __DIR__ . '/../middleware.php';

$user = api_authenticate('domiciliario');
$db = Database::getConnection();

// Consultar pedidos disponibles
$stmt = $db->query("
    SELECT d.id, d.codigo_seguimiento, d.numero_pedido_origen,
           d.direccion_recogida, d.lat_recogida, d.lng_recogida,
           d.direccion_entrega, d.lat_entrega, d.lng_entrega,
           d.nombre_cliente, d.telefono_cliente, d.descripcion_paquete,
           d.distancia_km, d.tiempo_estimado_min, d.costo_total_envio,
           d.ganancia_domiciliario, d.metodo_pago, d.fecha_creacion,
           r.nombre_comercial AS restaurante_nombre,
           r.telefono_contacto AS restaurante_telefono
    FROM domicilios d
    INNER JOIN restaurantes r ON d.restaurante_id = r.id
    WHERE d.domiciliario_id IS NULL 
      AND d.estado IN ('publicado', 'buscando_domiciliario')
    ORDER BY d.fecha_creacion ASC
");
$pedidos = $stmt->fetchAll();

json_response([
    'success' => true,
    'total'   => count($pedidos),
    'data'    => $pedidos
]);

