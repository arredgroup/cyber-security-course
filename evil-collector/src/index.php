<?php
// ===========================================================================
//  PANEL DEL ATACANTE  —  http://localhost:5000/
//  Muestra TODAS las cookies robadas que llegaron a steal.php.
//  Esta es "la otra base de datos del lab" donde terminan las sesiones robadas.
// ===========================================================================
require __DIR__ . '/db.php';
$db = evil_db();

if (isset($_GET['reset'])) {
    $db->exec("DELETE FROM capturas;");
    header('Location: /');
    exit;
}

$capturas = $db->query("SELECT * FROM capturas ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Si el atacante logró robar la cookie del ADMIN (SESION_ID=1001), se gana la flag.
$robo_admin = false;
foreach ($capturas as $c) {
    if (strpos($c['cookie'], 'SESION_ID=1001') !== false) { $robo_admin = true; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>😈 C2 Panel — Cookies Robadas</title>
  <meta http-equiv="refresh" content="5">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-black text-green-400 p-6 font-mono">
  <div class="max-w-6xl mx-auto">
    <header class="border border-green-600 rounded-xl p-5 mb-6 bg-green-950/20">
      <h1 class="text-2xl font-bold"><i class="fa-solid fa-skull-crossbones mr-2"></i> Servidor del Atacante — Panel de Mando (C2)</h1>
      <p class="text-green-600 text-sm mt-1">Receptor de exfiltración XSS · DB propia: /tmp/robos.sqlite · se refresca cada 5s</p>
      <p class="text-xs text-green-700 mt-2">Esta máquina NO es la intranet. Simula el servidor externo donde el atacante recolecta las sesiones robadas.</p>
    </header>

    <?php if ($robo_admin): ?>
      <div class="border-2 border-red-500 bg-red-950/40 text-red-300 p-4 rounded-xl mb-6 font-bold">
        🏴 COOKIE DE ADMINISTRADOR CAPTURADA (SESION_ID=1001) — FLAG{XSS_COOKIE_ADMIN_EXFILTRADA}
        <div class="text-xs text-red-400 mt-1 font-normal">Con esta cookie el atacante puede secuestrar la sesión del admin sin su contraseña (session hijacking).</div>
      </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-3">
      <span class="text-green-500 text-sm"><?php echo count($capturas); ?> cookie(s) capturada(s)</span>
      <a href="?reset=1" class="text-xs border border-green-700 px-3 py-1 rounded hover:bg-green-900">Vaciar capturas</a>
    </div>

    <?php if (!$capturas): ?>
      <div class="border border-green-800 rounded-xl p-8 text-center text-green-700">
        A la espera de víctimas… Inyecta el XSS en el muro de la intranet y espera a que el admin lo visite.
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($capturas as $c): ?>
          <div class="border border-green-800 rounded-xl p-4 bg-green-950/10">
            <div class="text-xs text-green-600 mb-1">#<?php echo $c['id']; ?> · <?php echo htmlspecialchars($c['fecha']); ?> · desde <?php echo htmlspecialchars($c['ip_victima']); ?> · origen <?php echo htmlspecialchars($c['origen']); ?></div>
            <div class="text-green-300 break-all text-sm"><i class="fa-solid fa-cookie-bite mr-1"></i> <?php echo htmlspecialchars($c['cookie']); ?></div>
            <div class="text-xs text-green-800 mt-1 break-all"><?php echo htmlspecialchars($c['user_agent']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
