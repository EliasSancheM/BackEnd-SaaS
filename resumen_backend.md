# SaaS de Facturación — Resumen Backend

**Stack:** Laravel 13 · MariaDB · Sanctum · Spatie Permission

---

## 1. Descripción general

API REST construida en Laravel 13 que sirve exclusivamente a la app Next.js. No renderiza vistas Blade para usuarios finales. Cada empresa opera en un tenant aislado; sus datos nunca se mezclan con los de otro tenant.

| Aspecto | Decisión |
|---|---|
| Framework | Laravel 13 |
| Base de datos | MariaDB |
| Autenticación | Laravel Sanctum (tokens SPA) |
| Roles y permisos | Spatie Laravel Permission (teams) |
| Generación de PDFs | DomPDF (barryvdh/laravel-dompdf) |
| Colas de trabajo | Laravel Queues con driver database |
| Email | Laravel Mail + log (desarrollo) |
| Pagos | MercadoPago SDK + PayPal Orders API |
| Multi-tenancy | `tenant_id` en cada tabla + global scope |

---

## 2. Estructura de carpetas

```
app/
  Http/
    Controllers/
      Auth/          → AuthController
      Clients/       → ClientController
      Invoices/      → InvoiceController, InvoiceItemController, InvoicePdfController, InvoiceSendController
      Payments/      → PaymentController, CheckoutController
      Reports/       → ReportController
      Tenants/       → TenantController
      Users/         → UserController
      Webhooks/      → MercadoPagoWebhookController, PayPalWebhookController
    Middleware/
      TenantResolver.php   → inyecta tenant_id en cada request
      EnsureHasRole.php    → verifica rol mínimo requerido
    Requests/
      Clients/       → StoreClientRequest, UpdateClientRequest
      Invoices/      → StoreInvoiceRequest, UpdateInvoiceRequest, StoreInvoiceItemRequest, UpdateInvoiceItemRequest
      Payments/      → StorePaymentRequest, UpdatePaymentRequest
  Models/
    Tenant.php, User.php, Client.php
    Invoice.php, InvoiceItem.php, Payment.php
  Jobs/
    SendInvoiceEmail.php
  Mail/
    InvoiceEmail.php
  Policies/
    ClientPolicy.php, InvoicePolicy.php, InvoiceItemPolicy.php, PaymentPolicy.php
  Services/
    Payments/
      MercadoPagoService.php
      PayPalService.php
```

---

## 3. Base de datos (MariaDB)

### 3.1 Tablas de identidad y permisos

| Tabla | Propósito |
|---|---|
| `tenants` | Una fila por empresa registrada. Tiene `plan` y `trial_ends_at`. |
| `users` | Usuarios del sistema. Cada user pertenece a un tenant. |
| `roles` | Roles por tenant: owner, admin, billing, viewer. |
| `permissions` | Permisos atómicos por módulo: `invoices.create`, `clients.view`, etc. |
| `role_has_permissions` | Pivot: qué permisos tiene cada rol. |
| `model_has_roles` | Pivot: qué roles tiene cada user dentro de su tenant. |

### 3.2 Tablas del negocio

| Tabla | Propósito |
|---|---|
| `clients` | Clientes de cada empresa. Campos: rut, email, teléfono, dirección. |
| `invoices` | Facturas. Estado: `draft` → `sent` → `paid` / `overdue` / `cancelled`. |
| `invoice_items` | Líneas de detalle de cada factura (descripción, cantidad, precio unitario). |
| `payments` | Registro de pagos multi-providers (MercadoPago, PayPal, manual). |

### 3.3 Jerarquía de roles

| Rol | Descripción | Permisos clave |
|---|---|---|
| `owner` | Dueño de la cuenta | Todo, incluyendo gestionar usuarios y plan |
| `admin` | Administrador operativo | Facturar, clientes, reportes — no gestionar plan |
| `billing` | Operador de facturación | Solo crear y enviar facturas |
| `viewer` | Solo lectura | Ver facturas y clientes, sin crear ni editar |

---

## 4. Autenticación (Sanctum)

Laravel Sanctum provee tokens de API sin estado. Next.js guarda el token en cookie HttpOnly. Cada request incluye `Authorization: Bearer {token}`.

| Método + ruta | Acción |
|---|---|
| `POST /api/register` | Crea tenant + user owner. Devuelve token. |
| `POST /api/login` | Valida credenciales. Devuelve token + user con roles. |
| `POST /api/logout` | Revoca el token actual. |
| `GET  /api/me` | Devuelve user autenticado con roles y permisos. |

### Flujo de registro (onboarding)

1. Next.js envía nombre de empresa, email y contraseña.
2. Laravel crea el registro en `tenants` (slug único generado automáticamente).
3. Crea el user con `tenant_id` y le asigna el rol `owner`.
4. Devuelve el token Sanctum y los datos del user.

---

## 5. Multi-tenancy

Cada request autenticado pasa por `TenantResolver`, que extrae el `tenant_id` del user y lo inyecta en el request. Todos los modelos de negocio tienen un global scope que filtra por `tenant_id` automáticamente.

```php
// app/Http/Middleware/TenantResolver.php
public function handle(Request $request, Closure $next)
{
    $tenant = $request->user()->tenant;
    app()->instance('current_tenant', $tenant);
    return $next($request);
}
```

```php
// app/Models/Invoice.php
protected static function booted()
{
    static::addGlobalScope('tenant', function ($query) {
        $query->where('tenant_id', auth()->user()->tenant_id);
    });
}
```

`Invoice::all()` solo retorna las facturas del tenant autenticado, sin filtrar manualmente en cada query.

---

## 6. Endpoints principales

Todas las rutas están bajo `/api` y protegidas por `auth:sanctum` + `TenantResolver` + `EnsureHasRole`.

### Clientes

| Método | Ruta | Rol mínimo |
|---|---|---|
| GET | `/api/clients` | viewer |
| POST | `/api/clients` | billing |
| GET | `/api/clients/{id}` | viewer |
| PUT | `/api/clients/{id}` | billing |
| DELETE | `/api/clients/{id}` | billing |

### Facturas

| Método | Ruta | Rol mínimo |
|---|---|---|
| GET | `/api/invoices` | viewer |
| POST | `/api/invoices` | billing |
| GET | `/api/invoices/{id}` | viewer |
| PUT | `/api/invoices/{id}` | billing |
| POST | `/api/invoices/{id}/send` | billing |
| GET | `/api/invoices/{id}/pdf` | viewer |
| DELETE | `/api/invoices/{id}` | billing |

### Items de factura

| Método | Ruta | Rol mínimo |
|---|---|---|
| GET | `/api/invoice-items` | viewer |
| POST | `/api/invoice-items` | billing |
| GET | `/api/invoice-items/{id}` | viewer |
| PUT | `/api/invoice-items/{id}` | billing |
| DELETE | `/api/invoice-items/{id}` | billing |

### Pagos

| Método | Ruta | Rol mínimo |
|---|---|---|
| GET | `/api/payments` | viewer |
| POST | `/api/payments` | billing |
| GET | `/api/payments/{id}` | viewer |
| PUT | `/api/payments/{id}` | billing |
| DELETE | `/api/payments/{id}` | billing |
| POST | `/api/payments/{id}/checkout` | billing |

### Reportes

| Método | Ruta | Rol mínimo |
|---|---|---|
| GET | `/api/reports/revenue` | viewer |
| GET | `/api/reports/invoices-summary` | viewer |
| GET | `/api/reports/export/csv` | billing |

### Webhooks (públicas)

| Método | Ruta | Propósito |
|---|---|---|
| POST | `/api/webhooks/mercadopago` | Notificaciones de pago MercadoPago |
| POST | `/api/webhooks/paypal` | Notificaciones de pago PayPal |

---

## 7. Flujo de pagos

### MercadoPago Checkout Pro

1. Se crea un Payment con `provider = mercadopago`.
2. `POST /api/payments/{id}/checkout` crea una Preference en MercadoPago y devuelve `init_point`.
3. El usuario paga en MercadoPago.
4. MercadoPago envía webhook a `POST /api/webhooks/mercadopago`.
5. El webhook actualiza el estado del payment.

### PayPal Orders

1. Se crea un Payment con `provider = paypal`.
2. `POST /api/payments/{id}/checkout` crea una Order en PayPal y devuelve `approval_url`.
3. El usuario aprueba y PayPal redirige de vuelta.
4. PayPal envía webhook `CHECKOUT.ORDER.APPROVED` a `POST /api/webhooks/paypal`.
5. El webhook captura la orden y actualiza el estado.

---

## 8. Generación de PDFs

DomPDF convierte una vista Blade a PDF.

```php
$pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
return $pdf->download("factura-{$invoice->number}.pdf");
```

La vista `resources/views/invoices/pdf.blade.php` usa estilos CSS inline para máxima compatibilidad con DomPDF. Incluye logo, datos del tenant, tabla de items, totales con IVA y datos del cliente.

---

## 9. Paquetes clave

| Paquete | Propósito |
|---|---|
| `laravel/sanctum` | Autenticación API con tokens |
| `spatie/laravel-permission` | Roles y permisos por tenant (teams) |
| `barryvdh/laravel-dompdf` | Generación de PDFs desde Blade |
| `mercadopago/dx-php` | SDK oficial de MercadoPago |
| `guzzlehttp/guzzle` | Cliente HTTP para PayPal API |
| `laravel/horizon` *(opcional)* | Dashboard visual para monitorear Queues |

---

## 10. Envío de emails

Las facturas se envían por email:

1. `POST /api/invoices/{id}/send` (solo facturas en draft).
2. El controller despacha `SendInvoiceEmail` a la cola.
3. El Job genera el PDF con DomPDF, lo adjunta al Mailable y envía.
4. El invoice se marca como `sent` y se guarda `sent_at`.

En desarrollo los emails se guardan en `storage/logs/laravel.log` (`MAIL_MAILER=log`).

---

## 11. Tests

56 tests (PHPUnit), 107 assertions:
- Autenticación (register, login, logout, me, tenant)
- CRUD clientes, facturas, items, pagos
- Envío de facturas (send)
- Generación de PDFs
- Checkout (MercadoPago, PayPal)
- Webhooks (MercadoPago, PayPal)
- Roles y permisos (viewer no puede crear, billing no puede eliminar facturas)
- Validación de campos requeridos
- Exportación CSV
- Reportes
