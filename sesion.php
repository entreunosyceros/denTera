<?php
session_start();

function usuarioLogueado() {
    return isset($_SESSION['usuario_id']);
}

function usuarioActual() {
    if (!usuarioLogueado()) return null;
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'rol' => $_SESSION['usuario_rol'],
    ];
}

function requerirLogin() {
    if (!usuarioLogueado()) {
        header('Location: login.php');
        exit;
    }
}

function registrarAccion($pdo, $accion, $tabla, $registro_id = null, $descripcion = '') {
    if (!usuarioLogueado()) return;
    $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, descripcion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $accion, $tabla, $registro_id, $descripcion]);
}
