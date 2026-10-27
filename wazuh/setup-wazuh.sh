#!/usr/bin/env bash
# ===========================================================================
#  Monta un Wazuh manager + indexer + dashboard (single-node) con Docker,
#  usando las imágenes OFICIALES de wazuh/wazuh-docker (soportadas).
#  Pensado para el equipo del INSTRUCTOR: los PC de prueba (Windows) instalan
#  solo el agente y apuntan a la IP de este equipo.
#
#  Requisitos: Docker + Docker Compose v2, ~4 GB de RAM libres para el indexer.
#  Uso:   bash setup-wazuh.sh          (usa la versión por defecto)
#         WAZUH_VERSION=v4.9.2 bash setup-wazuh.sh
# ===========================================================================
set -euo pipefail

# Verifica en https://github.com/wazuh/wazuh-docker/releases la última estable.
WAZUH_VERSION="${WAZUH_VERSION:-v4.9.2}"
SRC_DIR="wazuh-docker-src"

echo "==> Wazuh single-node (versión ${WAZUH_VERSION})"

# 1) Requisito del indexer (Opensearch). En un host Linux hay que subir
#    vm.max_map_count; en Docker Desktop (macOS/Windows) se aplica dentro de su VM.
if [ "$(uname)" = "Linux" ]; then
  echo "==> Ajustando vm.max_map_count=262144 (requiere sudo)"
  sudo sysctl -w vm.max_map_count=262144 || echo "   (no se pudo; ajústalo manualmente si el indexer no arranca)"
fi

# 2) Clonar el repositorio oficial (una sola vez)
if [ ! -d "${SRC_DIR}" ]; then
  echo "==> Clonando wazuh-docker ${WAZUH_VERSION}"
  git clone https://github.com/wazuh/wazuh-docker.git -b "${WAZUH_VERSION}" "${SRC_DIR}"
fi

cd "${SRC_DIR}/single-node"

# 3) Generar los certificados TLS del indexer (obligatorio)
echo "==> Generando certificados del indexer"
docker compose -f generate-indexer-certs.yml run --rm generator

# 4) Levantar el stack
echo "==> Levantando Wazuh (manager + indexer + dashboard)"
docker compose up -d

cat <<'EOF'

====================================================================
 Wazuh está levantando. Dale 1-3 minutos al indexer.
 Dashboard:  https://localhost         (acepta el cert autofirmado)
 Usuario:    admin
 Password:   SecretPassword            <-- CÁMBIALA en producción
 Puertos del manager para los agentes:  1514/tcp (eventos) y 1515/tcp (registro)
 Averigua la IP de este equipo (la usarán los agentes Windows):
    - Linux/macOS:  ip a   /   ipconfig getifaddr en0
====================================================================
EOF
