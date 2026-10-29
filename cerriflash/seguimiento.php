<?php
$pageTitle = 'Seguimiento de Pedido con Mapa - CerriFlash';
require_once __DIR__ . '/config/config.php';

$codigo = trim($_GET['codigo'] ?? '');
$pedido = null;
$historial = [];

if (!empty($codigo)) {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT d.*, 
               r.nombre_comercial AS restaurante_nombre,
               r.telefono_contacto AS restaurante_telefono,
               r.direccion AS restaurante_direccion,
               dom.tipo_vehiculo AS domi_vehiculo,
               dom.placa_vehiculo AS domi_placa,
               dom.foto_perfil AS domi_foto,
               dom.latitud_actual AS domi_lat,
               dom.longitud_actual AS domi_lng,
               u_dom.nombre AS domi_nombre,
               u_dom.telefono AS domi_telefono
        FROM domicilios d
        INNER JOIN restaurantes r ON d.restaurante_id = r.id
        LEFT JOIN domiciliarios dom ON d.domiciliario_id = dom.id
        LEFT JOIN usuarios u_dom ON dom.usuario_id = u_dom.id
        WHERE d.codigo_seguimiento = ?
    ");
    $stmt->execute([$codigo]);
    $pedido = $stmt->fetch();

    if ($pedido) {
        $stmtH = $db->prepare("SELECT * FROM historial_estados WHERE domicilio_id = ? ORDER BY created_at ASC");
        $stmtH->execute([$pedido['id']]);
        $historial = $stmtH->fetchAll();
    }
}

// Calcular en qué índice de etapa se encuentra
$estadoActual = $pedido['estado'] ?? '';
$ordenEstados = ['creado', 'publicado', 'buscando_domiciliario', 'aceptado', 'en_camino_recogida', 'llego_recogida', 'pedido_recogido', 'en_camino_entrega', 'entregado', 'finalizado'];
$indiceActual = array_search($estadoActual, $ordenEstados);
if ($indiceActual === false) $indiceActual = -1;

// Coordenadas con fallback
$latRecogida = !empty($pedido['lat_recogida']) ? (float)$pedido['lat_recogida'] : 3.68412;
$lngRecogida = !empty($pedido['lng_recogida']) ? (float)$pedido['lng_recogida'] : -76.31345;
$latEntrega  = !empty($pedido['lat_entrega']) ? (float)$pedido['lat_entrega'] : 3.68950;
$lngEntrega  = !empty($pedido['lng_entrega']) ? (float)$pedido['lng_entrega'] : -76.31900;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <!-- Barra de Búsqueda de Código -->
            <div class="card-cf bg-white p-3 mb-4">
                <form method="GET" action="<?= BASE_URL ?>/seguimiento.php" class="row g-2 align-items-center">
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="codigo" value="<?= e($codigo) ?>" placeholder="Ingresa tu código de seguimiento (ej. CF-123456)" required>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary-cf w-100">
                            <i class="bi bi-crosshair me-1"></i> Rastrear
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!$pedido): ?>
                <?php if (!empty($codigo)): ?>
                    <div class="alert alert-danger text-center p-4 rounded-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-2 d-block"></i>
                        <h5 class="fw-bold">Código no encontrado</h5>
                        <p class="mb-0 small">No se encontró ningún despacho con el código <strong><?= e($codigo) ?></strong>. Por favor verifica los datos.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-radar display-3 text-secondary opacity-50 mb-3 d-block"></i>
                        <h4>Seguimiento en Vivo CerriFlash</h4>
                        <p class="small text-muted">Ingresa el código proporcionado por tu comercio para consultar el trayecto en el mapa.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Mapa Interactivo en Vivo -->
                <div class="card-cf bg-white p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold"><i class="bi bi-pin-map text-danger me-2"></i>Ruta en Vivo CerriFlash</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white"><i class="bi bi-geo-alt me-1"></i><?= e($pedido['distancia_km']) ?> km</span>
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>~ <?= e($pedido['tiempo_estimado_min']) ?> min</span>
                        </div>
                    </div>
                    <div id="mapaSeguimiento" style="height: 360px; width: 100%; border-radius: 8px; z-index: 1;"></div>
                </div>

                <!-- Tarjeta Principal de Seguimiento -->
                <div class="card-cf bg-white p-4 p-md-5 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill small fw-bold text-uppercase mb-1">
                                Seguimiento en Tiempo Real
                            </span>
                            <h3 class="fw-bold mb-0 brand-text">Pedido #<?= e($pedido['codigo_seguimiento']) ?></h3>
                            <small class="text-muted">Fecha de Creación: <?= format_datetime($pedido['fecha_creacion']) ?></small>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <?= get_status_badge($pedido['estado']) ?>
                        </div>
                    </div>

                    <!-- Timeline de 5 Pasos Clave -->
                    <h5 class="fw-bold mb-4 text-secondary">
                        <i class="bi bi-geo-fill text-danger me-2"></i>Estado del Despacho
                    </h5>

                    <div class="cf-timeline mb-4">
                        <!-- Paso 1: Publicado -->
                        <?php 
                        $completado1 = $indiceActual >= 1;
                        $activo1 = $indiceActual === 1 || $indiceActual === 2;
                        ?>
                        <div class="cf-timeline-item <?= $completado1 ? 'completed' : ($activo1 ? 'active' : '') ?>">
                            <div class="cf-timeline-point">
                                <i class="bi <?= $completado1 ? 'bi-check-lg' : 'bi-megaphone' ?> small"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Domicilio publicado</h6>
                            <small class="text-muted">El restaurante ha solicitado el servicio a la red CerriFlash.</small>
                        </div>

                        <!-- Paso 2: Domiciliario Asignado -->
                        <?php 
                        $completado2 = $indiceActual >= 3;
                        $activo2 = in_array($pedido['estado'], ['aceptado', 'en_camino_recogida', 'llego_recogida']);
                        ?>
                        <div class="cf-timeline-item <?= $completado2 ? 'completed' : ($activo2 ? 'active' : '') ?>">
                            <div class="cf-timeline-point">
                                <i class="bi <?= $completado2 ? 'bi-check-lg' : 'bi-person' ?> small"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Domiciliario asignado</h6>
                            <small class="text-muted">
                                <?= !empty($pedido['domi_nombre']) ? 'Asignado a: ' . e($pedido['domi_nombre']) : 'Buscando repartidor disponible...' ?>
                            </small>
                        </div>

                        <!-- Paso 3: Pedido Recogido -->
                        <?php 
                        $completado3 = $indiceActual >= 6;
                        $activo3 = $pedido['estado'] === 'pedido_recogido';
                        ?>
                        <div class="cf-timeline-item <?= $completado3 ? 'completed' : ($activo3 ? 'active' : '') ?>">
                            <div class="cf-timeline-point">
                                <i class="bi <?= $completado3 ? 'bi-check-lg' : 'bi-box-seam' ?> small"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Pedido recogido</h6>
                            <small class="text-muted">El domiciliario ya tiene el paquete en sus manos.</small>
                        </div>

                        <!-- Paso 4: En Camino al Cliente -->
                        <?php 
                        $completado4 = $indiceActual >= 8;
                        $activo4 = $pedido['estado'] === 'en_camino_entrega';
                        ?>
                        <div class="cf-timeline-item <?= $completado4 ? 'completed' : ($activo4 ? 'active' : '') ?>">
                            <div class="cf-timeline-point">
                                <i class="bi <?= $completado4 ? 'bi-check-lg' : 'bi-scooter' ?> small"></i>
                            </div>
                            <h6 class="fw-bold mb-0">En camino al destino</h6>
                            <small class="text-muted">El repartidor se desplaza hacia la dirección de entrega.</small>
                        </div>

                        <!-- Paso 5: Entregado -->
                        <?php 
                        $completado5 = in_array($pedido['estado'], ['entregado', 'finalizado']);
                        ?>
                        <div class="cf-timeline-item <?= $completado5 ? 'completed' : '' ?>">
                            <div class="cf-timeline-point">
                                <i class="bi <?= $completado5 ? 'bi-check2-all' : 'bi-house-check' ?> small"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Entregado</h6>
                            <small class="text-muted">Despacho completado con éxito.</small>
                        </div>
                    </div>

                    <!-- Datos del Comercio y Repartidor -->
                    <div class="row g-3 pt-3 border-top">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-danger text-white small mb-2"><i class="bi bi-shop me-1"></i>Comercio</span>
                                <h6 class="fw-bold mb-1"><?= e($pedido['restaurante_nombre']) ?></h6>
                                <p class="small text-muted mb-1"><i class="bi bi-geo-alt text-danger me-1"></i><?= e($pedido['restaurante_direccion']) ?></p>
                                <a href="tel:<?= e($pedido['restaurante_telefono']) ?>" class="small text-decoration-none fw-semibold">
                                    <i class="bi bi-telephone me-1"></i><?= e($pedido['restaurante_telefono']) ?>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-success text-white small mb-2"><i class="bi bi-scooter me-1"></i>Repartidor</span>
                                <?php if (!empty($pedido['domi_nombre'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <?php if (!empty($pedido['domi_foto'])): ?>
                                            <img src="<?= UPLOADS_URL ?>/fotos/<?= e($pedido['domi_foto']) ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-white d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;"><i class="bi bi-person"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="fw-bold mb-0"><?= e($pedido['domi_nombre']) ?></h6>
                                            <small class="text-muted text-capitalize"><?= e($pedido['domi_vehiculo']) ?> <?= !empty($pedido['domi_placa']) ? '('.e($pedido['domi_placa']).')' : '' ?></small>
                                        </div>
                                    </div>
                                    <a href="tel:<?= e($pedido['domi_telefono']) ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-telephone-fill me-1"></i> Llamar al Repartidor
                                    </a>
                                <?php else: ?>
                                    <div class="text-muted small py-2">
                                        <i class="bi bi-hourglass-split me-1"></i> Asignando domiciliario disponible...
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Paquete -->
                    <div class="mt-4 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small text-dark">Detalle del Paquete:</strong>
                            <span class="badge bg-dark">Total Envío: <?= format_currency($pedido['costo_total_envio']) ?></span>
                        </div>
                        <p class="small text-muted mb-0"><?= e($pedido['descripcion_paquete']) ?></p>
                        <div class="small text-muted mt-1"><i class="bi bi-pin-map text-success me-1"></i>Entrega en: <strong><?= e($pedido['direccion_entrega']) ?></strong></div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const latR = <?= $latRecogida ?>;
                    const lngR = <?= $lngRecogida ?>;
                    const latE = <?= $latEntrega ?>;
                    const lngE = <?= $lngEntrega ?>;
                    const estado = "<?= e($pedido['estado']) ?>";

                    const map = L.map('mapaSeguimiento').setView([(latR + latE)/2, (lngR + lngE)/2], 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

                    const iconRest = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41]
                    });

                    const iconClie = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41]
                    });

                    const iconMoto = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41]
                    });

                    L.marker([latR, lngR], { icon: iconRest }).addTo(map).bindPopup("<strong>Restaurante: <?= e($pedido['restaurante_nombre']) ?></strong>");
                    L.marker([latE, lngE], { icon: iconClie }).addTo(map).bindPopup("<strong>Cliente: <?= e($pedido['nombre_cliente']) ?></strong>");

                    const polyline = L.polyline([[latR, lngR], [latE, lngE]], { color: '#ff5722', weight: 4 }).addTo(map);
                    map.fitBounds(polyline.getBounds(), { padding: [40, 40] });

                    // Si hay repartidor asignado, simular posición a lo largo de la ruta según el estado
                    <?php if (!empty($pedido['domiciliario_id'])): ?>
                        let progreso = 0.1;
                        if (estado === 'en_camino_recogida') progreso = 0.2;
                        else if (estado === 'llego_recogida' || estado === 'pedido_recogido') progreso = 0.05;
                        else if (estado === 'en_camino_entrega') progreso = 0.6;
                        else if (estado === 'entregado' || estado === 'finalizado') progreso = 1.0;

                        const curLat = latR + (latE - latR) * progreso;
                        const curLng = lngR + (lngE - lngR) * progreso;

                        const domiMarker = L.marker([curLat, curLng], { icon: iconMoto }).addTo(map)
                            .bindPopup("<strong>🛵 Repartidor: <?= e($pedido['domi_nombre'] ?? 'CerriFlash') ?></strong>")
                            .openPopup();
                    <?php endif; ?>
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
