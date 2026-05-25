# BackEnd SaaS

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-56%20passed-brightgreen)](#)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

API REST multi-tenant para facturación electrónica con pagos integrados (MercadoPago + PayPal).

---

## Stack

| | |
|---|---|
| **Framework** | Laravel 13 / PHP 8.5+ |
| **Database** | MariaDB / MySQL |
| **Auth** | Laravel Sanctum (tokens stateless) |
| **AuthZ** | Spatie Permission (teams) |
| **PDF** | DomPDF |
| **Payments** | MercadoPago SDK 3.x, PayPal Orders API v2 |
| **Mail** | Laravel Mail (`log` en desarrollo) |

---

## Quick Start

```bash
composer install
cp .env.example .env   # configura DB y credenciales de pago
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test --compact
```

---

## Demo Credentials

| Rol | Email | Password |
|-----|-------|----------|
| owner | `test@example.com` | `password` |

El seeder crea un tenant demo con cliente (Acme SpA), factura, item y pago de ejemplo.

---

## API Reference

### Public

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/register` | Register a new company |
| POST | `/api/login` | Login |
| POST | `/api/webhooks/mercadopago` | MercadoPago webhook |
| POST | `/api/webhooks/paypal` | PayPal webhook |

### Authenticated (`auth:sanctum`)

| Method | Path | Min Role | Description |
|--------|------|----------|-------------|
| POST | `/api/logout` | — | Revoke token |
| GET | `/api/me` | — | Current user profile |
| GET | `/api/tenant` | — | Current tenant |
| GET | `/api/users` | admin | List users |

#### Clients

| Method | Path | Min Role |
|--------|------|----------|
| GET | `/api/clients` | viewer |
| GET | `/api/clients/{id}` | viewer |
| POST | `/api/clients` | billing |
| PUT | `/api/clients/{id}` | billing |
| DELETE | `/api/clients/{id}` | billing |

#### Invoices

| Method | Path | Min Role |
|--------|------|----------|
| GET | `/api/invoices` | viewer |
| GET | `/api/invoices/{id}` | viewer |
| POST | `/api/invoices` | billing |
| PUT | `/api/invoices/{id}` | billing |
| DELETE | `/api/invoices/{id}` | billing |
| GET | `/api/invoices/{id}/pdf` | viewer |
| POST | `/api/invoices/{id}/send` | billing |

#### Invoice Items

| Method | Path | Min Role |
|--------|------|----------|
| GET | `/api/invoice-items` | viewer |
| GET | `/api/invoice-items/{id}` | viewer |
| POST | `/api/invoice-items` | billing |
| PUT | `/api/invoice-items/{id}` | billing |
| DELETE | `/api/invoice-items/{id}` | billing |

#### Payments

| Method | Path | Min Role |
|--------|------|----------|
| GET | `/api/payments` | viewer |
| GET | `/api/payments/{id}` | viewer |
| POST | `/api/payments` | billing |
| PUT | `/api/payments/{id}` | billing |
| DELETE | `/api/payments/{id}` | billing |
| POST | `/api/payments/{id}/checkout` | billing |

#### Reports

| Method | Path | Min Role |
|--------|------|----------|
| GET | `/api/reports/revenue` | viewer |
| GET | `/api/reports/invoices-summary` | viewer |
| GET | `/api/reports/export/csv` | billing |

---

## Roles & Permissions

| Role | Permissions |
|------|-------------|
| **owner** | Full access |
| **admin** | Full access (except plan management) |
| **billing** | View clients, manage invoices & payments |
| **viewer** | Read-only (clients, invoices, reports) |

Permissions are scoped per tenant using Spatie teams.

---

## Environment Variables

```env
MERCADOPAGO_ACCESS_TOKEN=...
MERCADOPAGO_PUBLIC_KEY=...
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
PAYPAL_WEBHOOK_ID=...
```

---

## Architecture Highlights

- **Multi-tenant isolation**: `tenant_id` column + global scope on every model
- **Role enforcement**: `EnsureHasRole` middleware + Policies with Spatie permissions
- **Payment providers**: MercadoPago Checkout Pro & PayPal Orders via unified `Payment` model
- **Invoice emailing**: Queued job generates PDF via DomPDF and sends via `Mail::log` (dev)

---

## Tests

```bash
php artisan test --compact
```

56 tests / 110 assertions covering auth, CRUD, PDF generation, payments, webhooks, role enforcement, validation, and CSV export.

---

## Pending

- **PayPal webhook verification**: create the webhook in [PayPal Developer Dashboard](https://developer.paypal.com/dashboard) pointing to `POST /api/webhooks/paypal` with event `CHECKOUT.ORDER.APPROVED`, copy the Webhook ID to `PAYPAL_WEBHOOK_ID` in `.env`, and enable signature verification in `PayPalWebhookController`.
