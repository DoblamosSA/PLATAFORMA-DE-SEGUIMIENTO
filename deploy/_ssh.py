#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Conexion SSH compartida entre los scripts de deploy/ (deploy.py, migrate.py)."""

import os
import sys

HOST = os.environ.get("DEPLOY_SSH_HOST") or "148.224.28.92"
PORT_SSH = int(os.environ.get("DEPLOY_SSH_PORT") or "59422")
USER = os.environ.get("DEPLOY_SSH_USER") or "admindoblamos"
PASS = os.environ.get("DEPLOY_SSH_PASS") or ""
KEY = os.environ.get("DEPLOY_SSH_KEY") or ""  # llave privada (contenido PEM), opcional


def conectar():
    try:
        import paramiko
    except ImportError:
        print("Falta la libreria paramiko. Instalala con:  pip install paramiko")
        sys.exit(1)

    global PASS
    pkey = None
    if KEY:
        import io
        for tipo in (paramiko.Ed25519Key, paramiko.RSAKey, paramiko.ECDSAKey):
            try:
                pkey = tipo.from_private_key(io.StringIO(KEY))
                break
            except paramiko.SSHException:
                continue
        if pkey is None:
            print("ERROR: DEPLOY_SSH_KEY no es una llave privada valida (Ed25519/RSA/ECDSA).")
            sys.exit(1)
    elif not PASS:
        if not sys.stdin.isatty():
            print("ERROR: define DEPLOY_SSH_PASS (o DEPLOY_SSH_KEY) en el entorno.")
            print("No hay terminal interactiva para pedir la contrasena.")
            sys.exit(1)
        import getpass
        PASS = getpass.getpass(f"Contrasena SSH de {USER}@{HOST}: ")

    print(f"==> Conectando a {HOST}:{PORT_SSH} como {USER}...")
    cliente = paramiko.SSHClient()
    cliente.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    cliente.connect(HOST, port=PORT_SSH, username=USER,
                    password=PASS or None, pkey=pkey, timeout=25)
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
