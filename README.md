# API (Laravel)

Backend Laravel modular — OpenAPI serve, unitgen, harness AI (code lane).

```text
api/
├── openapi/           # OpenAPI source (Redocly)
├── public/openapi/    # bundled output
├── package.json       # openapi:* · unitgen
├── unitgen/
├── src/               # Laravel app
├── registries/        # codegen + unit-test registries (Codegenkit)
└── .cursor/           # local harness (gitignored — from toolkit init)
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

Generation owner: **Codegenkit** (`laravel`).  
Lane bootstrap: **Platform DNA** (`--type=be --adapter=laravel`).

```bash
platform-dna init --type=be --adapter=laravel --yes
codegenkit init --type=be --adapter=laravel --yes
# optional (also pulled by platform-dna):
processkit init --type=be --target=cursor --yes
```

Skills (sau `init`, local tại `.cursor/skills/` — không commit):

| Skill | Owner |
|-------|--------|
| `/api` · `/grill-api` | Codegenkit |
| `/business-impact-review` | Processkit |

`/platform-ai` **không** sync vào destination repo (chỉ sống trong toolkit source).

Codegen:

```bash
codegenkit api-gen:dry --adapter=laravel -- --spec <path>
codegenkit api-gen --adapter=laravel -- --spec <path>
```

SSOT harness: `.cursor/` tại repo này (Platform DNA + Codegenkit + Processkit). Ignore/contract: xem `TODO-GITIGNORE.md`.
