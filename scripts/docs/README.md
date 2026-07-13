# Docs build scripts

Chạy từ **repo root** (`api/`):

```bash
pnpm install
pnpm docs:render
pnpm docs:dev          # http://localhost:5173 — mermaid workflow diagrams
pnpm docs:build
pnpm docs:preview
pnpm openapi:render
pnpm openapi:lint
pnpm swagger:dev
```

## Docs site map

| Path | Nội dung |
|------|----------|
| [docs/index.md](../../docs/index.md) | Hub + workflow diagram (mermaid) |
| [docs/operational/TEAM-AI-BACKEND-WORKFLOW.md](../../docs/operational/TEAM-AI-BACKEND-WORKFLOW.md) | Full pipeline diagram |
| [docs/operational/BACKEND_API_SPEC_GUIDE.md](../../docs/operational/BACKEND_API_SPEC_GUIDE.md) | Spec authoring |
| [docs/operational/INTEGRATION-API-SPEC.md](../../docs/operational/INTEGRATION-API-SPEC.md) | Webhook / partner (no Portal FE) |
| [docs/api-base/](../../docs/api-base/) | Conventions, generators |
| [docs/openapi/](../../docs/openapi/) | Merged OpenAPI + Swagger |

VitePress: [docs/.vitepress/config.ts](../../docs/.vitepress/config.ts) — `vitepress-plugin-mermaid` for diagrams.

## Scripts

| Script | Mô tả |
|--------|--------|
| `render-backend-spec.mjs` | `01-backend-spec.yaml` + `features/common/*.yaml` → `generated/*.md` + `api-base/generated.md` |
| `render-openapi.mjs` | `openapi/base.yaml` + `features/**/02-openapi.yaml` → `openapi/api.yaml` + redocly lint |
| `build-swagger-ui.mjs` | Copy Swagger UI static assets → `docs/public/swagger/` |

Codegen: [../api-gen/README.md](../api-gen/README.md)
