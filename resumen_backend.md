# SaaS de Facturación — Resumen Backend

**Stack:** Laravel 11 · MariaDB · Sanctum · Spatie Permission

---

## 1. Descripción general

API REST construida en Laravel 11 que sirve exclusivamente a la app Next.js. No renderiza vistas Blade para usuarios finales. Cada empresa opera en un tenant aislado; sus datos nunca se mezclan con los de otro tenant.

| Aspecto | Decisión |
|---|---|
| Framework | Laravel 11 |
| Base de datos | MariaDB |
| Autenticación | Laravel Sanctum (tokens SPA) |
| Roles y permisos | Spatie Laravel Permission |
| Generación de PDFs | DomPDF (barryvdh/laravel-dompdf) |
| Colas de trabajo | Laravel Queues con driver database |
| Email | Laravel Mail + Resend / SMTP |
| Pagos | MercadoPago SDK + Webhooks |
| Multi-tenancy | `tenant_id` en cada tabla + middleware |

---

## 2. Estructura de carpetas

```
app/
  Http/
    Controllers/
      Auth/          → AuthController, RegisterController
      Tenants/       → TenantController
      Clients/       → ClientController
      Invoices/      → InvoiceController, InvoiceItemController
      Payments/      → PaymentController, WebhookController
      Reports/       → ReportController
    Middleware/
      TenantResolver.php   → inyecta tenant_id en cada request
      EnsureHasRole.php    → verifica rol mínimo requerido
  Models/
    Tenant.php, User.php, Client.php
    Invoice.php, InvoiceItem.php, Payment.php
  Jobs/
    ProcessMercadoPagoPayment.php
    GenerateInvoicePdf.php
    SendInvoiceEmail.php
  Policies/
    InvoicePolicy.php, ClientPolicy.php
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
| `payments` | Registro de pagos vinculados a MercadoPago. Guarda `mp_payment_id`. |

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

Todas las rutas están bajo `/api` y protegidas por `auth:sanctum` + `TenantResolver`.

### Clientes

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/api/clients` | `clients.view` |
| POST | `/api/clients` | `clients.create` |
| GET | `/api/clients/{id}` | `clients.view` |
| PUT | `/api/clients/{id}` | `clients.edit` |
| DELETE | `/api/clients/{id}` | `clients.delete` |

### Facturas

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/api/invoices` | `invoices.view` |
| POST | `/api/invoices` | `invoices.create` |
| GET | `/api/invoices/{id}` | `invoices.view` |
| PUT | `/api/invoices/{id}` | `invoices.edit` |
| POST | `/api/invoices/{id}/send` | `invoices.send` |
| GET | `/api/invoices/{id}/pdf` | `invoices.view` |
| POST | `/api/invoices/{id}/pay` | `invoices.create` |
| DELETE | `/api/invoices/{id}` | `invoices.delete` |

### Reportes

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/api/reports/revenue` | `reports.view` |
| GET | `/api/reports/invoices-summary` | `reports.view` |
| GET | `/api/reports/export/csv` | `reports.export` |

---

## 7. Flujo de pagos (MercadoPago)

1. Next.js llama a `POST /api/invoices/{id}/pay`.
2. Laravel crea una Preference en la API de MercadoPago con el monto y referencia de la factura.
3. Devuelve la `checkout_url` a Next.js, que redirige al usuario.
4. El usuario paga en MercadoPago y es redirigido de vuelta a la app.
5. MercadoPago envía un webhook a `POST /api/webhooks/mercadopago`.
6. Laravel responde `200` inmediatamente y despacha `ProcessMercadoPagoPayment` a la cola.
7. El Job consulta el estado del pago a la API de MercadoPago y actualiza `invoice.status` a `paid`.
8. Si el pago es aprobado, se despacha `SendInvoiceEmail` para notificar al cliente.

> El webhook nunca ejecuta lógica pesada directamente. Siempre delega a un Job para responder en menos de 2 segundos y evitar reintentos de MercadoPago.

---

## 8. Generación de PDFs

DomPDF convierte una vista Blade a PDF. El proceso corre en background vía Job.

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
| `spatie/laravel-permission` | Roles y permisos por tenant |
| `barryvdh/laravel-dompdf` | Generación de PDFs desde Blade |
| `mercadopago/dx-php` | SDK oficial de MercadoPago |
| `laravel/horizon` *(opcional)* | Dashboard visual para monitorear Queues |

---

## 10. Plan de desarrollo (8 semanas)

| Semanas | Tareas |
|---|---|
| 1 – 2 | Setup del proyecto, migraciones, Sanctum, registro con multi-tenancy, roles con Spatie. |
| 3 – 4 | CRUD de clientes y facturas, policies, paginación, validaciones. |
| 5 – 6 | Generación de PDFs, envío de emails, endpoint de descarga. |
| 7 | Integración con MercadoPago: preferencias, webhook, Jobs. |
| 8 | Endpoints de reportes, exportación CSV, tests básicos, documentación API. |
