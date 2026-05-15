<?php
require_once 'config.php';
require_once 'sesion.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '') {
        $errores[] = 'Introduce tu nombre de usuario.';
    }
    if ($password === '') {
        $errores[] = 'Introduce tu contraseña.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = ? AND activo = 1");
        $stmt->execute([$nombre]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            header('Location: index.php');
            exit;
        } else {
            $errores[] = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Iniciar Sesión</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <img src="img/logo.png" alt="DenTera" class="login-logo">
            <h2>Iniciar Sesión</h2>
            <p>Introduce tus credenciales para acceder</p>
        </div>

        <?php if (!empty($errores)): ?>
            <?php foreach ($errores as $e): ?>
                <div class="message message-error">&#x26A0; <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nombre">Usuario</label>
                <input type="text" id="nombre" name="nombre" required
                       placeholder="admin o auxiliar" value="admin" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required
                       placeholder="admin123" value="admin123">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            <p class="login-hint">
                Por defecto: <strong>admin</strong> / <strong>admin123</strong> o <strong>auxiliar</strong> / <strong>auxiliar123</strong>
            </p>
        </form>
    </div>
</div>

</body>
</html>
