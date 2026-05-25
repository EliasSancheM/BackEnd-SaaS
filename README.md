# BackEnd SaaS

API Laravel para facturación multi-tenant con pagos (MercadoPago + PayPal).

## Stack
- Laravel 13 / PHP 8.5+
- MariaDB
- Sanctum (autenticación por tokens)
- Spatie Permission (roles y permisos multi-tenant)
- DomPDF (generación de PDF)
- MercadoPago SDK 3.x
- PayPal Orders API (v2)

## Requisitos
- PHP 8.5+
- Composer
- MariaDB / MySQL

## Instalación

```bash
composer install
php artisan key:generate
```

Configura `.env` con tu base de datos, credenciales de MercadoPago y PayPal, luego:

```bash
php artisan migrate:fresh --seed
```

## Datos de prueba

| Rol | Email | Password |
|-----|-------|----------|
| owner | `test@example.com` | `password` |

Incluye: tenant demo, cliente (Acme SpA), factura, item y pago de ejemplo.

## Tests

```bash
php artisan test --compact
```

## API

### Autenticación (públicas)
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/register` | Registrar nueva empresa |
| POST | `/api/login` | Iniciar sesión |

### Webhooks (públicas)
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/webhooks/mercadopago` | Webhook MercadoPago |
| POST | `/api/webhooks/paypal` | Webhook PayPal |

### Privadas (requieren `auth:sanctum`)
| Método | Ruta | Rol mínimo | Descripción |
|--------|------|------------|-------------|
| POST | `/api/logout` | — | Cerrar sesión |
| GET | `/api/me` | — | Perfil del usuario |
| GET | `/api/tenant` | — | Empresa actual |
| GET | `/api/users` | admin | Listar usuarios |

#### Clientes
| Método | Ruta | Rol mínimo |
|--------|------|------------|
| GET | `/api/clients` | viewer |
| GET | `/api/clients/{id}` | viewer |
| POST | `/api/clients` | billing |
| PUT | `/api/clients/{id}` | billing |
| DELETE | `/api/clients/{id}` | billing |

#### Facturas
| Método | Ruta | Rol mínimo |
|--------|------|------------|
| GET | `/api/invoices` | viewer |
| GET | `/api/invoices/{id}` | viewer |
| POST | `/api/invoices` | billing |
| PUT | `/api/invoices/{id}` | billing |
| DELETE | `/api/invoices/{id}` | billing |
| GET | `/api/invoices/{id}/pdf` | viewer |
| POST | `/api/invoices/{id}/send` | billing |

#### Items de factura
| Método | Ruta | Rol mínimo |
|--------|------|------------|
| GET | `/api/invoice-items` | viewer |
| GET | `/api/invoice-items/{id}` | viewer |
| POST | `/api/invoice-items` | billing |
| PUT | `/api/invoice-items/{id}` | billing |
| DELETE | `/api/invoice-items/{id}` | billing |

#### Pagos
| Método | Ruta | Rol mínimo |
|--------|------|------------|
| GET | `/api/payments` | viewer |
| GET | `/api/payments/{id}` | viewer |
| POST | `/api/payments` | billing |
| PUT | `/api/payments/{id}` | billing |
| DELETE | `/api/payments/{id}` | billing |
| POST | `/api/payments/{id}/checkout` | billing |

#### Reportes
| Método | Ruta | Rol mínimo |
|--------|------|------------|
| GET | `/api/reports/revenue` | viewer |
| GET | `/api/reports/invoices-summary` | viewer |
| GET | `/api/reports/export/csv` | billing |

## Roles

| Rol | Permisos |
|-----|----------|
| **owner** | Todos |
| **admin** | Todos |
| **billing** | Ver clientes, gestionar facturas y pagos |
| **viewer** | Solo lectura (clientes, facturas, reportes) |

## Variables de entorno requeridas

```
MERCADOPAGO_ACCESS_TOKEN=...
MERCADOPAGO_PUBLIC_KEY=...
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
PAYPAL_WEBHOOK_ID=...
```

## Notas
- Multi-tenant por `tenant_id` con scope global.
- Los roles y permisos se asignan por tenant (Spatie teams).
- Los emails de factura se envían con `MAIL_MAILER=log` en desarrollo.

## Pendiente
- **PayPal webhook**: crear el webhook en PayPal Developer Dashboard apuntando a `POST /api/webhooks/paypal` con el evento `CHECKOUT.ORDER.APPROVED`, copiar el Webhook ID a `PAYPAL_WEBHOOK_ID` en `.env`, y habilitar la verificación de firma en `PayPalWebhookController`.
