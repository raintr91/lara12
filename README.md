# API (Laravel)

Backend Laravel modular — OpenAPI serve, unitgen, harness AI (code lane).

```text
api/
├── openapi/           # OpenAPI source (Redocly)
├── public/openapi/    # bundled output
├── package.json       # openapi:* · unitgen
├── unitgen/
├── src/               # Laravel app
└── .cursor/           # code-lane skills
```

## Quick start (Laravel)

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

Docker: [docker/README.md](docker/README.md)

## OpenAPI (repo này)

```bash
pnpm openapi:bundle
pnpm openapi:preview
```

## Unit gen

```bash
pnpm api:unit-gen
pnpm api:unit-gen:dry
pnpm api:unit-registry
```

## AI harness (code lane)

Skills: `.cursor/skills/` · rules/extracts: `.cursor/`.  
SSOT harness = `.cursor/` tại repo này.
