<?php
// Solo accesible desde localhost (127.0.0.1) para comprobar SSRF (Track 11 - A10)
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    die("Acceso prohibido desde la red externa. Solo permitido desde 127.0.0.1");
}
echo "PANEL INTERNO LOCALHOST — FLAG{A10_SSRF_ACCESO_INTERNO_EXITOSO}";
?>
