<?php
/**
 * CerriFlash - Endpoint AJAX para cambio de disponibilidad de Domiciliario
 */
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in() || current_user_role() !== 'domiciliario') {
    json_response(['success' => false, 'error' => 'No autorizado'], 403);
}

$user = current_user();

if ($user['estado'] !== 'aprobado') {
    json_response(['success' => false, 'error' => 'Tu cuenta aún no ha sido aprobada por administración.'], 403);
}

$db = Database::getConnection();

// Consultar disponibilidad actual
$stmt = $db->prepare("SELECT disponible FROM domiciliarios WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$current = (int)$stmt->fetchColumn();

$newStatus = $current === 1 ? 0 : 1;

// Actualizar
$stmtUp = $db->prepare("UPDATE domiciliarios SET disponible = ?, ultima_conexion = NOW() WHERE usuario_id = ?");
$stmtUp->execute([$newStatus, $user['id']]);

json_response([
    'success'    => true,
    'disponible' => $newStatus,
    'label'      => $newStatus === 1 ? '🟢 DISPONIBLE (Activo)' : '⚪ NO DISPONIBLE (Desconectado)',
    'message'    => $newStatus === 1 ? '¡Estás en línea! Listo para recibir pedidos.' : 'Te has desconectado. No recibirás nuevas solicitudes.'
]);

