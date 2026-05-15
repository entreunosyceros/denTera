<?php
/**
 * Cabecera común. Define antes de incluir:
 * - $header_tagline (string): texto bajo el nombre DenTera
 * - $header_nav: 'dashboard' (módulos) o 'page' (solo volver a agenda)
 * - $header_class (string, opcional): clases del <header>, por defecto "header"
 */
if (!isset($usuario)) {
    $usuario = usuarioActual();
}
$nombreUsuario = htmlspecialchars($usuario['nombre'] ?? '', ENT_QUOTES, 'UTF-8');

$header_tagline = isset($header_tagline) ? (string) $header_tagline : '';
$header_nav = isset($header_nav) && $header_nav === 'dashboard' ? 'dashboard' : 'page';
$header_class = isset($header_class) ? trim((string) $header_class) : 'header';
?>
<header class="<?= htmlspecialchars($header_class, ENT_QUOTES, 'UTF-8') ?>">
    <div class="header-content">
        <div class="header-brand">
            <h1><a href="index.php" class="header-title-link">DenTera</a></h1>
            <?php if ($header_tagline !== ''): ?>
                <span class="header-tagline"><?= htmlspecialchars($header_tagline, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <nav class="header-nav" aria-label="<?= $header_nav === 'dashboard' ? 'Módulos' : 'Navegación' ?>">
                <?php if ($header_nav === 'dashboard'): ?>
                    <a href="pacientes.php" class="nav-pill">&#x1F465; Pacientes</a>
                    <a href="configuracion.php" class="nav-pill">&#x2699; Configuración</a>
                    <a href="doctores.php" class="nav-pill">&#x1F468;&#x200D;&#x2695;&#xFE0F; Equipo</a>
                    <a href="tratamientos.php" class="nav-pill">&#x1F48A; Tratamientos</a>
                <?php else: ?>
                    <a href="index.php" class="nav-pill">&#x2190; Agenda</a>
                    <a href="pacientes.php" class="nav-pill">&#x1F465; Pacientes</a>
                <?php endif; ?>
            </nav>
            <div class="user-menu">
                <span class="user-name" title="<?= $nombreUsuario ?>"><?= $nombreUsuario ?></span>
                <a href="logout.php" class="btn btn-logout">Salir</a>
            </div>
        </div>
    </div>
</header>
