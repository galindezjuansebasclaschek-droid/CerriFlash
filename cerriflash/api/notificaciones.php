<?php
/**
 * CerriFlash - API de Notificaciones en Vivo
 */
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
    json_response(['success' => false, 'error' => 'No autorizado'], 401);
}

$user = current_user();
$db = Database::getConnection();

// Marcar como leídas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'marcar_leidas') {
    $stmtM = $db->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ?");
    $stmtM->execute([$user['id']]);
    json_response(['success' => true]);
}

// Consultar no leídas y últimas 8 notificaciones
$stmtCount = $db->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leido = 0");
$stmtCount->execute([$user['id']]);
$noLeidas = (int)$stmtCount->fetchColumn();

$stmtList = $db->prepare("
    SELECT * FROM notificaciones 
    WHERE usuario_id = ? 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmtList->execute([$user['id']]);
$notificaciones = $stmtList->fetchAll();

$items = array_map(function($n) {
    return [
        'id'         => $n['id'],
        'titulo'     => $n['titulo'],
        'mensaje'    => $n['mensaje'],
        'tipo'       => $n['tipo'],
        'url_accion' => $n['url_accion'] ? BASE_URL . '/' . ltrim($n['url_accion'], '/') : null,
        'leido'      => (int)$n['leido'],
        'fecha'      => date('d/m h:i A', strtotime($n['created_at']))
    ];
}, $notificaciones);

json_response([
    'success'        => true,
    'no_leidas'      => $noLeidas,
    'notificaciones' => $items
]);

