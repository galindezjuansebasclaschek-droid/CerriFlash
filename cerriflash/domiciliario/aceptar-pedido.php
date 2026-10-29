<?php
/**
 * CerriFlash - Aceptación Atómica y Bloqueo Concurrente de Domicilio
 */
require_once __DIR__ . '/../config/config.php';
require_role('domiciliario');

$user = current_user();

if ($user['estado'] !== 'aprobado') {
    set_flash('danger', 'Tu cuenta debe estar aprobada para tomar pedidos.');
    redirect('domiciliario/index.php');
}

$db = Database::getConnection();

// Cargar domiciliario_id
$stmtD = $db->prepare("SELECT id, disponible FROM domiciliarios WHERE usuario_id = ?");
$stmtD->execute([$user['id']]);
$domi = $stmtD->fetch();
$domiId = (int)($domi['id'] ?? 0);

if (!$domi || (int)$domi['disponible'] !== 1) {
    set_flash('warning', 'Debes tener tu disponibilidad activa para aceptar un pedido.');
    redirect('domiciliario/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        set_flash('danger', 'Token de seguridad inválido.');
        redirect('domiciliario/index.php');
    }

    $domicilioId = (int)($_POST['domicilio_id'] ?? 0);

    if (!$domicilioId) {
        set_flash('danger', 'Domicilio no válido.');
        redirect('domiciliario/index.php');
    }

    try {
        $db->beginTransaction();

        // Actualización atómica condicionada: solo si domiciliario_id sigue siendo NULL
        $stmtLock = $db->prepare("
            UPDATE domicilios 
            SET domiciliario_id = ?, 
                estado = 'aceptado', 
                fecha_asignacion = NOW() 
            WHERE id = ? 
              AND domiciliario_id IS NULL 
              AND estado IN ('publicado', 'buscando_domiciliario')
        ");
        $stmtLock->execute([$domiId, $domicilioId]);

        if ($stmtLock->rowCount() === 1) {
            // Éxito: El pedido quedó bloqueado para los demás domiciliarios
            $stmtHist = $db->prepare("
                INSERT INTO historial_estados (domicilio_id, estado, observaciones, usuario_id) 
                VALUES (?, 'aceptado', 'El domiciliario aceptó la orden de despacho.', ?)
            ");
            $stmtHist->execute([$domicilioId, $user['id']]);

            // Obtener datos del restaurante para notificar
            $stmtRest = $db->prepare("SELECT restaurante_id, codigo_seguimiento FROM domicilios WHERE id = ?");
            $stmtRest->execute([$domicilioId]);
            $domInfo = $stmtRest->fetch();

            if ($domInfo) {
                // Notificar al restaurante
                $stmtGetRestUser = $db->prepare("SELECT usuario_id FROM restaurantes WHERE id = ?");
                $stmtGetRestUser->execute([$domInfo['restaurante_id']]);
                $restUserId = $stmtGetRestUser->fetchColumn();

                if ($restUserId) {
                    $stmtNotif = $db->prepare("
                        INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, url_accion)
                        VALUES (?, 'Domiciliario asignado', ?, 'success', 'restaurante/domicilios.php')
                    ");
                    $stmtNotif->execute([
                        $restUserId,
                        "El repartidor {$user['nombre']} aceptó el domicilio #{$domInfo['codigo_seguimiento']} y va en camino."
                    ]);
                }
            }

            $db->commit();
            set_flash('success', '¡Has tomado el domicilio con éxito! Inicia tu recorrido hacia el restaurante.');
            redirect("domiciliario/pedido-activo.php?id={$domicilioId}");

        } else {
            // El pedido ya fue tomado por otro repartidor o cancelado
            $db->rollBack();
            set_flash('warning', '¡Lo sentimos! Este pedido ya fue aceptado por otro repartidor.');
            redirect('domiciliario/index.php');
        }

    } catch (Exception $e) {
        $db->rollBack();
        set_flash('danger', 'Error al procesar la aceptación del pedido: ' . $e->getMessage());
        redirect('domiciliario/index.php');
    }
} else {
    redirect('domiciliario/index.php');
}

