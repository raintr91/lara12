# API (Laravel)

Backend Laravel modular — contract YAML, OpenAPI, codegen, harness AI.

```text
api/
├── docs/              # VitePress site + feature specs + OpenAPI
├── package.json       # Docs tooling (VitePress, Redocly, Swagger, api-gen)
├── scripts/
│   ├── api-gen/       # pnpm api:gen — Laravel codegen from spec
│   └── docs/          # render-backend-spec, render-openapi, swagger
├── src/               # Laravel (composer, artisan, Modules/)
├── shared/            # api-codegen.registry.json
├── platform-ai/       # SSOT skills, rules, extracts (mirror → .cursor)
├── .cursor/           # Mirrored by ./scripts/platform-ai-link (gitignored)
└── .harness/          # Session handoff
```

## Quick start (Laravel)

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

Docker: [docker/README.md](docker/README.md)

## Documentation site

```bash
pnpm install
./scripts/platform-ai-link   # mirror platform-ai/ → .cursor (sau clone)
pnpm docs:render
pnpm docs:dev          # → http://localhost:5173
pnpm docs:build
pnpm docs:preview
```

| Trang | Path |
|-------|------|
| **Docs hub** | [docs/index.md](docs/index.md) — workflow diagram (ASCII) |
| **Team workflow** | [docs/operational/TEAM-AI-BACKEND-WORKFLOW.md](docs/operational/TEAM-AI-BACKEND-WORKFLOW.md) |
| **Spec guide** | [docs/operational/BACKEND_API_SPEC_GUIDE.md](docs/operational/BACKEND_API_SPEC_GUIDE.md) |
| **Integration / webhook** | [docs/operational/INTEGRATION-API-SPEC.md](docs/operational/INTEGRATION-API-SPEC.md) |
| **OpenAPI** | [docs/openapi/index.md](docs/openapi/index.md) |
| **API Base** | [docs/api-base/index.md](docs/api-base/index.md) |
| **Feature contracts** | [docs/api-base/generated.md](docs/api-base/generated.md) |

Scripts: [scripts/docs/README.md](scripts/docs/README.md) · Codegen: [scripts/api-gen/README.md](scripts/api-gen/README.md)

Portal FE: [../portal/docs/operational/TEAM-AI-WORKFLOW.md](../portal/docs/operational/TEAM-AI-WORKFLOW.md)

## Team AI commands

**Portal-backed (có FE spec):**

```text
/api-spec → /grill-api-spec → /api-code
```

**Integration (webhook / partner — không Portal FE):**

```text
/api-int-spec → /grill-int-spec → /api-code
```

(`/api-integration-spec` · `/grill-integration-spec` — tên đầy đủ; xem [common integration spec](docs/features/common/generated/common-integration-spec.md))

Router: `.cursor/skills/api/SKILL.md` · Skills: `.cursor/skills/`

## OpenAPI & codegen

```bash
pnpm openapi:render    # merge → docs/openapi/api.yaml
pnpm openapi:lint
pnpm api:gen:dry --spec docs/features/{slug}/01-backend-spec.yaml
pnpm api:gen --spec docs/features/{slug}/01-backend-spec.yaml --write-spec
```

Laravel Vite (assets trong `src/`):

```bash
cd src && pnpm install && pnpm dev
```
