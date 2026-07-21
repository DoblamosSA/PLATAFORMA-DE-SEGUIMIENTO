#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Despliegue de "Projects" (PLATAFORMA-DE-SEGUIMIENTO) en el servidor Docker.

Ejecutar desde cualquier maquina CON ACCESO al servidor (misma red/VPN):

    pip install paramiko
    python deploy/deploy.py            # usa el puerto 8090
    python deploy/deploy.py 8091       # o el puerto que indiques

Que hace, en orden:
  1. Conecta por SSH y verifica que Docker este disponible.
  2. Valida que el puerto elegido este LIBRE en el host (ss -tln); si esta
     ocupado, muestra los puertos en uso y aborta sin tocar nada.
  3. Empaqueta el proyecto local (sin vendor/node_modules/.git/BD local).
  4. Lo sube por SFTP y lo extrae en /home/<user>/projects-app.
  5. Construye la imagen y levanta SOLO el servicio "projects_app"
     (docker compose -p projects). No detiene ni modifica ningun otro
     contenedor del servidor.
  6. Verifica que el contenedor quedo arriba y que responde HTTP.
"""

import os
import sys
import tarfile
import tempfile
import time

# ----------------------------------------------------------------------
# Configuracion. La contrasena NO va en el codigo: se toma de la variable
# de entorno DEPLOY_SSH_PASS o se pide por teclado al ejecutar.
# ----------------------------------------------------------------------
HOST = os.environ.get("DEPLOY_SSH_HOST", "148.222.28.92")
PORT_SSH = int(os.environ.get("DEPLOY_SSH_PORT", "59422"))
USER = os.environ.get("DEPLOY_SSH_USER", "admindoblamos")
PASS = os.environ.get("DEPLOY_SSH_PASS", "")

PUERTO_APP = int(sys.argv[1]) if len(sys.argv) > 1 else 8090
DIR_REMOTO = f"/home/{USER}/projects-app"

# Raiz del proyecto = carpeta padre de este archivo (deploy/..)
RAIZ = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), ".."))

EXCLUIR = (
    "node_modules", "vendor", ".git", "public/hot", "public/storage",
    "storage/logs", "storage/framework", ".env", ".phpunit.result.cache",
)


def excluido(ruta_rel: str) -> bool:
    ruta_rel = ruta_rel.replace("\\", "/")
    if ruta_rel.startswith("database/") and ruta_rel.endswith(".sqlite"):
        return True
    return any(ruta_rel == e or ruta_rel.startswith(e + "/") for e in EXCLUIR)


def conectar():
    try:
        import paramiko
    except ImportError:
        print("Falta la libreria paramiko. Instalala con:  pip install paramiko")
        sys.exit(1)

    global PASS
    if not PASS:
        import getpass
        PASS = getpass.getpass(f"Contrasena SSH de {USER}@{HOST}: ")

    print(f"==> Conectando a {HOST}:{PORT_SSH} como {USER}...")
    cliente = paramiko.SSHClient()
    cliente.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    cliente.connect(HOST, port=PORT_SSH, username=USER, password=PASS, timeout=25)
    return cliente


def ejecutar(cliente, comando: str, mostrar: bool = True, timeout: int = 1800) -> tuple[int, str]:
    """Ejecuta un comando remoto transmitiendo la salida en vivo."""
    stdin, stdout, stderr = cliente.exec_command(comando, timeout=timeout, get_pty=True)
    salida = []
    for linea in iter(stdout.readline, ""):
        salida.append(linea)
        if mostrar:
            print("    " + linea.rstrip())
    codigo = stdout.channel.recv_exit_status()
    return codigo, "".join(salida)


def main():
    # .env.production esta fuera de git (contiene claves): debe existir
    # en la copia local desde la que se despliega.
    if not os.path.isfile(os.path.join(RAIZ, "deploy", ".env.production")):
        print("ERROR: falta deploy/.env.production (no se versiona en git).")
        print("Copialo desde la maquina de desarrollo o crealo a partir de la")
        print("plantilla del README de deploy antes de ejecutar este script.")
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

    # --- 2. Validar puerto libre --------------------------------------
    print(f"==> Validando que el puerto {PUERTO_APP} este libre en el host...")
    _, salida = ejecutar(cliente, "ss -tln | awk 'NR>1 {print $4}' | sed 's/.*://' | sort -un", mostrar=False)
    ocupados = {int(p) for p in salida.split() if p.strip().isdigit()}
    if PUERTO_APP in ocupados:
        print(f"ERROR: el puerto {PUERTO_APP} YA esta en uso. Puertos ocupados: {sorted(ocupados)}")
        libres = [p for p in range(8090, 8120) if p not in ocupados]
        print(f"Sugerencias libres: {libres[:5]} -> python deploy/deploy.py {libres[0]}")
        sys.exit(1)
    print(f"    Puerto {PUERTO_APP} libre. (Ocupados en el host: {sorted(ocupados)})")

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

    # --- 4. Subir y extraer -------------------------------------------
    print("==> Subiendo al servidor por SFTP...")
    sftp = cliente.open_sftp()
    ejecutar(cliente, f"mkdir -p {DIR_REMOTO}", mostrar=False)
    sftp.put(tmp.name, f"{DIR_REMOTO}/proyecto.tgz")
    sftp.close()
    os.unlink(tmp.name)
    ejecutar(cliente, f"cd {DIR_REMOTO} && tar xzf proyecto.tgz && rm proyecto.tgz", mostrar=False)
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
    _, estado = ejecutar(cliente, "docker ps --filter name=projects_app --format '{{.Names}} | {{.Status}} | {{.Ports}}'")
    if "projects_app" not in estado:
        print("ERROR: el contenedor no aparece arriba. Logs:")
        ejecutar(cliente, "docker logs --tail 50 projects_app")
        sys.exit(1)

    codigo, http = ejecutar(cliente, f"curl -s -o /dev/null -w '%{{http_code}}' http://127.0.0.1:{PUERTO_APP}/login", mostrar=False)
    print(f"    Respuesta HTTP del login: {http.strip()}")

    # Confirmar que el resto de contenedores siguen igual
    _, otros = ejecutar(cliente, "docker ps --format '{{.Names}}: {{.Status}}' | grep -v projects_app", mostrar=False)
    print("    Otros sistemas (sin tocar):")
    for linea in otros.strip().splitlines():
        print("      " + linea.strip())

    cliente.close()
    print()
    print("=" * 60)
    print(f"  DESPLIEGUE COMPLETO: http://{HOST}:{PUERTO_APP}")
    print("  La BD arranca vacia: ejecuta el seeder si quieres datos")
    print(f"  demo:  ssh -p {PORT_SSH} {USER}@{HOST} \\")
    print("         \"docker exec projects_app php artisan db:seed --force\"")
    print("  Nota: PWA y notificaciones push requieren HTTPS; por IP:puerto")
    print("  la app funciona pero sin push. Para push usa un subdominio")
    print("  detras del traefik existente.")
    print("=" * 60)


if __name__ == "__main__":
    main()
