#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Valida y ejecuta migraciones pendientes de Laravel en el contenedor ya
desplegado (projects_app).

Corre como paso separado del pipeline, DESPUES de "Desplegar" (ver
.github/workflows/deploy.yml), para que el resultado -si habia
migraciones pendientes y si se aplicaron- quede visible como su propio
paso ("Validacion y ejecucion de migraciones") en el log de GitHub
Actions.

El entrypoint del contenedor (deploy/entrypoint.sh) ya corre
'php artisan migrate --force' al arrancar, asi que en el caso normal
aqui no habra nada pendiente. Este paso es la confirmacion explicita de
eso, y una red de seguridad por si alguna migracion quedara pendiente.

Uso manual (con acceso SSH al servidor):
    pip install paramiko
    python deploy/migrate.py
"""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from _ssh import conectar, ejecutar  # noqa: E402

CONTENEDOR = "projects_app"


def main():
    cliente = conectar()

    print("==> Consultando estado de migraciones...")
    codigo, estado = ejecutar(
        cliente, f"docker exec {CONTENEDOR} php artisan migrate:status", mostrar=False
    )
    if codigo != 0:
        print("ERROR: no se pudo consultar el estado de migraciones.")
        print(estado)
        cliente.close()
        sys.exit(1)

    pendientes = [linea for linea in estado.splitlines() if "Pending" in linea]

    if not pendientes:
        print("==> No hay migraciones pendientes, continuando.")
        cliente.close()
        return

    print(f"==> {len(pendientes)} migracion(es) pendiente(s):")
    for linea in pendientes:
        print("    " + linea.strip())

    print("==> Aplicando migraciones pendientes...")
    codigo, _ = ejecutar(cliente, f"docker exec {CONTENEDOR} php artisan migrate --force")
    if codigo != 0:
        print("ERROR: fallo la ejecucion de las migraciones.")
        cliente.close()
        sys.exit(1)

    print("==> Migraciones aplicadas correctamente.")
    cliente.close()


if __name__ == "__main__":
    main()
