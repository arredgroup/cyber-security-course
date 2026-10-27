# ===========================================================================
#  install-sysmon.ps1  —  Instala Sysmon con una configuración base en el
#  Windows de PRUEBA, y activa el Script Block Logging de PowerShell (4104).
#  Ejecutar en PowerShell COMO ADMINISTRADOR.
#     powershell -ExecutionPolicy Bypass -File .\install-sysmon.ps1
# ===========================================================================
$ErrorActionPreference = "Stop"
$tmp = $env:TEMP

Write-Host "==> Descargando Sysmon (Sysinternals)..."
Invoke-WebRequest -Uri "https://download.sysinternals.com/files/Sysmon.zip" -OutFile "$tmp\Sysmon.zip"
Expand-Archive -Path "$tmp\Sysmon.zip" -DestinationPath "$tmp\Sysmon" -Force

Write-Host "==> Descargando configuración base (SwiftOnSecurity)..."
# Config de referencia ampliamente usada. Puedes cambiarla por la de Olaf Hartong.
Invoke-WebRequest -Uri "https://raw.githubusercontent.com/SwiftOnSecurity/sysmon-config/master/sysmonconfig-export.xml" -OutFile "$tmp\Sysmon\sysmonconfig.xml"

Write-Host "==> Instalando Sysmon..."
& "$tmp\Sysmon\Sysmon64.exe" -accepteula -i "$tmp\Sysmon\sysmonconfig.xml"

Write-Host "==> Activando Script Block Logging de PowerShell (Event ID 4104)..."
$psLog = "HKLM:\SOFTWARE\Policies\Microsoft\Windows\PowerShell\ScriptBlockLogging"
New-Item -Path $psLog -Force | Out-Null
Set-ItemProperty -Path $psLog -Name EnableScriptBlockLogging -Value 1

Write-Host "==> Activando auditoría de Logon y Creación de procesos (4624/4625/4688)..."
auditpol /set /subcategory:"Logon" /success:enable /failure:enable | Out-Null
auditpol /set /subcategory:"Process Creation" /success:enable /failure:enable | Out-Null
# Incluir la línea de comandos en el evento 4688
reg add "HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System\Audit" /v ProcessCreationIncludeCmdLine_Enabled /t REG_DWORD /d 1 /f | Out-Null

Write-Host "==> Listo. Verifica el canal 'Microsoft-Windows-Sysmon/Operational' en el Visor de eventos."
