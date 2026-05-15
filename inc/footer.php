<?php
/**
 * Pie común. Opcional antes de incluir:
 * - $footer_subtitulo (string|null): texto tras "— " (ej. "Configuración"). null o '' = solo año
 * - $footer_class (string): clases del <footer>, por defecto "footer"
 */
if (!isset($footer_subtitulo)) {
    $footer_subtitulo = null;
}
$footer_class = isset($footer_class) ? trim((string) $footer_class) : 'footer';
?>
<footer class="<?= htmlspecialchars($footer_class, ENT_QUOTES, 'UTF-8') ?>">
    <img src="img/logo.png" alt="DenTera" class="footer-logo">
    <p>DenTera &copy; <?= date('Y') ?><?php
        if ($footer_subtitulo !== null && $footer_subtitulo !== '') {
            echo ' — ' . htmlspecialchars((string) $footer_subtitulo, ENT_QUOTES, 'UTF-8');
        }
    ?></p>
</footer>
