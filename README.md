# API (Laravel)

Backend Laravel modular — chỉ **code API thật**. Tooling (codegen, unitgen,
registries, harness) do toolkit sync khi `init`, không commit.

```text
api/
├── src/               # Laravel app (SSOT code)
├── docs/              # operational guides (link tới base-docs)
└── .cursor/           # local harness (gitignored — from toolkit init)
```

Sync khi init (gitignored): `.cursor/` · `.codegenkit/` · `registries/` ·
`src/.codegenkit/` (PHP unitgen — xem `codegenkit/TODO-UNITGEN-LARAVEL.md`).

Contract SSOT (api spec · `02-openapi.yaml` · mock) sống trên **base-docs**
(docs hub), không ở repo này. Xem `TODO-OPENAPI.md`.

## Quick start (Laravel)

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

Docker: [docker/README.md](docker/README.md)

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

SSOT harness: `.cursor/` tại repo này (Platform DNA + Codegenkit + Processkit).  
Maps (`platform-repos*`) sync từ `platform-dna init` — không commit.  
Ignore/contract: `TODO-GITIGNORE.md` · OpenAPI → `TODO-OPENAPI.md`.
