<?php
/**
 * CerriFlash - Módulo de Autenticación y Control de Roles
 */

/**
 * Retorna true si hay un usuario autenticado en la sesión
 */
function is_logged_in(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Retorna los datos del usuario logueado o null
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Retorna el rol del usuario autenticado
 */
function current_user_role(): ?string {
    return $_SESSION['user']['rol_nombre'] ?? null;
}

/**
 * Exige inicio de sesión activo; si no, redirige a login
 */
function require_auth(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Debes iniciar sesión para acceder a este panel.');
        redirect('login.php');
    }
}

/**
 * Exige un rol específico (o varios roles permitidos)
 */
function require_role(string|array $allowedRoles): void {
    require_auth();

    $allowedRoles = (array) $allowedRoles;
    $userRole = current_user_role();

    if (!in_array($userRole, $allowedRoles)) {
        set_flash('danger', 'Acceso denegado. No tienes permisos para acceder a esta sección.');
        redirect_to_dashboard();
    }
}

/**
 * Redirige al panel correspondiente según el rol del usuario
 */
function redirect_to_dashboard(): void {
    $role = current_user_role();
    switch ($role) {
        case 'admin':
            redirect('admin/index.php');
            break;
        case 'restaurante':
            redirect('restaurante/index.php');
            break;
        case 'domiciliario':
            redirect('domiciliario/index.php');
            break;
        case 'cliente':
            redirect('cliente/index.php');
            break;
        default:
            redirect('index.php');
            break;
    }
}

/**
 * Registra la sesión del usuario con sus datos completos y relaciones de rol
 */
function login_user(array $userRecord): void {
    session_regenerate_id(true);

    $db = Database::getConnection();

    // Obtener detalles adicionales según el rol
    $profileData = [];
    if ($userRecord['rol_nombre'] === 'restaurante') {
        $stmt = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
        $stmt->execute([$userRecord['id']]);
        $profileData = $stmt->fetch() ?: [];
    } elseif ($userRecord['rol_nombre'] === 'domiciliario') {
        $stmt = $db->prepare("SELECT * FROM domiciliarios WHERE usuario_id = ?");
        $stmt->execute([$userRecord['id']]);
        $profileData = $stmt->fetch() ?: [];
    } elseif ($userRecord['rol_nombre'] === 'cliente') {
        $stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
        $stmt->execute([$userRecord['id']]);
        $profileData = $stmt->fetch() ?: [];
    }

    $_SESSION['user'] = [
        'id'          => $userRecord['id'],
        'email'       => $userRecord['email'],
        'nombre'      => $userRecord['nombre'],
        'telefono'    => $userRecord['telefono'],
        'rol_id'      => $userRecord['rol_id'],
        'rol_nombre'  => $userRecord['rol_nombre'],
        'estado'      => $userRecord['estado'],
        'perfil'      => $profileData
    ];
}

/**
 * Cierra la sesión del usuario
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

