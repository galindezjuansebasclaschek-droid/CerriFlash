<?php
/**
 * CerriFlash - Cierre de Sesión Seguro
 */
require_once __DIR__ . '/config/config.php';

logout_user();
set_flash('info', 'Has cerrado sesión exitosamente. ¡Hasta pronto!');
redirect('login.php');

