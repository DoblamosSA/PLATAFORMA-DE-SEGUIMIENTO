# Despliegue de Projects

La app se despliega como **un solo contenedor Docker** (nginx + php-fpm +
supervisord, BD SQLite en volumen) en el servidor, sin tocar los demás
sistemas que conviven ahí. El script [deploy.py](deploy.py) empaqueta el
código, lo sube por SSH/SFTP, reconstruye la imagen y verifica que la app
responda.

## Despliegue automático (GitHub Actions)

Cada push a `main` dispara [.github/workflows/deploy.yml](../.github/workflows/deploy.yml),
que ejecuta `deploy.py` desde un runner de GitHub. También se puede lanzar
a mano desde la pestaña **Actions → Desplegar a produccion → Run workflow**.

> Requisito de red: el puerto SSH del servidor debe aceptar conexiones
> desde internet (los runners de GitHub no están en la VPN).

### Secrets a configurar (una sola vez)

En GitHub: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Contenido |
|---|---|
| `DEPLOY_SSH_PASS` | Contraseña SSH del usuario de despliegue. Alternativa más segura: `DEPLOY_SSH_KEY` con el contenido de una llave privada (Ed25519/RSA) cuya pública esté en `~/.ssh/authorized_keys` del servidor. |
| `ENV_PRODUCTION` | El contenido **completo** del archivo `deploy/.env.production` (cópialo y pégalo tal cual). |
| `DEPLOY_SSH_HOST` | (Opcional) IP del servidor si cambia respecto al valor por defecto. |
| `DEPLOY_SSH_PORT` | (Opcional) Puerto SSH si cambia. |
| `DEPLOY_SSH_USER` | (Opcional) Usuario SSH si cambia. |

Variable opcional (**Variables**, no secret): `DEPLOY_APP_PORT` para usar un
puerto host distinto de 8090.

Cuando cambies algo en `.env.production` (por ejemplo una clave), actualiza
el secret `ENV_PRODUCTION`: el workflow reconstruye el archivo en cada
despliegue a partir del secret.

## Despliegue manual

Desde cualquier máquina con acceso al servidor (red/VPN) y con el archivo
`deploy/.env.production` presente:

```bash
pip install paramiko
python deploy/deploy.py            # puerto 8090
python deploy/deploy.py 8091       # u otro puerto
```

La contraseña se pide por teclado, o se toma de la variable de entorno
`DEPLOY_SSH_PASS`.

## Plantilla de `.env.production`

Este archivo **no se versiona** (contiene claves). Variables esperadas:

```ini
APP_NAME=Projects
APP_ENV=production
APP_KEY=            # generar con: php artisan key:generate --show
APP_DEBUG=false
APP_TIMEZONE=America/Bogota
APP_URL=https://projects.doblamos.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=480
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log

# Web Push (claves VAPID)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:...
```

## Qué se conserva entre despliegues

- **Base de datos**: volumen `projects_db` (SQLite). Cada despliegue corre
  `php artisan migrate --force`; los datos no se pierden.
- **Archivos subidos**: volumen `projects_storage` (`storage/app`).
- El directorio de código en el servidor (`~/projects-app`) se **borra y
  reemplaza** en cada despliegue para no arrastrar archivos eliminados.
