# ===========================================================================
#  setup-wazuh.ps1  —  Wazuh manager single-node en WINDOWS con Docker Desktop
#  (backend WSL2), usando las imágenes OFICIALES de wazuh/wazuh-docker.
#
#  El manager de Wazuh es Linux: en Windows NO se instala nativo, se ejecuta
#  dentro de Docker Desktop (WSL2), el mismo que ya usas para el lab vulnerable.
#  Los PC Windows de prueba solo instalan el AGENTE (.msi) y apuntan aquí.
#
#  Requisitos: Docker Desktop con WSL2 y >= 4 GB asignados
#  (Settings > Resources). Git instalado.
#  Uso (PowerShell como administrador):
#     powershell -ExecutionPolicy Bypass -File .\setup-wazuh.ps1
#     (opcional)  $env:WAZUH_VERSION="v4.9.2"; .\setup-wazuh.ps1
# ===========================================================================
$ErrorActionPreference = "Stop"

$WazuhVersion = if ($env:WAZUH_VERSION) { $env:WAZUH_VERSION } else { "v4.9.2" }
$Src = "wazuh-docker-src"

Write-Host "==> Wazuh single-node ($WazuhVersion) en Docker Desktop (WSL2)"

# 0) Docker disponible
try { docker version | Out-Null } catch {
  throw "Docker no responde. Abre Docker Desktop y espera a que el motor esté 'Running'."
}

# 1) Requisito del indexer: vm.max_map_count dentro de la VM WSL2 de Docker Desktop.
#    Es efímero (se reinicia con Docker); si el indexer no arranca, vuelve a ejecutarlo.
Write-Host "==> Ajustando vm.max_map_count=262144 en la VM de Docker Desktop (WSL2)..."
try {
  wsl -d docker-desktop sh -c "sysctl -w vm.max_map_count=262144" | Out-Null
} catch {
  Write-Warning "No se pudo ajustar vm.max_map_count automáticamente."
  Write-Warning "Si el indexer no arranca, usa backend WSL2 en Docker Desktop y reintenta,"
  Write-Warning "o agrega 'vm.max_map_count=262144' vía %USERPROFILE%\.wslconfig (kernelCommandLine)."
}

# 2) Clonar el repositorio oficial (una sola vez)
if (-not (Test-Path $Src)) {
  Write-Host "==> Clonando wazuh-docker $WazuhVersion"
  git clone https://github.com/wazuh/wazuh-docker.git -b $WazuhVersion $Src
}
Set-Location (Join-Path $Src "single-node")

# 3) Generar los certificados TLS del indexer (obligatorio)
Write-Host "==> Generando certificados del indexer"
docker compose -f generate-indexer-certs.yml run --rm generator

# 4) Levantar el stack
Write-Host "==> Levantando Wazuh (manager + indexer + dashboard)"
docker compose up -d

Write-Host ""
Write-Host "===================================================================="
Write-Host " Wazuh está levantando. Dale 1-3 minutos al indexer."
Write-Host " Dashboard:  https://localhost   (acepta el certificado autofirmado)"
Write-Host " Usuario:    admin"
Write-Host " Password:   SecretPassword      <-- CÁMBIALA"
Write-Host " Puertos para los agentes: 1514/tcp (eventos) y 1515/tcp (registro)"
Write-Host " IP de este equipo (la usarán los agentes Windows):  ejecuta  ipconfig"
Write-Host "===================================================================="
