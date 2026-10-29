<?php
/**
 * CerriFlash - Funciones Auxiliares y Helpers Globales
 */

/**
 * Escapa cadenas para prevenir ataques XSS
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirecciona a una ruta interna
 */
function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: $path");
    } else {
        $url = BASE_URL . '/' . ltrim($path, '/');
        header("Location: $url");
    }
    exit;
}

/**
 * Establece un mensaje flash en sesión
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Renderiza y limpia los mensajes flash de sesión
 */
function display_flash(): string {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $output = '';
    foreach ($_SESSION['flash_messages'] as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $output .= "
        <div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm\" role=\"alert\">
            <div class=\"d-flex align-items-center\">
                <i class=\"bi bi-info-circle-fill me-2 fs-5\"></i>
                <div>{$msg}</div>
            </div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Cerrar\"></button>
        </div>";
    }

    unset($_SESSION['flash_messages']);
    return $output;
}

/**
 * Formatea valores numéricos a formato de moneda COP ($8.000)
 */
function format_currency(float|int|string $amount): string {
    return '$' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Formatea fechas a formato legible
 */
function format_datetime(?string $dateStr): string {
    if (!$dateStr) return 'N/A';
    $time = strtotime($dateStr);
    return date('d/m/Y h:i A', $time);
}

/**
 * Genera un badge Bootstrap con estilos para estados de domicilios y cuentas
 */
function get_status_badge(string $status): string {
    $map = [
        // Estados de cuenta
        'pendiente'              => ['warning', 'bi-hourglass-split', 'Pendiente'],
        'aprobado'               => ['success', 'bi-check-circle', 'Aprobado'],
        'rechazado'              => ['danger', 'bi-x-circle', 'Rechazado'],
        'suspendido'             => ['secondary', 'bi-slash-circle', 'Suspendido'],

        // Estados del Domicilio
        'creado'                 => ['secondary', 'bi-file-earmark-plus', 'Creado'],
        'publicado'              => ['info', 'bi-megaphone', 'Publicado'],
        'buscando_domiciliario'  => ['warning text-dark', 'bi-radar', 'Buscando Repartidor'],
        'aceptado'               => ['primary', 'bi-hand-thumbs-up', 'Aceptado'],
        'en_camino_recogida'     => ['primary', 'bi-bicycle', 'En camino a recogida'],
        'llego_recogida'         => ['info', 'bi-shop', 'Llegó al restaurante'],
        'pedido_recogido'        => ['dark', 'bi-box-seam', 'Pedido Recogido'],
        'en_camino_entrega'      => ['primary', 'bi-scooter', 'En camino al cliente'],
        'entregado'              => ['success', 'bi-check2-all', 'Entregado'],
        'finalizado'             => ['success', 'bi-patch-check-fill', 'Finalizado'],
        'cancelado'              => ['danger', 'bi-x-octagon-fill', 'Cancelado'],
        'no_entregado'           => ['danger', 'bi-exclamation-octagon', 'No Entregado'],
        'incidencia'             => ['danger', 'bi-flag-fill', 'Incidencia']
    ];

    $config = $map[$status] ?? ['secondary', 'bi-question-circle', ucfirst($status)];
    return "<span class=\"badge bg-{$config[0]} py-2 px-3 fw-semibold\"><i class=\"bi {$config[1]} me-1\"></i>{$config[2]}</span>";
}

/**
 * Genera código único de seguimiento amigable para pedidos CerriFlash (ej. CF-82914)
 */
function generate_tracking_code(): string {
    return 'CF-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Manejador seguro para carga de archivos
 */
function upload_file(array $file, string $targetFolder, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'], int $maxMb = 5): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > ($maxMb * 1024 * 1024)) {
        return null; // Archivo demasiado grande
    }

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedExtensions)) {
        return null; // Extensión no permitida
    }

    if (!is_dir($targetFolder)) {
        mkdir($targetFolder, 0755, true);
    }

    $uniqueName = 'cf_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $fileExt;
    $destination = rtrim($targetFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $uniqueName;
    }

    return null;
}

/**
 * Retorna respuesta JSON con cabecera y código HTTP
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Calcula tarifas de domicilio CerriFlash según la tarifa activa en base de datos
 */
function calculate_delivery_fare(float $distanciaKm, float $recargos = 0.00): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM tarifas WHERE activa = 1 ORDER BY id DESC LIMIT 1");
    $tarifa = $stmt->fetch();

    if (!$tarifa) {
        $tarifa = [
            'tarifa_base' => 3500.00,
            'precio_por_km' => 1200.00,
            'tarifa_minima' => 5000.00,
            'comision_plataforma_porcentaje' => 20.00
        ];
    }

    $base = (float)$tarifa['tarifa_base'];
    $precioKm = (float)$tarifa['precio_por_km'];
    $minima = (float)$tarifa['tarifa_minima'];
    $comisionPct = (float)$tarifa['comision_plataforma_porcentaje'];

    $distanciaKm = max(0.5, $distanciaKm);
    $tiempoMinutos = max(10, (int)round($distanciaKm * 4 + 8)); // Estimación: ~4 min por km + 8 min recogida/entrega

    // Fórmula: base + (km * precio_km) + recargos
    $calculado = $base + ($distanciaKm * $precioKm) + $recargos;
    $totalEnvio = max($minima, round($calculado / 100) * 100); // Redondeado a centenas

    $comision = round($totalEnvio * ($comisionPct / 100));
    $ganancia = $totalEnvio - $comision;

    return [
        'distancia_km'        => $distanciaKm,
        'tiempo_estimado_min' => $tiempoMinutos,
        'tarifa_base'         => $base,
        'precio_km'           => $precioKm,
        'recargos'            => $recargos,
        'costo_total_envio'   => $totalEnvio,
        'comision_cerriflash' => $comision,
        'ganancia_domiciliario' => $ganancia,
        'porcentaje_comision' => $comisionPct
    ];
}


