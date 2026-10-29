<?php
/**
 * CerriFlash REST API - Creación de Pedido por Comercio
 * POST /api/pedidos/crear.php
 * Header: Authorization: Bearer <token>
 * Body JSON:
 * {
 *   "numero_pedido": "PED-990",
 *   "direccion_entrega": "Calle 10 # 5-20",
 *   "nombre_cliente": "Juan Perez",
 *   "telefono_cliente": "3151234567",
 *   "descripcion": "2 Hamburguesas",
 *   "distancia_km": 2.5,
 *   "valor_pedido": 35000
 * }
 */
require_once __DIR__ . '/../middleware.php';

$user = api_authenticate('restaurante');
$db = Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$dirEntrega      = trim($input['direccion_entrega'] ?? '');
$nombreCliente   = trim($input['nombre_cliente'] ?? '');
$telefonoCliente = trim($input['telefono_cliente'] ?? '');
$descripcion     = trim($input['descripcion'] ?? '');
$distanciaKm     = (float)($input['distancia_km'] ?? 2.5);
$valorPedido     = (float)($input['valor_pedido'] ?? 0);
$numeroPedido    = trim($input['numero_pedido'] ?? '');

if (empty($dirEntrega) || empty($nombreCliente) || empty($telefonoCliente) || empty($descripcion)) {
    json_response(['success' => false, 'error' => 'Faltan campos obligatorios (direccion_entrega, nombre_cliente, telefono_cliente, descripcion).'], 400);
}

// Cargar restaurante
$stmtR = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmtR->execute([$user['id']]);
$restaurante = $stmtR->fetch();

$calculo = calculate_delivery_fare($distanciaKm);
$codigo = generate_tracking_code();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO domicilios (
            codigo_seguimiento, restaurante_id, numero_pedido_origen,
            direccion_recogida, direccion_entrega, nombre_cliente, telefono_cliente,
            descripcion_paquete, valor_declarado_pedido, distancia_km, tiempo_estimado_min,
            tarifa_base, precio_km, costo_total_envio, ganancia_domiciliario, comision_cerriflash,
            metodo_pago, estado, fecha_creacion
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            'contraentrega', 'buscando_domiciliario', NOW()
        )
    ");
    $stmt->execute([
        $codigo, $restaurante['id'], $numeroPedido ?: $codigo,
        $restaurante['direccion'], $dirEntrega, $nombreCliente, $telefonoCliente,
        $descripcion, $valorPedido, $calculo['distancia_km'], $calculo['tiempo_estimado_min'],
        $calculo['tarifa_base'], $calculo['precio_km'], $calculo['costo_total_envio'],
        $calculo['ganancia_domiciliario'], $calculo['comision_cerriflash']
    ]);
    $id = (int)$db->lastInsertId();

    $db->prepare("INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id) VALUES (?, 'buscando_domiciliario', 'Creado vía API REST', ?)")
       ->execute([$id, $user['id']]);

    $db->commit();

    json_response([
        'success'            => true,
        'codigo_seguimiento' => $codigo,
        'domicilio_id'       => $id,
        'costo_envio'        => $calculo['costo_total_envio'],
        'tiempo_estimado'    => $calculo['tiempo_estimado_min'] . ' min'
    ], 201);

} catch (Exception $e) {
    $db->rollBack();
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}

