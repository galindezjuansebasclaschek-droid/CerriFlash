<?php
$pageTitle = 'Documentación API REST - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold text-uppercase">Desarrolladores Móviles</span>
        <h2 class="display-6 fw-bold mt-2 brand-text">Cerri<span>Flash</span> REST API v1.0</h2>
        <p class="text-muted">Especificación técnica de endpoints para integración con aplicaciones móviles Android, iOS y sistemas de comandas externas.</p>
    </div>

    <!-- Guía de Autenticación -->
    <div class="card-cf bg-white p-4 mb-4">
        <h5 class="fw-bold mb-2"><i class="bi bi-shield-lock-fill text-success me-2"></i>Esquema de Autenticación</h5>
        <p class="small text-muted mb-3">
            Para consumir los endpoints protegidos, envía la cabecera HTTP con el Bearer Token obtenido en el login:
        </p>
        <div class="p-3 bg-dark text-white rounded-3 font-monospace small">
            Authorization: Bearer cf_tok_xxxxxxxxxxxxxxxxxxxxxxxx
        </div>
    </div>

    <!-- Catálogo de Endpoints -->
    <div class="row g-4">
        <!-- 1. Auth Login -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success fw-bold">POST</span>
                    <code class="fw-bold text-dark">/api/auth/login.php</code>
                </div>
                <p class="small text-muted mb-2">Autentica usuario y retorna el Bearer Token para sesiones móviles.</p>
                <div class="p-2 bg-light rounded small font-monospace mb-2">
                    <strong>Body JSON:</strong><br>
                    { "email": "domi@cerriflash.com", "password": "..." }
                </div>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 200 OK:</strong><br>
                    { "success": true, "token": "...", "usuario": {...} }
                </div>
            </div>
        </div>

        <!-- 2. Domicilios Disponibles -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary fw-bold">GET</span>
                    <code class="fw-bold text-dark">/api/domicilios/disponibles.php</code>
                </div>
                <p class="small text-muted mb-2">Bolsa de pedidos en espera de ser aceptados (para app de repartidores).</p>
                <div class="p-2 bg-light rounded small font-monospace mb-2">
                    <strong>Headers:</strong> Authorization: Bearer &lt;token&gt;
                </div>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 200 OK:</strong><br>
                    { "success": true, "total": 2, "data": [...] }
                </div>
            </div>
        </div>

        <!-- 3. Aceptar Domicilio -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success fw-bold">POST</span>
                    <code class="fw-bold text-dark">/api/domicilios/aceptar.php</code>
                </div>
                <p class="small text-muted mb-2">Aceptación atómica con bloqueo para el repartidor.</p>
                <div class="p-2 bg-light rounded small font-monospace mb-2">
                    <strong>Body JSON:</strong> { "domicilio_id": 10 }
                </div>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 200 OK:</strong><br>
                    { "success": true, "message": "Domicilio asignado..." }
                </div>
            </div>
        </div>

        <!-- 4. Actualizar Estado + GPS -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success fw-bold">POST</span>
                    <code class="fw-bold text-dark">/api/domicilios/actualizar-estado.php</code>
                </div>
                <p class="small text-muted mb-2">Transición de estados y actualización de posición geográfica GPS en ruta.</p>
                <div class="p-2 bg-light rounded small font-monospace mb-2">
                    <strong>Body JSON:</strong><br>
                    { "domicilio_id": 10, "nuevo_estado": "en_camino_entrega", "lat": 3.684, "lng": -76.313 }
                </div>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 200 OK:</strong><br>
                    { "success": true, "nuevo_estado": "en_camino_entrega" }
                </div>
            </div>
        </div>

        <!-- 5. Tracking de Pedido -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary fw-bold">GET</span>
                    <code class="fw-bold text-dark">/api/domicilios/tracking.php?codigo=CF-XXXX</code>
                </div>
                <p class="small text-muted mb-2">Consulta pública del estado, posición del repartidor y coordenadas de entrega.</p>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 200 OK:</strong><br>
                    { "success": true, "domicilio": {...}, "historial": [...] }
                </div>
            </div>
        </div>

        <!-- 6. Crear Pedido -->
        <div class="col-lg-6">
            <div class="card-cf bg-white p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success fw-bold">POST</span>
                    <code class="fw-bold text-dark">/api/pedidos/crear.php</code>
                </div>
                <p class="small text-muted mb-2">Creación programática de despacho desde POS o app del comercio.</p>
                <div class="p-2 bg-light rounded small font-monospace mb-2">
                    <strong>Body JSON:</strong><br>
                    { "direccion_entrega": "...", "nombre_cliente": "...", "telefono_cliente": "...", "descripcion": "...", "distancia_km": 3.2 }
                </div>
                <div class="p-2 bg-light rounded small font-monospace text-success">
                    <strong>Response 201 Created:</strong><br>
                    { "success": true, "codigo_seguimiento": "CF-XXXXXX" }
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

