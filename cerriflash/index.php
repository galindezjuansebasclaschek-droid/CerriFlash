<?php
$pageTitle = 'CerriFlash - Tu pedido, rápido y seguro.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-cf text-center text-lg-start position-relative">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-warning mb-3">
                    <i class="bi bi-stars"></i>
                    <span class="small fw-semibold">Plataforma Logística y de Domicilios</span>
                </div>
                <h1 class="display-4 fw-black text-white mb-3">
                    Cerri<span class="text-warning">Flash</span>
                </h1>
                <p class="fs-3 fw-bold text-white-50 mb-3">
                    “Tu pedido, rápido y seguro.”
                </p>
                <p class="lead text-light opacity-90 mb-4">
                    Conectamos restaurantes, comercios y empresas locales con repartidores independientes disponibles para prestar el servicio más ágil, seguro y confiable de la región.
                </p>

                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= BASE_URL ?>/<?= current_user_role() ?>/index.php" class="btn btn-primary-cf btn-lg px-4">
                            <i class="bi bi-speedometer2 me-2"></i> Ir a mi Panel
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/registro-restaurante.php" class="btn btn-primary-cf btn-lg px-4">
                            <i class="bi bi-shop me-2"></i> Registra tu Negocio
                        </a>
                        <a href="<?= BASE_URL ?>/registro-domiciliario.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-scooter me-2"></i> Conviértete en Domiciliario
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Métricas destacadas -->
                <div class="row g-4 mt-4 pt-4 border-top border-white border-opacity-10 text-white">
                    <div class="col-4">
                        <h4 class="fw-bold mb-0 text-warning">100%</h4>
                        <small class="text-white-50">Transparencia</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bold mb-0 text-warning">&lt; 25 min</h4>
                        <small class="text-white-50">Entrega promedio</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bold mb-0 text-warning">24/7</h4>
                        <small class="text-white-50">Seguimiento en vivo</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 text-center">
                <div class="card-cf p-4 bg-white text-dark shadow-lg rounded-4 text-start">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-danger-subtle text-danger fw-bold px-3 py-2 rounded-pill">
                            <i class="bi bi-broadcast me-1"></i> Radar Activo
                        </span>
                        <span class="status-indicator">
                            <span class="pulse-dot online"></span>
                            <small class="text-success fw-bold">Repartidores en línea</small>
                        </span>
                    </div>

                    <h5 class="fw-bold mb-3">Solicitud de Entrega Rápida</h5>
                    <p class="text-muted small">Cálculo de tarifas automático en base a distancia exacta y tiempo estimado.</p>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                            <div>
                                <small class="text-muted d-block">Punto de Recogida</small>
                                <strong class="small">Restaurante / Negocio Aliado</strong>
                            </div>
                        </div>
                        <div class="border-start border-2 ms-2 ps-3 my-1" style="height: 16px;"></div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-pin-map-fill text-success me-2"></i>
                            <div>
                                <small class="text-muted d-block">Punto de Entrega</small>
                                <strong class="small">Cliente Final</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-warning-subtle text-dark mb-3">
                        <div>
                            <small class="text-muted d-block">Tarifa sugerida</small>
                            <span class="fs-4 fw-bold text-dark">$6.500 COP</span>
                        </div>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Distancia ~ 2.4 km</span>
                    </div>

                    <a href="<?= BASE_URL ?>/registro-restaurante.php" class="btn btn-primary-cf w-100 py-2">
                        Comenzar a Enviar Pedidos
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección: ¿Cómo funciona CerriFlash? -->
<section class="py-5" id="como-funciona">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill fw-bold text-uppercase">Proceso Simple</span>
            <h2 class="display-6 fw-bold mt-2">¿Cómo funciona CerriFlash?</h2>
            <p class="text-muted">Un ecosistema conectado en 3 pasos sencillos para agilizar tus despachos sin complicaciones.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box h-100">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-clipboard2-check"></i>
                    </div>
                    <h5 class="fw-bold mb-2">1. El Negocio Publica</h5>
                    <p class="text-muted small">
                        El restaurante o empresa ingresa los datos del cliente, origen y destino. CerriFlash calcula la distancia, tiempo y precio al instante.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-box h-100">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <h5 class="fw-bold mb-2">2. Repartidor Acepta</h5>
                    <p class="text-muted small">
                        Los domiciliarios disponibles reciben la notificación con la distancia y ganancia exacta. El primero en aceptar asegura el despacho.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-box h-100">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold mb-2">3. Entrega y Seguimiento</h5>
                    <p class="text-muted small">
                        El comercio y el cliente consultan el estado paso a paso hasta la entrega final, garantizando satisfacción y seguridad.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección: Para Restaurantes y Para Domiciliarios -->
<section class="py-5 bg-white border-top border-bottom" id="restaurantes">
    <div class="container py-4">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">PARA COMERCIOS</span>
                <h2 class="display-6 fw-bold mt-2">Potencia las ventas de tu negocio con entregas inmediatas</h2>
                <p class="text-muted">
                    No pierdas pedidos por falta de repartidores. Con <strong>CerriFlash</strong> tienes una flota de domiciliarios listos para llevar tus productos directo a la puerta de tus clientes.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Cálculo automático de precio por distancia exacta.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Panel intuitivo para publicar despachos en segundos.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Historial detallado de pedidos y costos por servicio.</span>
                    </li>
                </ul>
                <a href="<?= BASE_URL ?>/registro-restaurante.php" class="btn btn-primary-cf">
                    Registrar mi Restaurante o Negocio
                </a>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 border shadow-sm text-center">
                    <i class="bi bi-shop display-1 text-danger mb-3 d-block"></i>
                    <h5 class="fw-bold">Afíliate hoy mismo a CerriFlash</h5>
                    <p class="text-muted small mb-4">Revisamos tu documentación y activamos tu cuenta en tiempo récord.</p>
                    <div class="row g-2 text-start">
                        <div class="col-6 p-2 border rounded bg-white"><small class="text-muted d-block">Comisión por envío</small><strong>Tarifa justa fija</strong></div>
                        <div class="col-6 p-2 border rounded bg-white"><small class="text-muted d-block">Disponibilidad</small><strong>Todos los días</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center g-5 pt-5 border-top" id="domiciliarios">
            <div class="col-lg-6 order-lg-2">
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">PARA DOMICILIARIOS</span>
                <h2 class="display-6 fw-bold mt-2">Maneja tu tiempo y gana dinero por cada entrega</h2>
                <p class="text-muted">
                    Conviértete en repartidor CerriFlash y genera ingresos con tu bicicleta, motocicleta o automóvil. Tú decides cuándo conectarte y qué solicitudes tomar.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Conoce la ganancia neta antes de aceptar cualquier pedido.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Botón de disponibilidad 🟢 Disponible / ⚪ Desconectado.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>Liquidación clara y transparente sin cobros ocultos.</span>
                    </li>
                </ul>
                <a href="<?= BASE_URL ?>/registro-domiciliario.php" class="btn btn-outline-cf">
                    Unirme como Domiciliario
                </a>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="p-4 bg-light rounded-4 border shadow-sm text-center">
                    <i class="bi bi-scooter display-1 text-success mb-3 d-block"></i>
                    <h5 class="fw-bold">Gana más en tu tiempo libre</h5>
                    <p class="text-muted small mb-4">Solo requieres tu documento de identidad, tu vehículo y muchas ganas de prestar un servicio de calidad.</p>
                    <div class="row g-2 text-start">
                        <div class="col-6 p-2 border rounded bg-white"><small class="text-muted d-block">Flexibilidad</small><strong>Sin horarios fijos</strong></div>
                        <div class="col-6 p-2 border rounded bg-white"><small class="text-muted d-block">Ganancia promedio</small><strong>80% del valor total</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección: Tarifas y Cobertura -->
<section class="py-5" id="tarifas">
    <div class="container py-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill fw-bold">TARIFAS CLARAS</span>
                <h2 class="display-6 fw-bold mt-2">Cálculo transparente basado en distancia</h2>
                <p class="text-muted">
                    En <strong>CerriFlash</strong> calculamos las tarifas de manera equitativa utilizando la fórmula estándar:
                </p>
                <div class="p-3 bg-dark text-white rounded-3 mb-3 font-monospace small">
                    Precio = Tarifa Base + (Km × Precio por Km) + Recargos
                </div>
                <p class="text-muted small">
                    Tanto el restaurante como el repartidor conocen los desgloses exactos antes de confirmar el servicio.
                </p>
            </div>
            <div class="col-lg-7" id="cobertura">
                <div class="card-cf p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-map text-primary me-2"></i>Zona de Cobertura Principal</h5>
                    <p class="text-muted small mb-4">
                        Operamos activamente en el municipio de <strong>El Cerrito</strong>, corregimientos aledaños (Santa Elena, El Placer) y rutas especiales hacia Ginebra y Palmira.
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Casco Urbano</h6>
                                <p class="text-muted small mb-0">Entregas exprés en menos de 20 minutos.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-1"><i class="bi bi-signpost-2-fill text-primary me-1"></i> Zonas Rurales y Corregimientos</h6>
                                <p class="text-muted small mb-0">Rutas programadas con tarifas ajustadas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección FAQ -->
<section class="py-5 bg-white border-top" id="faq">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-bold">RESOLVEMOS TUS DUDAS</span>
            <h2 class="display-6 fw-bold mt-2">Preguntas Frecuentes</h2>
        </div>

        <div class="accordion accordion-flush max-w-800 mx-auto" id="faqAccordion">
            <div class="accordion-item border rounded mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        ¿Cómo se cobra la comisión de la plataforma?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        CerriFlash cobra una pequeña comisión porcentual transparente por cada domicilio completado, la cual es descontada del valor total pagado por el servicio de transporte.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        ¿Qué documentos requiere un domiciliario para activarse?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        Para registrarse se solicita documento de identidad, fotografía personal, y en caso de vehículo a motor (moto o carro), licencia de conducción, SOAT y tarjeta de propiedad. El equipo administrador revisa y aprueba cada cuenta.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        ¿Qué sucede si un cliente no recibe el pedido?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">
                        El domiciliario o el restaurante pueden activar el estado de “Incidencia” dentro de la plataforma. El equipo de soporte interviene de inmediato para coordinar la devolución o solución.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

