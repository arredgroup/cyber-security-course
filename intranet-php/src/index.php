<?php
// Configuración de sesión intencionalmente débil para auditoría (Track 8 - A07)
session_start();
if (!isset($_COOKIE['SESION_ID'])) {
    setcookie('SESION_ID', '1002', 0, '/'); // Correlativo predecible (1001 es Admin)
}
if (!isset($_COOKIE['datos_usuario'])) {
    // Base64 de: {"usuario":"empleado","rol":"lector"} (Track 9 - A08)
    setcookie('datos_usuario', base64_encode('{"usuario":"empleado","rol":"lector"}'), 0, '/');
}

// Inicializar Base de Datos SQLite local para los laboratorios
$db = new PDO('sqlite:/tmp/intranet.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS usuarios (id INTEGER PRIMARY KEY, user TEXT, pass_md5 TEXT, rol TEXT, sueldo TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS boletas (id INTEGER PRIMARY KEY, empleado TEXT, cargo TEXT, monto TEXT, flag TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS comentarios (id INTEGER PRIMARY KEY AUTOINCREMENT, autor TEXT, mensaje TEXT);");

// Sembrar datos iniciales si está vacía
$count = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
if ($count == 0) {
    // Hashes MD5 reales: admin -> 21232f297a57a5a743894a0e4a801fc3 | 123456 -> e10adc3949ba59abbe56e057f20f883e
    $db->exec("INSERT INTO usuarios VALUES (1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'administrador', '$4.500.000');");
    $db->exec("INSERT INTO usuarios VALUES (2, 'jperez', 'e10adc3949ba59abbe56e057f20f883e', 'empleado', '$1.200.000');");
    
    $db->exec("INSERT INTO boletas VALUES (101, 'Juan Pérez', 'Analista', '$1.200.000', 'Registro normal');");
    $db->exec("INSERT INTO boletas VALUES (102, 'María Soto', 'Soporte', '$1.350.000', 'Registro normal');");
    $db->exec("INSERT INTO boletas VALUES (103, 'Gerencia General', 'Directorio', '$6.800.000', 'FLAG{A01_IDOR_LIQUIDACION_DESCUBIERTA}');");
}

if (!isset($_SESSION['carrito_total'])) {
    $_SESSION['carrito_total'] = 50000;
}

$mensaje_sistema = "";

// --- PROCESAMIENTO DE FORMULARIOS ---

// Track 1: Formulario con campos ocultos y readonly
if (isset($_POST['accion']) && $_POST['accion'] === 'reembolso') {
    $monto = $_POST['monto'];
    $bono = $_POST['bono_bloqueado'];
    $aprobado = $_POST['estado_aprobacion'];
    if ($aprobado === 'si' || $bono > 0) {
        $mensaje_sistema = "✅ Reembolso APROBADO por $" . htmlspecialchars($monto) . " con bono de " . htmlspecialchars($bono) . "% — FLAG{TRACK1_FORMULARIO_MANIPULADO}";
    } else {
        $mensaje_sistema = "Solicitud recibida en estado: PENDIENTE (Monto: $" . htmlspecialchars($monto) . ")";
    }
}

// Track 4 (A03): Comentarios con XSS Almacenado
if (isset($_POST['accion']) && $_POST['accion'] === 'comentar') {
    $stmt = $db->prepare("INSERT INTO comentarios (autor, mensaje) VALUES (?, ?)");
    $stmt->execute([$_POST['autor'], $_POST['mensaje']]);
}

// Track 5 (A04): Lógica de Negocio (Cantidades negativas y cupones repetibles)
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar_item') {
    $precio = intval($_POST['precio_oculto']);
    $cantidad = intval($_POST['cantidad']);
    $_SESSION['carrito_total'] += ($precio * $cantidad);
    if ($_SESSION['carrito_total'] <= 0) {
        $mensaje_sistema = "🚨 Total en $0 o negativo alcanzado — FLAG{A04_FALLA_LOGICA_NEGOCIO}";
    }
}

// Track 9 (A08): Subida de archivos sin validar extensión real
if (isset($_FILES['comprobante'])) {
    $nombre = $_FILES['comprobante']['name'];
    $tipo = $_FILES['comprobante']['type'];
    // Solo valida Content-Type manipulable en Burp Suite
    if ($tipo === 'image/jpeg' || $tipo === 'image/png') {
        move_uploaded_file($_FILES['comprobante']['tmp_name'], __DIR__ . "/uploads/" . $nombre);
        $mensaje_sistema = "✅ Archivo subido en: <a class='underline font-bold' href='/uploads/$nombre'>/uploads/$nombre</a> — FLAG{A08_SUBIDA_SIN_INTEGRIDAD}";
    } else {
        $mensaje_sistema = "❌ Error: Solo se aceptan cabeceras Content-Type: image/jpeg";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Intranet Corporativa - Laboratorio de Auditoría OWASP</title>
  <!-- Huella intencional para detección de Componentes Vulnerables (Track 7 - A06) -->
  <meta name="generator" content="WordPress 5.2.1" />
  <script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-6">
  <div class="max-w-6xl mx-auto">
    <header class="bg-slate-900 text-white p-6 rounded-xl mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold">Portal Interno Corporativo (Laboratorio OWASP Top 10)</h1>
        <p class="text-slate-400 text-sm">Entorno de Auditoría con Burp Suite, OWASP ZAP y Nmap</p>
      </div>
      <div class="text-right text-sm">
        <span class="bg-blue-600 px-3 py-1 rounded">Cookie SESION_ID: <?php echo htmlspecialchars($_COOKIE['SESION_ID'] ?? '1002'); ?></span>
        <a href="/salir.php" class="ml-3 bg-red-600 px-3 py-1 rounded hover:bg-red-700">Cerrar Sesión</a>
      </div>
    </header>

    <?php if ($mensaje_sistema): ?>
      <div class="bg-amber-100 border-l-4 border-amber-600 text-amber-900 p-4 rounded mb-6 font-semibold">
        <?php echo $mensaje_sistema; ?>
      </div>
    <?php endif; ?>

    <?php if (($_COOKIE['SESION_ID'] ?? '') === '1001'): ?>
      <div class="bg-emerald-100 border-l-4 border-emerald-600 text-emerald-900 p-4 rounded mb-6 font-bold">
        🔓 SESIÓN DE ADMINISTRADOR SECUESTRADA (SESION_ID=1001) — FLAG{A07_SECUESTRO_DE_SESION_COOKIE}
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-6">

      <!-- TRACK 1: FORMULARIO CON CAMPOS OCULTOS Y READONLY -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">1. Solicitud de Reembolso (Track 1: Burp Proxy)</h2>
        <form method="POST" class="space-y-3 text-sm">
          <input type="hidden" name="accion" value="reembolso">
          <input type="hidden" name="estado_aprobacion" value="no">
          <div>
            <label class="block font-semibold">Monto a reembolsar ($):</label>
            <input type="number" name="monto" value="15000" class="border p-2 rounded w-full">
          </div>
          <div>
            <label class="block font-semibold">Bono adicional (%) [Bloqueado en HTML]:</label>
            <input type="number" name="bono_bloqueado" value="0" readonly class="border p-2 rounded w-full bg-slate-200">
          </div>
          <button class="bg-blue-600 text-white px-4 py-2 rounded font-semibold">Enviar Solicitud</button>
        </form>
      </section>

      <!-- TRACK 2 (A01): IDOR EN LIQUIDACIONES DE SUELDO -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">2. Consulta de Liquidaciones (Track 2: A01 - IDOR)</h2>
        <p class="text-xs text-slate-500 mb-3">Prueba cambiar el parámetro <code>?id_boleta=101</code> en la URL o en Burp Suite.</p>
        <div class="mb-3">
          <a href="?id_boleta=101" class="bg-slate-800 text-white px-3 py-1.5 rounded text-sm">Ver mi boleta (#101)</a>
        </div>
        <?php
        if (isset($_GET['id_boleta'])) {
            $id_b = intval($_GET['id_boleta']);
            $boleta = $db->query("SELECT * FROM boletas WHERE id = $id_b")->fetch(PDO::FETCH_ASSOC);
            if ($boleta) {
                echo "<div class='bg-slate-100 p-3 rounded text-sm border'>";
                echo "<b>Boleta #{$boleta['id']}</b> | Empleado: {$boleta['empleado']} | Monto: {$boleta['monto']}<br>";
                echo "<span class='text-red-600 font-bold'>Nota: {$boleta['flag']}</span>";
                echo "</div>";
            }
        }
        ?>
      </section>

      <!-- TRACK 3 & 4 (A02 / A03): INYECCIÓN SQL Y HASHES MD5 -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">3. Buscador de Usuarios (Track 3 y 4: A03 SQLi & A02 MD5)</h2>
        <form method="GET" class="flex gap-2 mb-3 text-sm">
          <input type="text" name="buscar_user" placeholder="Ej: jperez o ' OR '1'='1" class="border p-2 rounded flex-1">
          <button class="bg-blue-600 text-white px-4 py-2 rounded font-semibold">Buscar</button>
        </form>
        <?php
        if (isset($_GET['buscar_user'])) {
            $q = $_GET['buscar_user'];
            // VULNERABLE A INYECCIÓN SQL INTENCIONALMENTE
            $sql = "SELECT id, user, pass_md5, rol, sueldo FROM usuarios WHERE user = '$q'";
            try {
                $res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $u) {
                    echo "<div class='text-xs bg-slate-100 p-2 mb-1 rounded border font-mono'>";
                    echo "User: {$u['user']} | Rol: {$u['rol']} | Hash MD5: {$u['pass_md5']} | Sueldo: {$u['sueldo']}";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='text-xs text-red-600 font-mono bg-red-50 p-2 rounded'>Fatal SQL Error: " . $e->getMessage() . "</div>";
            }
        }
        ?>
      </section>

      <!-- TRACK 4 (A03): COMMAND INJECTION Y XSS -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">4. Diagnóstico de Red y Muro (Track 4: Comandos & XSS)</h2>
        <form method="GET" class="flex gap-2 mb-3 text-sm">
          <input type="text" name="ping_ip" placeholder="127.0.0.1 && whoami" class="border p-2 rounded flex-1">
          <button class="bg-slate-800 text-white px-3 py-2 rounded font-semibold">Ejecutar Comando</button>
        </form>
        <?php
        if (isset($_GET['ping_ip'])) {
            echo "<pre class='bg-black text-green-400 p-2 rounded text-xs overflow-x-auto'>" . shell_exec("ping -c 1 " . $_GET['ping_ip']) . "</pre>";
        }
        ?>
        <form method="POST" class="flex gap-2 mt-3 text-sm">
          <input type="hidden" name="accion" value="comentar">
          <input type="text" name="autor" placeholder="Nombre" class="border p-2 rounded w-1/3">
          <input type="text" name="mensaje" placeholder="Aviso (vulnerable a XSS)" class="border p-2 rounded flex-1">
          <button class="bg-emerald-600 text-white px-3 py-2 rounded font-semibold">Publicar</button>
        </form>
        <div class="mt-2 text-xs space-y-1">
          <?php
          foreach ($db->query("SELECT * FROM comentarios ORDER BY id DESC LIMIT 3") as $c) {
              // Imprime sin htmlspecialchars intencionalmente para practicar XSS
              echo "<div class='border-b py-1'><b>{$c['autor']}:</b> {$c['mensaje']}</div>";
          }
          ?>
        </div>
      </section>

      <!-- TRACK 5 (A04): LÓGICA DE NEGOCIO -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">5. Carrito de Compras Interno (Track 5: A04 Lógica)</h2>
        <p class="text-sm mb-2">Total actual del carrito: <b class="text-emerald-700">$<?php echo $_SESSION['carrito_total']; ?></b></p>
        <form method="POST" class="flex gap-2 text-sm">
          <input type="hidden" name="accion" value="agregar_item">
          <input type="hidden" name="precio_oculto" value="10000">
          <input type="number" name="cantidad" value="1" class="border p-2 rounded w-24">
          <button class="bg-blue-600 text-white px-4 py-2 rounded font-semibold">Agregar Licencias ($10.000 c/u)</button>
        </form>
      </section>

      <!-- TRACK 9, 10 Y 11 (A08 / A09 / A10): SUBIDA, LOGS Y SSRF -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-blue-700 mb-2">6. Subida de Archivos y Vista Previa URL (A08 & A10 SSRF)</h2>
        <form method="POST" enctype="multipart/form-data" class="flex gap-2 mb-3 text-xs">
          <input type="file" name="comprobante" class="border p-1 rounded flex-1">
          <button class="bg-amber-600 text-white px-3 py-1.5 rounded font-semibold">Subir (Valida Content-Type)</button>
        </form>
        <form method="GET" class="flex gap-2 text-xs">
          <input type="text" name="ssrf_url" placeholder="http://127.0.0.1/server-secret.php" class="border p-2 rounded flex-1">
          <button class="bg-purple-700 text-white px-3 py-1.5 rounded font-semibold">Cargar URL (SSRF)</button>
        </form>
        <?php
        if (isset($_GET['ssrf_url'])) {
            echo "<div class='mt-2 bg-slate-900 text-emerald-300 p-2 rounded text-xs font-mono'>" . htmlspecialchars(@file_get_contents($_GET['ssrf_url'])) . "</div>";
        }
        ?>
      </section>

    </div>

    <footer class="mt-6 text-xs text-slate-500 flex justify-between">
      <span>Rutas para auditar con OWASP ZAP: <a href="/documentos/" class="underline">/documentos/</a> | <a href="/info.php" class="underline">/info.php</a></span>
      <span>Laboratorio de Capacitación OWASP Top 10</span>
    </footer>
  </div>
</body>
</html>
