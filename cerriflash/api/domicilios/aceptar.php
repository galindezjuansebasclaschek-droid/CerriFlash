<?php
/**
 * CerriFlash REST API - Aceptación Atómica de Domicilio
 * POST /api/domicilios/aceptar.php
 * Header: Authorization: Bearer <token>
 * Body: { "domicilio_id": 1 }
 */
require_once __DIR__ . '/../middleware.php';

$user = api_authenticate('domiciliario');
$db = Database::getConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$domicilioId = (int)($input['domicilio_id'] ?? 0);

if (!$domicilioId) {
    json_response(['success' => false, 'error' => 'domicilio_id es requerido.'], 400);
}

// Cargar ID de repartidor
$stmtD = $db->prepare("SELECT id FROM domiciliarios WHERE usuario_id = ?");
$stmtD->execute([$user['id']]);
$domiId = (int)$stmtD->fetchColumn();

try {
    $db->beginTransaction();

    $stmtLock = $db->prepare("
        UPDATE domicilios 
        SET domiciliario_id = ?, estado = 'aceptado', fecha_asignacion = NOW()
        WHERE id = ? AND domiciliario_id IS NULL AND estado IN ('publicado', 'buscando_domiciliario')
    ");
    $stmtLock->execute([$domiId, $domicilioId]);

    if ($stmtLock->rowCount() === 1) {
        $stmtH = $db->prepare("INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id) VALUES (?, 'aceptado', 'Aceptado por API Móvil', ?)");
        $stmtH->execute([$domicilioId, $user['id']]);

        $db->commit();
        json_response([
            'success' => true,
            'message' => 'Domicilio asignado y bloqueado exitosamente.'
        ]);
    } else {
        $db->rollBack();
        json_response([
            'success' => false,
            'error'   => 'Este domicilio ya fue tomado por otro repartidor.'
        ], 409);
    }
} catch (Exception $e) {
    $db->rollBack();
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}

