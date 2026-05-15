<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT paciente FROM citas WHERE id = ?");
    $stmt->execute([$id]);
    $cita = $stmt->fetch();
    $nombre = $cita ? $cita['paciente'] : 'Desconocido';

    $stmt = $pdo->prepare("DELETE FROM citas WHERE id = ?");
    $stmt->execute([$id]);

    registrarAccion($pdo, 'eliminar', 'citas', $id, "Cita eliminada de $nombre");
}

header('Location: index.php?msg=eliminada');
exit;
