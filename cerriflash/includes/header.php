<?php
/**
 * CerriFlash - Header Global con Centro de Notificaciones y Menú por Rol
 */
require_once __DIR__ . '/../config/config.php';
$pageTitle = $pageTitle ?? APP_NAME . ' - ' . APP_SLOGAN;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Estilos Propios -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>

<!-- Navbar CerriFlash -->
<nav class="navbar navbar-expand-lg navbar-cf sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <span class="brand-badge"><i class="bi bi-lightning-charge-fill"></i></span>
            <span class="brand-text fs-4">Cerri<span>Flash</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navCerriFlash" aria-controls="navCerriFlash" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navCerriFlash">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user && $user['rol_nombre'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/usuarios.php"><i class="bi bi-people me-1"></i>Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/domiciliarios-activos.php"><i class="bi bi-radar me-1"></i>Flota</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/tarifas.php"><i class="bi bi-sliders me-1"></i>Tarifas</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/comisiones.php"><i class="bi bi-cash-stack me-1"></i>Comisiones</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/reportes.php"><i class="bi bi-bar-chart-fill me-1"></i>Reportes</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/admin/incidencias.php"><i class="bi bi-flag-fill me-1 text-danger"></i>Incidencias</a></li>
                <?php elseif ($user && $user['rol_nombre'] === 'restaurante'): ?>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/restaurante/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?= BASE_URL ?>/restaurante/crear-domicilio.php"><i class="bi bi-plus-circle me-1"></i>CREAR DOMICILIO</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/restaurante/domicilios.php"><i class="bi bi-card-list me-1"></i>Mis Domicilios</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/restaurante/perfil.php"><i class="bi bi-shop me-1"></i>Mi Comercio</a></li>
                <?php elseif ($user && $user['rol_nombre'] === 'domiciliario'): ?>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/domiciliario/index.php"><i class="bi bi-radar me-1"></i>Radar Solicitudes</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/domiciliario/billetera.php"><i class="bi bi-wallet2 me-1"></i>Mi Billetera</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/domiciliario/perfil.php"><i class="bi bi-person-gear me-1"></i>Mi Vehículo</a></li>
                <?php elseif ($user && $user['rol_nombre'] === 'cliente'): ?>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/cliente/index.php"><i class="bi bi-bag-check me-1"></i>Mis Pedidos</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/cliente/direcciones.php"><i class="bi bi-geo-alt me-1"></i>Direcciones</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="<?= BASE_URL ?>/cliente/perfil.php"><i class="bi bi-person-gear me-1"></i>Mi Perfil</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= BASE_URL ?>/index.php#como-funciona">¿Cómo funciona?</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= BASE_URL ?>/index.php#restaurantes">Para Comercios</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= BASE_URL ?>/index.php#domiciliarios">Para Domiciliarios</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= BASE_URL ?>/seguimiento.php"><i class="bi bi-crosshair me-1 text-danger"></i>Rastrear Pedido</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($user): ?>
                    <!-- Centro de Notificaciones -->
                    <div class="dropdown">
                        <button class="btn btn-light position-relative rounded-circle p-2 border" type="button" id="btnNotificaciones" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                            <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                            <span id="badgeNotifCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                                0
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" id="listaNotificaciones" style="width: 320px; max-height: 400px; overflow-y: auto;">
                            <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notificaciones</span>
                                <a href="#" id="btnMarcarLeidas" class="small text-decoration-none">Marcar leídas</a>
                            </h6></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li class="text-center py-3 text-muted small" id="notifVacias">Cargando alertas...</li>
                        </ul>
                    </div>

                    <!-- Menú de Usuario -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 py-2 px-3 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-primary"></i>
                            <span class="fw-semibold"><?= e($user['nombre']) ?></span>
                            <span class="badge bg-dark text-capitalize ms-1"><?= e($user['rol_nombre']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/<?= $user['rol_nombre'] ?>/index.php">
                                    <i class="bi bi-speedometer2 me-2"></i>Ir al Panel
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-cf">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-primary-cf dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-plus me-1"></i> Registrarme
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/registro-restaurante.php"><i class="bi bi-shop me-2 text-primary"></i>Soy Restaurante / Empresa</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/registro-domiciliario.php"><i class="bi bi-bicycle me-2 text-success"></i>Quiero ser Domiciliario</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/registro-cliente.php"><i class="bi bi-person me-2 text-secondary"></i>Soy Cliente</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Contenedor de Alertas Flash Globales -->
<div class="container mt-3">
    <?= display_flash() ?>
</div>

<main>

<?php if ($user): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('badgeNotifCount');
    const lista = document.getElementById('listaNotificaciones');
    const btnMarcar = document.getElementById('btnMarcarLeidas');

    async function cargarNotificaciones() {
        try {
            const res = await fetch('<?= BASE_URL ?>/api/notificaciones.php');
            const data = await res.json();
            if (data.success) {
                if (data.no_leidas > 0) {
                    badge.innerText = data.no_leidas;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }

                if (data.notificaciones.length === 0) {
                    document.getElementById('notifVacias').innerText = 'No tienes notificaciones recientes.';
                } else {
                    let html = '<li><h6 class="dropdown-header d-flex justify-content-between align-items-center"><span>Notificaciones</span><a href="#" id="btnMarcarLeidas" class="small text-decoration-none">Marcar leídas</a></h6></li><li><hr class="dropdown-divider my-1"></li>';
                    data.notificaciones.forEach(n => {
                        const bgClass = n.leido === 0 ? 'bg-light fw-bold' : '';
                        html += `
                            <li>
                                <a class="dropdown-item p-2 rounded ${bgClass} text-wrap" href="${n.url_accion || '#'}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-primary">${n.titulo}</small>
                                        <small class="text-muted" style="font-size: 0.7rem;">${n.fecha}</small>
                                    </div>
                                    <p class="small text-muted mb-0">${n.mensaje}</p>
                                </a>
                            </li>
                        `;
                    });
                    lista.innerHTML = html;

                    // Re-bind botón marcar leídas
                    const newBtn = document.getElementById('btnMarcarLeidas');
                    if (newBtn) {
                        newBtn.addEventListener('click', async (e) => {
                            e.preventDefault();
                            await fetch('<?= BASE_URL ?>/api/notificaciones.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'action=marcar_leidas'
                            });
                            cargarNotificaciones();
                        });
                    }
                }
            }
        } catch (e) {
            console.error('Error notificaciones:', e);
        }
    }

    cargarNotificaciones();
    setInterval(cargarNotificaciones, 10000); // Polling cada 10 seg
});
</script>
<?php endif; ?>
