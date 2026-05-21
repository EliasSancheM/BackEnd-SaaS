# BackEnd SaaS

API Laravel para facturacion multi-tenant.

## Stack
- Laravel 13
- MariaDB
- Sanctum
- Spatie Permission
- DomPDF
- MercadoPago SDK

## Requisitos
- PHP 8.5+
- Composer
- MariaDB

## Instalacion
```bash
composer install
php artisan key:generate
```

Configura `.env` con tu base de datos y luego ejecuta:
```bash
php artisan migrate:fresh --seed
```

## Datos de prueba
El proyecto incluye un seeder demo con:
- tenant demo
- usuario `test@example.com`
- password `password`
- cliente, factura, item y pago de ejemplo

## Tests
```bash
php artisan test --compact
```

## API
Rutas base disponibles:
- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`
- `GET /api/tenant`
- `GET|POST|PUT|DELETE /api/clients`
- `GET|POST|PUT|DELETE /api/invoices`
- `GET|POST|PUT|DELETE /api/invoice-items`

## Notas
- La app usa `tenant_id` para separar datos por empresa.
- `auth:sanctum` protege los endpoints privados.
- Los roles y permisos se siembran por tenant.
