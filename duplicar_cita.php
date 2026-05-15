<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sesion.php';
requerirLogin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM citas WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header('Location: index.php');
    exit;
}

header('Location: crear.php?desde_cita=' . $id);
exit;
