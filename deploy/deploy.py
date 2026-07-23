#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Despliegue de "Projects" (PLATAFORMA-DE-SEGUIMIENTO) en el servidor Docker.

Se ejecuta de dos formas:

A) Automatica (CI): GitHub Actions lo corre en cada push a main
   (ver .github/workflows/deploy.yml). Las credenciales y el
   .env.production llegan por secrets del repositorio.

B) Manual, desde cualquier maquina CON ACCESO al servidor (red/VPN):

    pip install paramiko
    python deploy/deploy.py            # usa el puerto 8090
    python deploy/deploy.py 8091       # o el puerto que indiques

Que hace, en orden:
  1. Conecta por SSH y verifica que Docker este disponible.
  2. Valida el puerto elegido: si lo ocupa OTRO servicio aborta sin tocar
     nada; si lo ocupa el propio contenedor projects_app continua
     (re-despliegue sobre el mismo puerto).
  3. Empaqueta el proyecto local (sin vendor/node_modules/.git/BD local).
  4. Lo sube por SFTP y lo extrae en /home/<user>/projects-app limpiando
     el codigo anterior (la BD y storage/app viven en volumenes Docker,
     no se tocan).
  5. Construye la imagen y levanta SOLO el servicio "projects"
     (docker compose -p projects). No detiene ni modifica ningun otro
     contenedor del servidor.
  6. Verifica que el contenedor quedo arriba y que /login responde
     HTTP 200/302 (reintenta ~60 s mientras corren migraciones); si no
     responde, muestra los logs del contenedor y termina con error.

En CI, despues de este script corre deploy/migrate.py como paso aparte
("Validacion y ejecucion de migraciones"), que confirma explicitamente
si quedo algo pendiente y lo aplica.
"""

import os
import sys
import tarfile
import tempfile
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from _ssh import HOST, PORT_SSH, USER, conectar, ejecutar  # noqa: E402

try:
    PUERTO_APP = int(sys.argv[1]) if len(sys.argv) > 1 else int(os.environ.get("DEPLOY_APP_PORT") or "8090")
except ValueError:
    print(f"ERROR: puerto invalido: {sys.argv[1]!r}. Uso: python deploy/deploy.py [puerto]")
    sys.exit(1)

DIR_REMOTO = f"/home/{USER}/projects-app"

# Raiz del proyecto = carpeta padre de este archivo (deploy/..)
RAIZ = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), ".."))

EXCLUIR = (
    "node_modules", "vendor", ".git", "public/hot", "public/storage",
    "storage/logs", "storage/framework", "storage/app", ".env",
    ".phpunit.result.cache",
)


def excluido(ruta_rel: str) -> bool:
    ruta_rel = ruta_rel.replace("\\", "/")
    if ruta_rel.startswith("database/") and ruta_rel.endswith(".sqlite"):
        return True
    return any(ruta_rel == e or ruta_rel.startswith(e + "/") for e in EXCLUIR)


def main():
    # .env.production esta fuera de git (contiene claves). En manual debe
    # existir en la copia local; en CI el workflow lo genera desde el
    # secret ENV_PRODUCTION antes de llamar a este script.
    ruta_env = os.path.join(RAIZ, "deploy", ".env.production")
    if not os.path.isfile(ruta_env) or os.path.getsize(ruta_env) == 0:
        print("ERROR: falta deploy/.env.production (no se versiona en git).")
        print("Manual: copialo desde la maquina de desarrollo (plantilla en deploy/README.md).")
        print("CI: configura el secret ENV_PRODUCTION en GitHub.")
        sys.exit(1)

    cliente = conectar()

    # --- 1. Docker disponible -----------------------------------------
    codigo, salida = ejecutar(cliente, "docker --version", mostrar=False)
    if codigo != 0:
        print("ERROR: Docker no esta disponible para este usuario.")
        print(salida)
        sys.exit(1)
    print(f"    Docker OK ({salida.strip().splitlines()[0].strip()})")

    codigo, _ = ejecutar(cliente, "docker compose version", mostrar=False)
    if codigo == 0:
        compose = "docker compose"
    else:
        codigo, _ = ejecutar(cliente, "docker-compose --version", mostrar=False)
        if codigo != 0:
            print("ERROR: no existe 'docker compose' ni 'docker-compose' en el servidor.")
            sys.exit(1)
        compose = "docker-compose"

    # --- 2. Validar puerto --------------------------------------------
    print(f"==> Validando el puerto {PUERTO_APP} en el host...")
    _, salida = ejecutar(cliente, "ss -tln | awk 'NR>1 {print $4}' | sed 's/.*://' | sort -un", mostrar=False)
    ocupados = {int(p) for p in salida.split() if p.strip().isdigit()}

    # Puertos publicados por el propio contenedor: no son conflicto,
    # permiten re-desplegar sobre el mismo puerto.
    codigo, salida = ejecutar(cliente, "docker port projects_app 2>/dev/null", mostrar=False)
    propios = set()
    if codigo == 0:
        for linea in salida.splitlines():
            if "->" in linea:
                p = linea.rsplit(":", 1)[-1].strip()
                if p.isdigit():
                    propios.add(int(p))

    if PUERTO_APP in propios:
        print(f"    Puerto {PUERTO_APP} ocupado por projects_app: re-despliegue sobre el mismo puerto.")
    elif PUERTO_APP in ocupados:
        print(f"ERROR: el puerto {PUERTO_APP} YA esta en uso por otro servicio. Ocupados: {sorted(ocupados)}")
        libres = [p for p in range(8090, 8120) if p not in ocupados]
        if libres:
            print(f"Sugerencias libres: {libres[:5]} -> python deploy/deploy.py {libres[0]}")
        sys.exit(1)
    else:
        print(f"    Puerto {PUERTO_APP} libre.")

    # --- 3. Empaquetar proyecto local ---------------------------------
    print("==> Empaquetando el proyecto local...")
    tmp = tempfile.NamedTemporaryFile(suffix=".tgz", delete=False)
    tmp.close()
    with tarfile.open(tmp.name, "w:gz") as tar:
        for carpeta, dirs, archivos in os.walk(RAIZ):
            rel_carpeta = os.path.relpath(carpeta, RAIZ)
            if rel_carpeta == ".":
                rel_carpeta = ""
            # poda de directorios excluidos para no recorrerlos
            dirs[:] = [d for d in dirs if not excluido(os.path.join(rel_carpeta, d))]
            for a in archivos:
                rel = os.path.join(rel_carpeta, a).replace("\\", "/")
                if not excluido(rel):
                    tar.add(os.path.join(carpeta, a), arcname=rel)
    tam_mb = os.path.getsize(tmp.name) / 1024 / 1024
    print(f"    Paquete listo: {tam_mb:.1f} MB")

    # --- 4. Subir y extraer (limpiando el codigo anterior) -------------
    print("==> Subiendo al servidor por SFTP...")
    tar_remoto = f"/home/{USER}/projects-app.tgz"
    sftp = cliente.open_sftp()
    sftp.put(tmp.name, tar_remoto)
    sftp.close()
    os.unlink(tmp.name)

    # rm -rf evita arrastrar archivos que ya no existen en el repo. La BD
    # (projects_db) y storage/app (projects_storage) viven en volumenes.
    codigo, _ = ejecutar(
        cliente,
        f"rm -rf {DIR_REMOTO} && mkdir -p {DIR_REMOTO} && "
        f"tar xzf {tar_remoto} -C {DIR_REMOTO} && rm -f {tar_remoto}",
        mostrar=False,
    )
    if codigo != 0:
        print("ERROR: fallo la extraccion del paquete en el servidor.")
        sys.exit(1)
    print("    Codigo extraido en " + DIR_REMOTO)

    # --- 5. Construir y levantar (solo este servicio) ------------------
    print(f"==> Construyendo la imagen y levantando el contenedor (puerto {PUERTO_APP})...")
    print("    (la primera vez tarda varios minutos: npm ci + composer install)")
    codigo, _ = ejecutar(
        cliente,
        f"cd {DIR_REMOTO}/deploy && echo 'PUERTO_HOST={PUERTO_APP}' > .env.deploy && "
        f"{compose} -p projects --env-file .env.deploy up -d --build projects",
        timeout=3600,
    )
    if codigo != 0:
        print("ERROR: fallo la construccion o el arranque. Revisa la salida de arriba.")
        sys.exit(1)

    # --- 6. Verificar ---------------------------------------------------
    print("==> Verificando...")
    time.sleep(5)
    _, estado = ejecutar(cliente, "docker ps --filter 'name=^projects_app$' --format '{{.Names}} | {{.Status}} | {{.Ports}}'")
    if "projects_app" not in estado:
        print("ERROR: el contenedor no aparece arriba. Logs:")
        ejecutar(cliente, "docker logs --tail 80 projects_app")
        sys.exit(1)

    # El entrypoint corre migraciones y caches antes de servir: reintenta.
    url = f"http://127.0.0.1:{PUERTO_APP}/login"
    http = ""
    for _ in range(12):
        codigo, http = ejecutar(cliente, f"curl -s -o /dev/null -w '%{{http_code}}' {url}", mostrar=False)
        http = http.strip()
        if codigo == 0 and http in ("200", "302"):
            break
        time.sleep(5)
    if http not in ("200", "302"):
        print(f"ERROR: la app no responde en {url} (ultimo codigo: {http or 'sin respuesta'}). Logs:")
        ejecutar(cliente, "docker logs --tail 80 projects_app")
        sys.exit(1)
    print(f"    /login responde HTTP {http}")

    # Confirmar que el resto de contenedores siguen igual
    _, otros = ejecutar(cliente, "docker ps --format '{{.Names}}: {{.Status}}' | grep -v projects_app", mostrar=False)
    print("    Otros sistemas (sin tocar):")
    for linea in otros.strip().splitlines():
        print("      " + linea.strip())

    cliente.close()
    print()
    print("=" * 60)
    print(f"  DESPLIEGUE COMPLETO: http://{HOST}:{PUERTO_APP}")
    print("  La BD vive en el volumen projects_db: los datos se conservan")
    print("  entre despliegues. Para datos demo en un servidor nuevo:")
    print(f"  ssh -p {PORT_SSH} {USER}@{HOST} \\")
    print("      \"docker exec projects_app php artisan db:seed --force\"")
    print("  Nota: PWA y notificaciones push requieren HTTPS; por IP:puerto")
    print("  la app funciona pero sin push. Para push usa un subdominio")
    print("  detras del traefik existente.")
    print("=" * 60)


if __name__ == "__main__":
    main()
