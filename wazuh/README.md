# Lab Wazuh — Montaje y validación (Windows + SIEM)

Guía para montar un **Wazuh manager en Docker (single-node)** en el equipo del instructor y conectar uno o varios **PC Windows de prueba** como agentes, de modo de **validar en vivo** las detecciones del deck `presentacion-windows-siem.html`.

> Arquitectura: **1 manager** (este equipo, Docker) ← **N agentes** (Windows de prueba, apuntando a la IP del manager por los puertos `1514/tcp` y `1515/tcp`). El dashboard queda en `https://localhost` (puerto 443) del equipo del manager.

## Requisitos
- Docker + Docker Compose v2, con **~4 GB de RAM libres** (el indexer/Opensearch es lo pesado).
- En host **Linux**: `vm.max_map_count=262144` (el script lo intenta). En **Docker Desktop** (macOS/Windows) se maneja dentro de su VM.
- Red: los Windows de prueba deben **alcanzar la IP** del manager en `1514` y `1515`.

## 1. Levantar el manager (en este equipo)
```bash
cd wazuh
bash setup-wazuh.sh          # usa la versión por defecto; o: WAZUH_VERSION=v4.9.2 bash setup-wazuh.sh
```
Esto clona el repo oficial `wazuh/wazuh-docker`, genera los certificados y levanta `manager + indexer + dashboard`.

- Dashboard: `https://localhost` → usuario `admin`, password `SecretPassword` (**cámbiala**).
- Averigua la IP del equipo (la usarán los agentes): `ipconfig getifaddr en0` (macOS) o `ip a` (Linux).

## 2. Instalar el agente en el Windows de prueba
Opción recomendada: en el dashboard, **Agents ▸ Deploy new agent**, elige Windows, pon la IP del manager y copia el comando que genera.

Opción manual (PowerShell **como administrador** en el PC de prueba):
```powershell
Invoke-WebRequest -Uri https://packages.wazuh.com/4.x/windows/wazuh-agent-4.9.2-1.msi -OutFile $env:TEMP\wazuh-agent.msi
msiexec.exe /i $env:TEMP\wazuh-agent.msi /q WAZUH_MANAGER="IP_DEL_MANAGER" WAZUH_AGENT_NAME="pc-prueba"
NET START WazuhSvc
```

## 3. Telemetría del endpoint (Sysmon + auditoría + PowerShell)
En el PC de prueba, ejecuta el script incluido (PowerShell admin):
```powershell
powershell -ExecutionPolicy Bypass -File .\windows-agent\install-sysmon.ps1
```
Instala Sysmon con config base y activa Script Block Logging (4104), auditoría de Logon y de Creación de procesos (4688 con línea de comandos).

## 4. Reenviar los canales de eventos al manager
Edita el `ossec.conf` del agente (`C:\Program Files (x86)\ossec-agent\ossec.conf`) y pega, dentro de `<ossec_config>`, los bloques de `windows-agent/ossec-eventchannel.xml`. Luego:
```powershell
Restart-Service WazuhSvc
```

## 5. Reglas del curso en el manager
Copia `manager/local_rules.xml` al manager y reinícialo:
```bash
docker cp manager/local_rules.xml wazuh.manager:/var/ossec/etc/rules/local_rules.xml
docker exec wazuh.manager /var/ossec/bin/wazuh-control restart
```

## 6. Validación — reproduce cada detección y confírmala en el dashboard
En el PC de prueba genera los eventos y verifica la alerta en **Threat Hunting / Security events**:

| Prueba en el Windows de prueba | Evento | Alerta esperada (regla) |
| :--- | :--- | :--- |
| Varios `runas` con clave incorrecta (fuerza bruta) | 4625 x N | Fuerza bruta (100100 · T1110) |
| `net user soporte2 P@ss! /add` + `net localgroup Administradores soporte2 /add` | 4720 / 4732 | Cuenta creada y alta a Administradores (100101/100102) |
| `wevtutil cl Security` (¡solo en VM de prueba!) | 1102 | Borrado del log (100103 · T1070.001) |
| Acceso a `lsass.exe` (Sysmon) | Sysmon 10 | Volcado de credenciales (100104 · T1003.001) |
| `netsh advfirewall firewall add rule ... dir=in` | 2004 | Nueva regla de firewall (100105) |
| Instalar un MSI | 1033 | Instalación de software (100106) |

> Si una alerta no aparece: revisa que el agente esté **Active** en el dashboard, que el canal esté en el `ossec.conf`, y ajusta el nombre de campo/nivel de la regla según tu versión de Wazuh (los decoders de Windows pueden variar entre versiones).

## Archivos de esta carpeta
- `setup-wazuh.sh` — levanta el manager single-node (imágenes oficiales).
- `manager/local_rules.xml` — reglas del curso para el manager.
- `windows-agent/ossec-eventchannel.xml` — bloques `<localfile>` para el `ossec.conf` del agente.
- `windows-agent/install-sysmon.ps1` — Sysmon + auditoría + ScriptBlock Logging en el PC de prueba.
