#!/bin/sh
# ------------------------------------------------------------------
# Despliega "Projects" en el servidor Docker SIN tocar otros sistemas.
#   uso: sh deploy/deploy.sh [puerto_host]
#
# 1. Valida por SSH que el puerto elegido este libre (ss + docker ps).
# 2. Empaqueta el proyecto (sin vendor/node_modules) y lo sube.
# 3. Construye la imagen y levanta SOLO el servicio "projects".
# ------------------------------------------------------------------
set -e

HOST=148.224.28.92
PORT_SSH=59422
USER=admindoblamos
DIR_REMOTO=/home/$USER/projects-app
PUERTO_HOST=${1:-8090}

SSH="ssh -p $PORT_SSH $USER@$HOST"

echo "==> Validando que el puerto $PUERTO_HOST este libre en el servidor..."
if $SSH "ss -tln | awk '{print \$4}' | grep -q ':$PUERTO_HOST\$'"; then
    echo "ERROR: el puerto $PUERTO_HOST ya esta en uso en el host. Elige otro."
    $SSH "ss -tln | awk 'NR>1 {print \$4}' | sed 's/.*://' | sort -un | tr '\n' ' '"
    exit 1
fi
echo "    Puerto $PUERTO_HOST libre."

echo "==> Empaquetando proyecto..."
tar czf /tmp/projects-app.tgz \
    --exclude=./node_modules --exclude=./vendor --exclude=./.git \
    --exclude=./storage/logs --exclude=./storage/framework \
    --exclude=./database/database.sqlite --exclude=./public/hot .

echo "==> Subiendo al servidor..."
$SSH "mkdir -p $DIR_REMOTO"
scp -P $PORT_SSH /tmp/projects-app.tgz $USER@$HOST:$DIR_REMOTO/
$SSH "cd $DIR_REMOTO && tar xzf projects-app.tgz && rm projects-app.tgz"

echo "==> Construyendo y levantando (solo el servicio projects)..."
$SSH "cd $DIR_REMOTO/deploy && cp .env.production .env.production.local 2>/dev/null; \
      echo 'PUERTO_HOST=$PUERTO_HOST' > .env.deploy && \
      docker compose --env-file .env.deploy up -d --build projects"

echo "==> Verificando..."
$SSH "docker ps --filter name=projects_app --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'"
$SSH "sleep 3 && curl -s -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:$PUERTO_HOST/login"

echo "Listo: http://$HOST:$PUERTO_HOST"
