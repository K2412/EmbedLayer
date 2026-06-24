# EmbedLayer

> Customer-facing analytics layer for SaaS products — governed, multi-tenant dashboards that drop into any application as a single web component.

EmbedLayer is **not a generic BI tool**. It is a platform that lets SaaS companies embed governed analytics into their own customer-facing applications. A product team defines semantic models (measures, dimensions, joins, access policies) once, builds dashboards against those models, then ships them to end users via a sandboxed iframe that authenticates with a short-lived JWT.

The architectural promise: the embedded runtime never knows whether data came from Postgres, BigQuery, Snowflake, Cube, or dbt Semantic Layer. It only renders.

## Repository layout

This repo is a small monorepo containing two deployable artefacts:

```
EmbedLayer-web/
├── app/                          # Laravel 13 application
│   ├── Analytics/                # The EmbedLayer product, isolated from the rest of the app
│   │   ├── Actions/              # Use-case actions
│   │   ├── Compiler/             # Deterministic SQL compilation + per-dialect rendering
│   │   ├── DataSources/          # Connector contract + driver implementations (Postgres, MySQL, BigQuery, Snowflake)
│   │   ├── Embeds/               # Embed token signing + payload contracts
│   │   ├── Pipelines/            # Query execution pipeline
│   │   ├── Security/             # Access policies, credential vault
│   │   ├── Semantic/             # Provider contract + internal/external semantic adapters
│   │   └── Support/              # Value objects, enums, exceptions
│   ├── Http/Controllers/Embed/   # JSON endpoints for the runtime
│   ├── Http/Middleware/          # EnforceEmbedOrigin
│   ├── Livewire/Analytics/       # Builder/control plane UI
│   └── Models/                   # Eloquent models (Dashboard, Chart, DataSource, SemanticModel, …)
├── packages/embed-runtime/       # Svelte 5 + TypeScript embedded runtime
├── database/migrations/          # Multi-tenant schema (organizations, teams, analytics_*)
├── docs/                         # Operator docs (e.g. per-driver read-only DB user setup)
├── routes/web.php                # Builder routes + public /embed/ + /api/embed/ endpoints
└── embedlayer_v1_v3_technical_plan.md   # (in parent dir) the architectural spec
```

The architectural spec lives one level up at `../embedlayer_v1_v3_technical_plan.md`. Section references in code comments (e.g. "Plan §9") point there.

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│ Customer Application                                             │
│   <embed-layer-dashboard token="…" embed-id="…">                 │
│   <script src="/vendor/embedlayer/runtime.js"></script>          │
└──────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ Svelte 5 Embedded Runtime  (packages/embed-runtime)              │
│   • Web component + window.EmbedLayer SDK                        │
│   • Dashboard rendering, chart adapters, filters                 │
│   • Fetches one payload from /api/embed/dashboards/{embedId}     │
│   • Fans out per-chart queries to /api/embed/charts/{id}/query   │
└──────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ Laravel API  (app/Http/Controllers/Embed)                        │
│   • Embed token validation (HMAC-signed JWT)                     │
│   • Origin enforcement (allowlist per embed)                     │
│   • Tenant context resolution                                    │
│   • Semantic query handling                                      │
│   • Cache lookup + query execution + result normalization        │
└──────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ Semantic Provider Layer  (app/Analytics/Semantic)                │
│   Internal Provider  │  Cube Provider* │  dbt Provider*          │
│   All implement the same SemanticProvider contract.              │
│   * Cube and dbt providers are V2/V3.                            │
└──────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ Compiler + Connectors  (app/Analytics/Compiler + DataSources)    │
│   Postgres │ MySQL │ BigQuery │ Snowflake                        │
│   Per-dialect SQL rendering. No SQLGlot, no runtime SQL parsing. │
└──────────────────────────────────────────────────────────────────┘
```

### Non-negotiable architectural principles

These come from §2 of the technical plan and are enforced by tests:

1. **No SQL in the chart builder.** Charts reference semantic fields (measures, dimensions, filters). The compiler turns those into SQL — the builder never does.
2. **No runtime SQL parsing.** Structured semantic definitions are the source of truth. There is no SQLGlot. Lineage, permissions, and column types come from metadata, not parsed SQL.
3. **The embedded runtime is provider-agnostic.** It cannot tell whether data came from a warehouse, Cube, or dbt — and tests guard that boundary.
4. **Read-only by construction.** EmbedLayer expects least-privilege credentials. See `docs/data-source-privileges.md` for per-driver GRANT recipes.

## Stack

| Layer | Tech |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Auth | Laravel Fortify (incl. 2FA + passkeys) |
| Builder UI | Livewire 4 + Flux UI |
| Styling | Tailwind CSS v4 |
| Embedded runtime | Svelte 5 + TypeScript, built as a self-contained IIFE |
| Tests | Pest 4 (Feature + Unit), PHPUnit 12, Larastan 3 |
| Lint/format | Laravel Pint |
| Tooling | Laravel Boost (MCP), Pail, Sail |
| Issue tracking | [bd (beads)](https://github.com/gastownhall/beads) — see `SETUP.md` |

### Supported data sources

`postgres`, `mysql`, `bigquery`, `snowflake` (toggle via `EMBEDLAYER_ENABLED_DRIVERS`).

### Supported semantic providers

`internal` ships today. Cube and dbt Semantic Layer adapters are scoped for V2/V3 (toggle via `EMBEDLAYER_ENABLED_PROVIDERS`).

## Getting started

> A condensed walkthrough lives at `SETUP.md`. The version below is the full reference.

### Prerequisites

- PHP 8.4 (`composer` available)
- Node.js 20+ and npm
- SQLite (default) — or Postgres/MySQL if you want to point Laravel at a real DB
- [`bd` (beads)](https://github.com/gastownhall/beads) CLI — required because issues live in a local Dolt DB

### First-time setup

```bash
git clone https://github.com/K2412/EmbedLayer.git
cd EmbedLayer-web

# 1. Dependencies
composer install
npm install
cd packages/embed-runtime && npm install && cd ../..

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Generate the EmbedLayer signing + encryption keys, then put them in .env:
#    EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));")
#    EMBEDLAYER_EMBED_SIGNING_KEY=$(php -r "echo bin2hex(random_bytes(32));")

# 4. Database
php artisan migrate

# 5. Build the embed runtime bundle and copy it into public/
cd packages/embed-runtime && npm run build && cd ../..
mkdir -p public/vendor/embedlayer
cp packages/embed-runtime/dist/runtime.js public/vendor/embedlayer/runtime.js

# 6. Beads — wire git hooks and seed the local issue DB
bd hooks install
bd import
bd ready
```

### Running the dev stack

```bash
composer dev
```

That runs four concurrent processes (server + queue + log tail + Vite). Equivalent to:

```bash
php artisan serve --host=localhost
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
npm run dev
```

The builder is at `http://localhost:8000/dashboard` after you log in. The embed iframe entry is at `/embed/dashboards/{embedId}`.

## Configuration

EmbedLayer-specific env vars (see `config/embedlayer.php`):

| Var | Purpose |
|---|---|
| `EMBEDLAYER_EMBED_SIGNING_KEY` | HMAC key for embed JWTs. **Required.** |
| `EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY` | Key used to encrypt customer warehouse credentials at rest (`analytics_data_sources.encrypted_config`). **Required.** |
| `EMBEDLAYER_PREVIOUS_CREDENTIAL_ENCRYPTION_KEYS` | Comma-separated list of older keys, used during rotation. |
| `EMBEDLAYER_DEFAULT_TTL_SECONDS` | Default cache TTL for query results. Default `300`. |
| `EMBEDLAYER_ENABLED_DRIVERS` | Comma-separated allowlist. Default `postgres,mysql,bigquery,snowflake`. |
| `EMBEDLAYER_ENABLED_PROVIDERS` | Comma-separated allowlist. Default `internal`. |
| `EMBEDLAYER_DEFAULT_ROW_LIMIT` | Hard cap on rows returned per chart query. Default `10000`. |
| `EMBEDLAYER_DEFAULT_QUERY_TIMEOUT_MS` | Per-query timeout. Default `30000`. |
| `EMBEDLAYER_ALLOWED_ORIGINS` | Origins allowed by the `embed.origin` middleware on `/api/embed/*`. |

To rotate the credential encryption key, add the old one to `EMBEDLAYER_PREVIOUS_CREDENTIAL_ENCRYPTION_KEYS`, set the new one as `EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY`, and run:

```bash
php artisan embedlayer:rotate-credentials
```

## Embedding a dashboard in a customer app

Once the runtime bundle is built and an embed has been provisioned, dropping a dashboard into a customer's UI looks like this:

```html
<embed-layer-dashboard
  api-base-url="https://your-embedlayer.example.com/api/embed"
  token="<JWT minted server-side for this end user>"
  embed-id="<embed_id from the builder>"
></embed-layer-dashboard>
<script src="https://your-embedlayer.example.com/vendor/embedlayer/runtime.js" defer></script>
```

Or programmatically:

```html
<div id="dashboard"></div>
<script src="/vendor/embedlayer/runtime.js"></script>
<script>
  window.EmbedLayer.renderDashboard('#dashboard', {
    apiBaseUrl: 'https://your-embedlayer.example.com/api/embed',
    token: '<JWT>',
    embedId: '<embed_id>',
  });
</script>
```

V1 ships four chart adapters: `number_card`, `bar_chart`, `line_chart`, `table`. Filters are equality-only on the dimensions declared by a dashboard's first chart.

## Data sources

Customer warehouses are connected from the builder UI (`/analytics/data-sources`). Credentials are stored encrypted in `analytics_data_sources.encrypted_config` and never leave the Laravel process.

**Always provision a read-only role for EmbedLayer.** Per-driver GRANT recipes live at `docs/data-source-privileges.md` — Postgres, MySQL, BigQuery, Snowflake.

Every connection test, introspection call, and query run is recorded in `analytics_query_runs` (scoped per organization).

## Testing

```bash
# Full suite (lint check + types + tests)
composer test

# Single suite — fast
php artisan test --compact

# Filter
php artisan test --compact --filter=CompilerTest

# Lint + format
vendor/bin/pint --dirty --format agent

# Static analysis
composer types:check
```

Notable test areas:

- `tests/Unit/CompilerTest.php` — deterministic SQL rendering per dialect
- `tests/Unit/CredentialVaultTest.php` — encryption + rotation
- `tests/Unit/QueryGuardTest.php` — row/time limits, denied operations
- `tests/Feature/DataSourceConnectorsTest.php` — connector contract conformance
- `tests/Feature/Embed/` — token validation + origin enforcement on `/api/embed/*`
- `tests/Feature/AnalyticsPolicyTest.php` — tenant isolation

## Issue tracking with beads

This repo uses **bd (beads)** instead of GitHub Issues or a TODO file. `.beads/issues.jsonl` is the git-tracked source of truth; the local Dolt DB at `.beads/embeddeddolt/` is per-clone state, kept in sync by `pre-commit` and `post-merge` hooks installed via `bd hooks install`.

```bash
bd ready                          # find unblocked work
bd show <id>                      # view an issue
bd update <id> --claim            # claim it
bd close <id> --reason "what landed"
```

See `SETUP.md` for the daily-workflow loop and `CLAUDE.md` / `AGENTS.md` for the full reference.

## Project status

V1 is complete. Open work is V2/V3 — Cube/dbt semantic providers, additional chart types, richer filter targeting, and SaaS CDN delivery for the runtime bundle. `bd ready` is the canonical source for what's next.

## License

MIT.
