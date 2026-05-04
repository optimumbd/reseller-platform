# Architecture

## Directory layout

```
bin/console            — CLI entry point
config/                — Per-feature config files
database/migrations    — SQL migrations
database/seeders       — Seeders
docs/                  — This documentation
lang/{en,bn}/          — i18n strings
public/                — Web root
public/index.php       — Front controller
src/Console/           — CLI commands
src/Controllers/       — HTTP controllers
src/Core/              — MVC framework: App, Router, Container, Database, …
src/Helpers/           — Helpers (functions.php + a few classes)
src/Middleware/        — Middleware
src/Models/            — ActiveRecord models
src/Services/          — Domain logic (registrar, payment, provisioning, …)
storage/               — Logs, cache, sessions (writable)
templates/             — PHP view templates
themes/                — Optional theme overrides
```

## Request lifecycle

1. `public/index.php` → `App::boot()` → `App::run()`.
2. `Request::capture()` builds the request from PHP superglobals.
3. The router matches a route, applies its middleware chain, and invokes the controller.
4. The controller fetches data via models / services and returns a `Response`.
5. View templates are rendered with output buffering and an optional `layout('layouts/app')` wrapper.
6. `Response::send()` writes headers + body.

## Unified product pipeline

```
Product (catalog row)
  └── ProductPricing (per term/cycle)
       └── Cart / Order
            └── Invoice → Payment
                 └── Service (ledger of "the customer owns X")
                      └── ProvisioningJob (queued)
                           └── ServiceLifecycleManager
                                └── Provisioner (Domain, Hosting, Email, SSL, VPS, Dedicated)
```

Adding a new product type means adding one Provisioner and registering it; everything upstream is type-agnostic.

## Theming

Templates resolve in this order:

1. `themes/{active_theme}/templates/{name}.php`
2. `templates/{name}.php`

Override any view by creating it in your theme. The `layouts/app.php` master layout uses Tailwind via CDN — you can swap it for your own layout in your theme without touching the framework.

## i18n

Each locale is a directory under `lang/`. Each PHP file in there returns a flat array; the file basename becomes the namespace:

```
lang/en/common.php  →  __('common.save'), __('common.cancel'), …
lang/en/auth.php    →  __('auth.login'), …
```

The `TranslationService` falls back to the default locale when a key is missing in the active locale.

## Sessions, cache, queue

All three default to file-driver. No external services required. They live under `storage/`.

## Persistence layer

`App\Core\Database` is a thin PDO wrapper. `App\Models\BaseModel` adds tiny ActiveRecord-style helpers (`find`, `where`, `paginate`, `save`, `delete`). Models intentionally stay thin — business logic lives in services.
