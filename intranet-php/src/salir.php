<?php
// Falla A07: Redirige al inicio pero olvida ejecutar session_destroy()
header("Location: index.php");
exit;
?>
