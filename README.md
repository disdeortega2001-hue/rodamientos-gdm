# Portal Rodamientos GDM

Portal público y panel administrativo. Laravel, Blade y Bootstrap, sin proceso de compilación.

## Instalar

Requiere PHP 8.3 o superior con `pdo_mysql`, `gd`, `zip`, `mbstring` y `fileinfo`, además de Composer. Para desarrollo local también sirve `pdo_sqlite`.

```bash
composer install --no-dev
cp .env.production.example .env
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan gdm:create-admin correo@ejemplo.com
```

## Cargar el Excel inicial

Se ejecuta una sola vez, después de los seeders. Los costos quedan solo en la base de datos.

```bash
php artisan gdm:import-excel "ruta/al/archivo.xlsx"
```

## Publicar

- Completa en `.env` el valor de `APP_URL` y los datos de la base MySQL.
- El portal exige HTTPS en producción, así que el hosting debe tener certificado activo.
- El directorio público del hosting debe apuntar a `public/`.
- Antes de abrir el portal al público, ejecuta las pruebas contra MySQL.
- Después de cada actualización, ejecuta `php artisan config:cache`, `route:cache` y `view:cache`.

## Respaldo

```bash
php artisan gdm:backup
```

Guarda una copia fechada en `storage/app/backups`. Prográmalo a diario en el hosting. Requiere `proc_open` y `mysqldump`; si el hosting no los permite, usa los respaldos de su panel.

## Publicar en Wasmer (plan gratis)

El plan gratis no tiene línea de comandos, SFTP ni respaldos, así que:

- `app.yaml` y `wasmer.toml` definen la aplicación, la base MySQL y el volumen de fotos.
- En Wasmer crea los secretos `APP_KEY`, `APP_URL`, `ADMIN_EMAIL` y `ADMIN_PASSWORD`.
- `gdm:setup` prepara la base y crea el primer administrador al publicar.
- En el panel, usa Ajustes > Importar Excel inicial una sola vez.
- Descarga con frecuencia Ajustes > Descargar respaldo.

## Pruebas

```bash
php artisan test
```
