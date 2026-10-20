# firma-api

API (Laravel) del sistema de firma digital: historial de documentos firmados, lotes de firma
masiva, motivos de firma (imagen, tamaño y diseño del sello) y parámetros generales.
**Nunca recibe ni maneja claves privadas**: la firma la realiza el
[agente local](https://github.com/RomeoSoft22/firma-agente) en el equipo del usuario.

Forma parte del sistema:
[firma-web](https://github.com/RomeoSoft22/firma-web) ·
[firma-agente](https://github.com/RomeoSoft22/firma-agente) ·
[firma-servicio](https://github.com/RomeoSoft22/firma-servicio) (documentación general).

## Requisitos

- PHP 8.2+ con `openssl`, `pdo_sqlite`, `sqlite3`, `fileinfo`, `mbstring`, `curl`, `zip`, `intl`
  (en `php.ini`; se recomienda `expose_php = Off`, `upload_max_filesize = 200M`, `post_max_size = 210M`).
- [Composer](https://getcomposer.org/download/).

## Instalación

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

En `.env` ajuste como mínimo:

```env
APP_NAME=FirmaDigital
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
DB_CONNECTION=sqlite
CORS_ORIGINS=http://localhost:4200,http://127.0.0.1:4200
```

```powershell
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

## Endpoints (`/api`)

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET/POST | `/documentos` | Listar (filtros `q`, `estado`, `lote_id`) / subir PDF original |
| GET/DELETE | `/documentos/{id}` | Detalle / eliminar |
| POST | `/documentos/{id}/firmado` | Adjuntar PDF firmado + datos del firmante |
| POST | `/documentos/{id}/error` | Registrar un error de firma |
| GET | `/documentos/{id}/descargar/{original\|firmado}` | Descarga |
| GET | `/documentos/hash/{sha256}` | Comprobar si un PDF firmado fue emitido por el sistema |
| GET | `/documentos/estadisticas` | Totales (el «hoy» usa la zona `APP_ZONA_LOCAL`, por defecto America/Lima) |
| GET/POST | `/lotes`, GET `/lotes/{id}` | Lotes de firma masiva |
| GET/POST/PUT/DELETE | `/motivos` | Motivos: tamaño (`ancho`, `alto`) y diseño (`disposicion`: `horizontal`/`vertical`) del sello. DELETE es borrado lógico y nunca puede quedar sin motivos activos |
| GET/POST/DELETE | `/motivos/{id}/imagen` | Imagen del sello (PNG/JPG ≤ 1 MB) |
| GET/PUT | `/parametros` | Lugar, TSA, motivo por defecto, tamaño de sello por defecto, etc. |

## Seguridad aplicada

- Sin modo depuración: las respuestas no exponen trazas, clases ni excepciones; mensajes en español.
- Sin `X-Powered-By`, sin página de bienvenida ni `/up`; cabeceras `nosniff`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy` y `Content-Security-Policy`.
- Límite de 300 peticiones/minuto por IP; parámetros de ruta restringidos; CORS por lista blanca.
- Archivos en almacenamiento privado y validados por contenido.

**Pendiente:** autenticación de usuarios (p. ej. Laravel Sanctum) antes de publicarla en red.
