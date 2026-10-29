<?php
/**
 * CerriFlash REST API - Middleware de Autenticación por Bearer Token
 */
require_once __DIR__ . '/../config/config.php';

function api_authenticate(?string $requiredRole = null): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    $token = null;
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    } elseif (isset($_REQUEST['api_token'])) {
        $token = $_REQUEST['api_token'];
    }

    if (empty($token)) {
        json_response([
            'success' => false,
            'error'   => 'Token de autorización ausente. Enviar cabecera Authorization: Bearer <token>'
        ], 401);
    }

    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT u.*, r.nombre AS rol_nombre 
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.api_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['success' => false, 'error' => 'Token inválido o expirado.'], 401);
    }

    if ($user['estado'] !== 'aprobado') {
        json_response(['success' => false, 'error' => 'Tu cuenta no está aprobada para operaciones de API.'], 403);
    }

    if ($requiredRole && $user['rol_nombre'] !== $requiredRole) {
        json_response(['success' => false, 'error' => "Permiso denegado. Se requiere rol: {$requiredRole}."], 403);
    }

    return $user;
}

