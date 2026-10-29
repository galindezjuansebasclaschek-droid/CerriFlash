<?php
/**
 * CerriFlash - Protección contra ataques CSRF (Cross-Site Request Forgery)
 */

if (!function_exists('generate_csrf_token')) {
    /**
     * Genera o retorna el token CSRF de la sesión actual
     */
    function generate_csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Genera un input hidden HTML con el token CSRF
     */
    function csrf_field(): string {
        $token = generate_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('validate_csrf_token')) {
    /**
     * Valida si el token enviado en la petición coincide con el de la sesión
     */
    function validate_csrf_token(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

