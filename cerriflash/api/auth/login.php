<?php
/**
 * CerriFlash REST API - Autenticación y Generación de Token
 * POST /api/auth/login.php
 */
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Método no permitido. Utilizar POST.'], 405);
}

// Leer cuerpo JSON o POST estándar
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(['success' => false, 'error' => 'Email y contraseña requeridos.'], 400);
}

$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT u.*, r.nombre AS rol_nombre 
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['success' => false, 'error' => 'Credenciales inválidas.'], 401);
}

if ($user['estado'] === 'suspendido') {
    json_response(['success' => false, 'error' => 'Cuenta suspendida por administración.'], 403);
}

if ($user['estado'] === 'rechazado') {
    json_response(['success' => false, 'error' => 'Solicitud de registro rechazada.'], 403);
}

// Generar o renovar API Token
$token = $user['api_token'];
if (empty($token)) {
    $token = 'cf_tok_' . bin2hex(random_bytes(24));
    $stmtUp = $db->prepare("UPDATE usuarios SET api_token = ? WHERE id = ?");
    $stmtUp->execute([$token, $user['id']]);
}

json_response([
    'success' => true,
    'message' => 'Autenticación exitosa',
    'token'   => $token,
    'usuario' => [
        'id'        => (int)$user['id'],
        'nombre'    => $user['nombre'],
        'email'     => $user['email'],
        'telefono'  => $user['telefono'],
        'rol'       => $user['rol_nombre'],
        'estado'    => $user['estado']
    ]
]);

