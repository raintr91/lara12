# API (Laravel)

Backend Laravel modular — contract từ Portal spec/testcases, OpenAPI YAML, harness AI riêng.

## Quick start

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

Local Docker: [docker/README.md](docker/README.md) — gateway + `base_shared_net` trước.

## Documentation

- [Docs hub](src/docs/index.md)
- [Team AI backend workflow](src/docs/TEAM-AI-BACKEND-WORKFLOW.md) — `/api-spec` → `/grill-api-spec` → `/api-code`
- [Backend API spec guide](src/docs/BACKEND_API_SPEC_GUIDE.md)
- [OpenAPI + Swagger](src/docs/OPENAPI-YAML-SWAGGER.md)

Portal FE workflow: `../portal/docs/operational/TEAM-AI-WORKFLOW.md`

## OpenAPI / docs site

```bash
cd src
pnpm openapi:lint
pnpm docs:dev
```

## Team AI harness

Shared snippets: `.cursor/extracts/`. Gỡ vendor cũ:

```bash
bash scripts/remove-ai-harness-vendor.sh
```
