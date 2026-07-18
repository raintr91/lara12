# Backend API — quick reference (Laravel)

> **R2/R3:** Product Code + architecture → [`base-docs`](https://github.com/raintr91/base_docs) · E2E plans → [`base-tests`](https://github.com/raintr91/base_test) · gen: `pnpm portal:gen --id …` / `pnpm testcase:gen --id …` · [HUBS](https://github.com/raintr91/base_docs/blob/1.0.0/platform/toolchain/HUBS.md) / [DOCS-HUB](https://github.com/raintr91/base_docs/blob/1.0.0/platform/toolchain/DOCS-HUB.md) / [TESTS-HUB](https://github.com/raintr91/base_docs/blob/1.0.0/platform/toolchain/TESTS-HUB.md)

Laravel modular API — OpenAPI serve, `api:unit-gen`, repository-local AI harness (code lane).

---

## Repo layout

```text
api/
├── openapi/           # OpenAPI source (Redocly)
├── public/openapi/    # bundled output
├── package.json       # openapi:* · unitgen
├── unitgen/
├── src/               # Laravel app
└── .cursor/           # code-lane skills
```

---

## Local dev

### 1. Laravel app (`src/`)

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Docker

Container stack: [docker/README.md](../../docker/README.md)

### 3. OpenAPI

```bash
pnpm openapi:bundle    # openapi/api.yaml → public/openapi/openapi.yaml
pnpm openapi:preview   # Redocly preview
```

### 4. Unit gen

```bash
pnpm api:unit-gen
pnpm api:unit-gen:dry
pnpm api:unit-registry
```

---

## AI harness (code lane)

Skills: `.cursor/skills/` · rules/extracts: `.cursor/`.
SSOT harness = `.cursor/` at this repository.
