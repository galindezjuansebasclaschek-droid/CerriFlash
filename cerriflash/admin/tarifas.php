<?php
$pageTitle = 'Configuración de Tarifas - CerriFlash';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$db = Database::getConnection();

// Cargar tarifa activa
$stmt = $db->query("SELECT * FROM tarifas WHERE activa = 1 ORDER BY id DESC LIMIT 1");
$tarifa = $stmt->fetch();

if (!$tarifa) {
    // Si no existe, crear la tarifa estándar por defecto
    $db->query("
        INSERT INTO tarifas (nombre, tarifa_base, precio_por_km, tarifa_minima, recargo_nocturno, recargo_alta_demanda, comision_plataforma_porcentaje, activa)
        VALUES ('Tarifa Oficial CerriFlash', 3500, 1200, 5000, 1500, 2000, 20.00, 1)
    ");
    $stmt = $db->query("SELECT * FROM tarifas WHERE activa = 1 ORDER BY id DESC LIMIT 1");
    $tarifa = $stmt->fetch();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $nombre       = trim($_POST['nombre'] ?? 'Tarifa General');
        $tarifaBase   = (float)($_POST['tarifa_base'] ?? 0);
        $precioKm     = (float)($_POST['precio_por_km'] ?? 0);
        $tarifaMinima = (float)($_POST['tarifa_minima'] ?? 0);
        $recNocturno  = (float)($_POST['recargo_nocturno'] ?? 0);
        $recDemanda   = (float)($_POST['recargo_alta_demanda'] ?? 0);
        $comisionPct  = (float)($_POST['comision_plataforma_porcentaje'] ?? 20);

        if ($tarifaBase <= 0)   $errors[] = 'La tarifa base debe ser mayor a 0.';
        if ($precioKm <= 0)     $errors[] = 'El precio por kilómetro debe ser mayor a 0.';
        if ($tarifaMinima <= 0) $errors[] = 'La tarifa mínima debe ser mayor a 0.';
        if ($comisionPct < 0 || $comisionPct > 100) $errors[] = 'El porcentaje de comisión debe estar entre 0% y 100%.';

        if (empty($errors)) {
            $stmtUp = $db->prepare("
                UPDATE tarifas SET
                    nombre = ?,
                    tarifa_base = ?,
                    precio_por_km = ?,
                    tarifa_minima = ?,
                    recargo_nocturno = ?,
                    recargo_alta_demanda = ?,
                    comision_plataforma_porcentaje = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUp->execute([
                $nombre, $tarifaBase, $precioKm, $tarifaMinima,
                $recNocturno, $recDemanda, $comisionPct, $tarifa['id']
            ]);

            set_flash('success', '¡Parámetros de tarifas y comisiones actualizados correctamente!');
            redirect('admin/tarifas.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1 brand-text"><i class="bi bi-cash-coin text-warning me-2"></i>Configuración de Tarifas Cerri<span>Flash</span></h2>
            <p class="text-muted small mb-0">Define los valores base, costo por kilómetro y porcentajes de comisión de la plataforma.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people me-1"></i> Usuarios
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

    <div class="row g-4">
        <!-- Columna Izquierda: Formulario de Configuración -->
        <div class="col-lg-7">
            <div class="card-cf bg-white p-4">
                <h5 class="fw-bold mb-3 text-secondary pb-2 border-bottom">
                    <i class="bi bi-sliders me-2 text-primary"></i>Fórmula de Cálculo: 
                    <code class="text-dark small">Precio = Base + (Km × Precio_Km) + Recargos</code>
                </h5>

                <form method="POST" action="" id="formTarifas">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre de la Zona / Esquema Tarifario</label>
                        <input type="text" class="form-control" name="nombre" value="<?= e($tarifa['nombre']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tarifa Base ($ COP) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="tarifa_base" id="inpBase" value="<?= (float)$tarifa['tarifa_base'] ?>" step="100" required>
                            </div>
                            <small class="text-muted">Costo de partida para cualquier servicio.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Precio por Kilómetro ($ COP) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="precio_por_km" id="inpKm" value="<?= (float)$tarifa['precio_por_km'] ?>" step="100" required>
                            </div>
                            <small class="text-muted">Incremento por cada km de trayecto.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tarifa Mínima Garantizada ($ COP) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="tarifa_minima" id="inpMinima" value="<?= (float)$tarifa['tarifa_minima'] ?>" step="100" required>
                            </div>
                            <small class="text-muted">Ningún domicilio costará menos que este valor.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Comisión CerriFlash (%) *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="comision_plataforma_porcentaje" id="inpComision" value="<?= (float)$tarifa['comision_plataforma_porcentaje'] ?>" min="0" max="100" step="0.5" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Porcentaje retenido por la plataforma (ej. 20%).</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Recargo Nocturno / Festivo ($ COP)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="recargo_nocturno" id="inpNocturno" value="<?= (float)$tarifa['recargo_nocturno'] ?>" step="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Recargo Lluvia / Alta Demanda ($ COP)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="recargo_alta_demanda" id="inpDemanda" value="<?= (float)$tarifa['recargo_alta_demanda'] ?>" step="100">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-cf px-4 py-2 fw-semibold">
                        <i class="bi bi-save me-2"></i> Guardar Nueva Configuración de Tarifas
                    </button>
                </form>
            </div>
        </div>

        <!-- Columna Derecha: Simulador y Tabla de Proyecciones -->
        <div class="col-lg-5">
            <div class="card-cf bg-white p-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-calculator text-success me-2"></i>Simulador de Reparto en Vivo
                </h5>

                <p class="text-muted small">
                    Proyección de ingresos calculados en base a los valores ingresados a la izquierda:
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle small text-center mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Distancia</th>
                                <th>Costo Envío</th>
                                <th>Repartidor</th>
                                <th>CerriFlash</th>
                            </tr>
                        </thead>
                        <tbody id="tablaSimulada">
                            <!-- Inyectado dinámicamente con JS -->
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Ejemplo Oficial CerriFlash:</h6>
                    <small class="text-muted d-block">
                        Para un domicilio de <strong>$10.000 COP</strong> con comisión del <strong>20%</strong>:
                    </small>
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top small">
                        <span class="text-success fw-bold">Repartidor (80%):</span>
                        <strong>$8.000 COP</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-primary fw-bold">CerriFlash (20%):</span>
                        <strong>$2.000 COP</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inpBase = document.getElementById('inpBase');
    const inpKm = document.getElementById('inpKm');
    const inpMinima = document.getElementById('inpMinima');
    const inpComision = document.getElementById('inpComision');
    const tbody = document.getElementById('tablaSimulada');

    function fmt(val) {
        return '$' + Math.round(val).toLocaleString('es-CO');
    }

    function simular() {
        const base = parseFloat(inpBase.value) || 0;
        const kmPrice = parseFloat(inpKm.value) || 0;
        const minima = parseFloat(inpMinima.value) || 0;
        const pct = parseFloat(inpComision.value) || 0;

        const distancias = [1.0, 2.5, 4.0, 6.0, 10.0];
        let html = '';

        distancias.forEach(km => {
            let total = base + (km * kmPrice);
            if (total < minima) total = minima;
            total = Math.round(total / 100) * 100;

            const comision = Math.round(total * (pct / 100));
            const domi = total - comision;

            html += `
                <tr>
                    <td class="fw-bold">${km.toFixed(1)} km</td>
                    <td class="fw-bold text-dark">${fmt(total)}</td>
                    <td class="text-success fw-semibold">${fmt(domi)}</td>
                    <td class="text-secondary">${fmt(comision)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    [inpBase, inpKm, inpMinima, inpComision].forEach(inp => inp.addEventListener('input', simular));
    simular();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

