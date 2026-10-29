<?php
$pageTitle = 'Crear Domicilio con Mapa - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('restaurante');

$user = current_user();

if ($user['estado'] !== 'aprobado') {
    set_flash('warning', 'Tu comercio está en revisión. No puedes publicar domicilios hasta ser aprobado.');
    redirect('restaurante/index.php');
}

$db = Database::getConnection();

// Cargar datos del restaurante
$stmtR = $db->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmtR->execute([$user['id']]);
$restaurante = $stmtR->fetch();

$errors = [];

// Consulta tarifa activa
$stmtTarifa = $db->query("SELECT * FROM tarifas WHERE activa = 1 ORDER BY id DESC LIMIT 1");
$tarifaActiva = $stmtTarifa->fetch() ?: [
    'tarifa_base' => 3500,
    'precio_por_km' => 1200,
    'tarifa_minima' => 5000,
    'recargo_nocturno' => 1500,
    'recargo_alta_demanda' => 2000,
    'comision_plataforma_porcentaje' => 20
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $numeroPedido     = trim($_POST['numero_pedido_origen'] ?? '');
        $dirRecogida      = trim($_POST['direccion_recogida'] ?? $restaurante['direccion']);
        $dirEntrega       = trim($_POST['direccion_entrega'] ?? '');
        $latRecogida      = !empty($_POST['lat_recogida']) ? (float)$_POST['lat_recogida'] : 3.68412000;
        $lngRecogida      = !empty($_POST['lng_recogida']) ? (float)$_POST['lng_recogida'] : -76.31345000;
        $latEntrega       = !empty($_POST['lat_entrega']) ? (float)$_POST['lat_entrega'] : 3.68950000;
        $lngEntrega       = !empty($_POST['lng_entrega']) ? (float)$_POST['lng_entrega'] : -76.31900000;
        $nombreCliente    = trim($_POST['nombre_cliente'] ?? '');
        $telefonoCliente  = trim($_POST['telefono_cliente'] ?? '');
        $descripcion      = trim($_POST['descripcion_paquete'] ?? '');
        $observaciones    = trim($_POST['observaciones'] ?? '');
        $valorDeclarado   = (float)($_POST['valor_declarado_pedido'] ?? 0);
        $horaRecogida     = !empty($_POST['hora_recogida_estimada']) ? $_POST['hora_recogida_estimada'] : date('H:i:s', strtotime('+15 minutes'));
        $distanciaKm      = max(0.5, (float)($_POST['distancia_km'] ?? 2.5));
        $metodoPago       = $_POST['metodo_pago'] ?? 'contraentrega';
        $recargos         = (float)($_POST['recargos'] ?? 0);

        if (empty($dirRecogida))     $errors[] = 'La dirección de recogida es requerida.';
        if (empty($dirEntrega))      $errors[] = 'La dirección de entrega es requerida.';
        if (empty($nombreCliente))   $errors[] = 'El nombre del cliente es obligatorio.';
        if (empty($telefonoCliente)) $errors[] = 'El teléfono del cliente es obligatorio.';
        if (empty($descripcion))     $errors[] = 'Indica una breve descripción del paquete o pedido.';

        if (empty($errors)) {
            $calculo = calculate_delivery_fare($distanciaKm, $recargos);
            $codigoSeguimiento = generate_tracking_code();

            try {
                $db->beginTransaction();

                // Asociar cliente si existe
                $stmtClie = $db->prepare("SELECT id FROM clientes WHERE usuario_id IN (SELECT id FROM usuarios WHERE telefono = ?) LIMIT 1");
                $stmtClie->execute([$telefonoCliente]);
                $clienteId = $stmtClie->fetchColumn() ?: null;

                $stmtDom = $db->prepare("
                    INSERT INTO domicilios (
                        codigo_seguimiento, restaurante_id, cliente_id, numero_pedido_origen,
                        direccion_recogida, lat_recogida, lng_recogida,
                        direccion_entrega, lat_entrega, lng_entrega,
                        nombre_cliente, telefono_cliente,
                        descripcion_paquete, observaciones, valor_declarado_pedido, hora_recogida_estimada,
                        distancia_km, tiempo_estimado_min, tarifa_base, precio_km, recargos,
                        costo_total_envio, ganancia_domiciliario, comision_cerriflash, metodo_pago,
                        estado_pago, estado, fecha_creacion
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        'pendiente', 'buscando_domiciliario', NOW()
                    )
                ");

                $stmtDom->execute([
                    $codigoSeguimiento,
                    $restaurante['id'],
                    $clienteId,
                    $numeroPedido ?: $codigoSeguimiento,
                    $dirRecogida, $latRecogida, $lngRecogida,
                    $dirEntrega, $latEntrega, $lngEntrega,
                    $nombreCliente,
                    $telefonoCliente,
                    $descripcion,
                    $observaciones,
                    $valorDeclarado,
                    $horaRecogida,
                    $calculo['distancia_km'],
                    $calculo['tiempo_estimado_min'],
                    $calculo['tarifa_base'],
                    $calculo['precio_km'],
                    $calculo['recargos'],
                    $calculo['costo_total_envio'],
                    $calculo['ganancia_domiciliario'],
                    $calculo['comision_cerriflash'],
                    $metodoPago
                ]);

                $domicilioId = (int)$db->lastInsertId();

                $stmtHist = $db->prepare("
                    INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id, latitud, longitud)
                    VALUES (?, 'buscando_domiciliario', 'Domicilio publicado en bolsa de repartidores CerriFlash.', ?, ?, ?)
                ");
                $stmtHist->execute([$domicilioId, $user['id'], $latRecogida, $lngRecogida]);

                // Notificar al admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, url_accion)
                    VALUES (1, 'Nuevo domicilio publicado', ?, 'info', 'admin/domicilios.php')
                ");
                $stmtNotif->execute(["Restaurante {$restaurante['nombre_comercial']} publicó el envío {$codigoSeguimiento}"]);

                $db->commit();

                set_flash('success', "¡Domicilio #{$codigoSeguimiento} publicado con mapa y coordenadas! Repartidores en línea han sido notificados.");
                redirect('restaurante/domicilios.php');

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Error al publicar domicilio: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-box-seam-fill text-danger me-2"></i>CREAR DOMICILIO</h2>
            <p class="text-muted small mb-0">Ubica en el mapa interactivo el origen y destino para el cálculo automático de ruta.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/restaurante/domicilios.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-card-list me-1"></i> Ver Mis Domicilios
            </a>
            <a href="<?= BASE_URL ?>/restaurante/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $e): ?>
                    <li><?= e($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="formCrearDomicilio">
        <?= csrf_field() ?>
        <input type="hidden" name="lat_recogida" id="latRecogida" value="3.68412000">
        <input type="hidden" name="lng_recogida" id="lngRecogida" value="-76.31345000">
        <input type="hidden" name="lat_entrega" id="latEntrega" value="3.68950000">
        <input type="hidden" name="lng_entrega" id="lngEntrega" value="-76.31900000">

        <div class="row g-4">
            <!-- Formulario Principal -->
            <div class="col-lg-8">
                <!-- Mapa de Rutas Interactivo -->
                <div class="card-cf bg-white p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-map-fill text-primary me-2"></i>Mapa de Recorrido CerriFlash</h6>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Arrastra el pin verde o haz clic en el mapa para fijar el destino</small>
                    </div>
                    <div id="mapaCreacion" style="height: 320px; width: 100%; border-radius: 8px; z-index: 1;"></div>
                </div>

                <div class="card-cf bg-white p-4 mb-4">
                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-geo-alt-fill text-danger me-2"></i>1. Puntos de Trayecto
                    </h5>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dirección de Recogida (Restaurante) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shop text-danger"></i></span>
                            <input type="text" class="form-control" name="direccion_recogida" id="inpDirRecogida" value="<?= e($_POST['direccion_recogida'] ?? $restaurante['direccion']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dirección de Entrega (Cliente) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-pin-map-fill text-success"></i></span>
                            <input type="text" class="form-control" name="direccion_entrega" id="inpDirEntrega" value="<?= e($_POST['direccion_entrega'] ?? 'Carrera 13 # 8-40 Barrio San Antonio') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold d-flex justify-content-between">
                                <span>Distancia Calculada en Ruta:</span>
                                <strong id="lblDistancia" class="text-primary">2.4 km</strong>
                            </label>
                            <input type="range" class="form-range" min="0.5" max="15" step="0.1" id="rangeDistancia" name="distancia_km" value="<?= e($_POST['distancia_km'] ?? '2.4') ?>">
                            <small class="text-muted">Ajustada automáticamente con el mapa.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Recargos Adicionales</label>
                            <select class="form-select" name="recargos" id="selRecargos">
                                <option value="0" selected>Sin recargo ($0)</option>
                                <option value="<?= $tarifaActiva['recargo_nocturno'] ?>">Nocturno / Fin de semana (+<?= format_currency($tarifaActiva['recargo_nocturno']) ?>)</option>
                                <option value="<?= $tarifaActiva['recargo_alta_demanda'] ?>">Lluvia / Alta Demanda (+<?= format_currency($tarifaActiva['recargo_alta_demanda']) ?>)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-cf bg-white p-4 mb-4">
                    <h5 class="fw-bold text-secondary mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-fill text-primary me-2"></i>2. Datos del Cliente y Pedido
                    </h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">N° Pedido / Factura</label>
                            <input type="text" class="form-control" name="numero_pedido_origen" value="<?= e($_POST['numero_pedido_origen'] ?? '') ?>" placeholder="Ej. PED-1052">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nombre del Cliente *</label>
                            <input type="text" class="form-control" name="nombre_cliente" value="<?= e($_POST['nombre_cliente'] ?? '') ?>" placeholder="Ej. Carlos Mendoza" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono del Cliente *</label>
                            <input type="tel" class="form-control" name="telefono_cliente" value="<?= e($_POST['telefono_cliente'] ?? '') ?>" placeholder="Ej. 3151234567" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción del Paquete *</label>
                        <input type="text" class="form-control" name="descripcion_paquete" value="<?= e($_POST['descripcion_paquete'] ?? '') ?>" placeholder="Ej. 2 Bandejas Paisas + Bebidas" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Valor Declarado ($ COP)</label>
                            <input type="number" class="form-control" name="valor_declarado_pedido" value="<?= e($_POST['valor_declarado_pedido'] ?? '42000') ?>" step="500">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Hora de Recogida</label>
                            <input type="time" class="form-control" name="hora_recogida_estimada" value="<?= date('H:i', strtotime('+15 minutes')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Método de Cobro Envío</label>
                            <select class="form-select" name="metodo_pago">
                                <option value="contraentrega" selected>Contra Entrega</option>
                                <option value="digital">Transferencia / Digital</option>
                                <option value="efectivo">Efectivo Anticipado</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Observaciones de Entrega</label>
                        <textarea class="form-control" name="observaciones" rows="2" placeholder="Indicaciones para el repartidor"><?= e($_POST['observaciones'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tarjeta de Precio -->
            <div class="col-lg-4">
                <div class="card-cf bg-white p-4 sticky-top" style="top: 85px;">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h5 class="fw-bold mb-0">Cálculo Automático</h5>
                        <span class="badge bg-success-subtle text-success px-2 py-1"><i class="bi bi-check2 me-1"></i>En Línea</span>
                    </div>

                    <ul class="list-unstyled small mb-3">
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Tarifa Base:</span>
                            <span id="resBase"><?= format_currency($tarifaActiva['tarifa_base']) ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Distancia (<span id="resKmText">2.4</span> km × <?= format_currency($tarifaActiva['precio_por_km']) ?>):</span>
                            <span id="resKm">$2.880</span>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Recargos:</span>
                            <span id="resRecargos">$0</span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-top pt-2">
                            <span class="fw-bold">Costo del Domicilio:</span>
                            <strong class="fs-5 text-dark" id="resTotal">$6.400</strong>
                        </li>
                    </ul>

                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="d-flex justify-content-between align-items-center small mb-2">
                            <span class="text-success fw-semibold"><i class="bi bi-bicycle me-1"></i>Repartidor (80%):</span>
                            <strong class="text-success fs-6" id="resDomi">$5.120</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="bi bi-shield-check me-1"></i>CerriFlash (20%):</span>
                            <span class="text-secondary fw-semibold" id="resComision">$1.280</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-4 text-muted small">
                        <i class="bi bi-clock-history fs-5 text-primary"></i>
                        <span>Tiempo estimado: <strong id="resTiempo">18 min</strong></span>
                    </div>

                    <button type="submit" class="btn btn-primary-cf w-100 py-3 fw-bold fs-5 shadow">
                        <i class="bi bi-broadcast me-2"></i> PUBLICAR DOMICILIO
                    </button>
                    <small class="text-muted text-center d-block mt-2">
                        Se enviará la alerta a los repartidores en línea.
                    </small>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Coordenadas base en El Cerrito, Valle del Cauca
    let latOrigen = 3.68412;
    let lngOrigen = -76.31345;
    let latDestino = 3.68950;
    let lngDestino = -76.31900;

    const map = L.map('mapaCreacion').setView([latOrigen, lngOrigen], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Íconos
    const iconRestaurante = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const iconCliente = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const markerOrigen = L.marker([latOrigen, lngOrigen], { icon: iconRestaurante, draggable: true }).addTo(map)
        .bindPopup("<strong>Restaurante (Recogida)</strong>").openPopup();

    const markerDestino = L.marker([latDestino, lngDestino], { icon: iconCliente, draggable: true }).addTo(map)
        .bindPopup("<strong>Cliente (Entrega)</strong>");

    let rutaLine = L.polyline([[latOrigen, lngOrigen], [latDestino, lngDestino]], { color: '#ff5722', weight: 4, dashArray: '6, 8' }).addTo(map);

    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radio de la Tierra en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        // Factor vial urbano de 1.25 para simular calles reales
        return Math.max(0.5, Math.round((R * c * 1.25) * 10) / 10);
    }

    function actualizarMapaYRuta() {
        const p1 = markerOrigen.getLatLng();
        const p2 = markerDestino.getLatLng();

        document.getElementById('latRecogida').value = p1.lat.toFixed(7);
        document.getElementById('lngRecogida').value = p1.lng.toFixed(7);
        document.getElementById('latEntrega').value = p2.lat.toFixed(7);
        document.getElementById('lngEntrega').value = p2.lng.toFixed(7);

        rutaLine.setLatLngs([p1, p2]);

        const distancia = haversineDistance(p1.lat, p1.lng, p2.lat, p2.lng);
        document.getElementById('rangeDistancia').value = distancia;
        recalculate();
    }

    markerOrigen.on('dragend', actualizarMapaYRuta);
    markerDestino.on('dragend', actualizarMapaYRuta);

    map.on('click', (e) => {
        markerDestino.setLatLng(e.latlng);
        actualizarMapaYRuta();
    });

    // Recálculo económico
    const range = document.getElementById('rangeDistancia');
    const lbl = document.getElementById('lblDistancia');
    const selRecargos = document.getElementById('selRecargos');
    const resBase = document.getElementById('resBase');
    const resKmText = document.getElementById('resKmText');
    const resKm = document.getElementById('resKm');
    const resRecargos = document.getElementById('resRecargos');
    const resTotal = document.getElementById('resTotal');
    const resDomi = document.getElementById('resDomi');
    const resComision = document.getElementById('resComision');
    const resTiempo = document.getElementById('resTiempo');

    const base = <?= (float)$tarifaActiva['tarifa_base'] ?>;
    const precioKm = <?= (float)$tarifaActiva['precio_por_km'] ?>;
    const minima = <?= (float)$tarifaActiva['tarifa_minima'] ?>;
    const comisionPct = <?= (float)$tarifaActiva['comision_plataforma_porcentaje'] ?>;

    function formatMoney(amount) {
        return '$' + Math.round(amount).toLocaleString('es-CO');
    }

    function recalculate() {
        const km = parseFloat(range.value) || 1;
        const recargo = parseFloat(selRecargos.value) || 0;

        lbl.innerText = km.toFixed(1) + ' km';
        resKmText.innerText = km.toFixed(1);

        const subtotalKm = km * precioKm;
        resKm.innerText = formatMoney(subtotalKm);
        resRecargos.innerText = formatMoney(recargo);

        let total = base + subtotalKm + recargo;
        if (total < minima) total = minima;
        total = Math.round(total / 100) * 100;

        const comision = Math.round(total * (comisionPct / 100));
        const ganancia = total - comision;
        const tiempo = Math.max(10, Math.round(km * 4 + 8));

        resTotal.innerText = formatMoney(total);
        resDomi.innerText = formatMoney(ganancia);
        resComision.innerText = formatMoney(comision);
        resTiempo.innerText = tiempo + ' min';
    }

    range.addEventListener('input', recalculate);
    selRecargos.addEventListener('change', recalculate);
    recalculate();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
