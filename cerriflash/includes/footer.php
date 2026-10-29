</main>

<!-- Footer CerriFlash -->
<footer class="footer-cf mt-5">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-badge"><i class="bi bi-lightning-charge-fill"></i></span>
                    <span class="brand-text fs-4 text-white">Cerri<span class="text-warning">Flash</span></span>
                </div>
                <p class="small text-light opacity-75">
                    <strong>“Tu pedido, rápido y seguro.”</strong><br>
                    La plataforma líder que conecta restaurantes, comercios y empresas con repartidores comprometidos y confiables.
                </p>
                <div class="d-flex gap-3 fs-5">
                    <a href="#" class="text-secondary"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-secondary"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-secondary"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-white fw-bold mb-3">Plataforma</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/index.php#como-funciona">¿Cómo funciona?</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#tarifas">Tarifas y Precios</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#cobertura">Zonas de Cobertura</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#faq">Preguntas Frecuentes</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Únete a CerriFlash</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/registro-restaurante.php">Registra tu Negocio</a></li>
                    <li><a href="<?= BASE_URL ?>/registro-domiciliario.php">Conviértete en Domiciliario</a></li>
                    <li><a href="<?= BASE_URL ?>/registro-cliente.php">Registro de Clientes</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Acceso a Paneles</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6" id="contacto">
                <h6 class="text-white fw-bold mb-3">Contacto y Soporte</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 text-light opacity-75">
                    <li><i class="bi bi-geo-alt me-2 text-warning"></i> El Cerrito, Valle del Cauca</li>
                    <li><i class="bi bi-telephone me-2 text-warning"></i> +57 (315) 123-4567</li>
                    <li><i class="bi bi-envelope me-2 text-warning"></i> soporte@cerriflash.com</li>
                    <li><i class="bi bi-clock me-2 text-warning"></i> Lun - Dom: 7:00 AM - 11:00 PM</li>
                </ul>
            </div>
        </div>

        <div class="pt-3 border-top border-secondary text-center small text-light opacity-50">
            &copy; <?= date('Y') ?> CerriFlash. Todos los derechos reservados. “Tu pedido, rápido y seguro.”
        </div>
    </div>
</footer>

<!-- Scripts JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- App JS -->
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
</body>
</html>

