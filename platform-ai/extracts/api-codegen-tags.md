# API Codegen Tags

Source: `pnpm api:gen --spec docs/features/.../01-backend-spec.yaml`  
Registry: `shared/api-codegen.registry.json` (mirror `portal/shared/portal-design.registry.json`)  
Manifest: `docs/features/{slug}/generated/codegen.manifest.json`  
Templates: `docs/templates/backend-api.yaml`  
Handoff: `docs/features/{slug}/generated/HANDOFF.md`

## Who adds what

| Phase | Adds to `01-backend-spec.yaml` |
|-------|-------------------------------|
| `/api-spec` | Contract v1 — entities, endpoints, domain tags only; **no** `codegen`, **no** `#gen:*` |
| `/grill-api-spec` | `codegen`, `api.endpoints[].action`, `#gen:*`, `approval`, `codegen.commands` — see `api-codegen-readiness.md` |
| `/api-code` | Run `pnpm api:gen`; read `codegen.manifest.json` + HANDOFF; finish `#manual-*` |

## Spec blocks for codegen

| Block | Purpose |
|-------|---------|
| `approval.status` | `draft` → `reviewed` → `approved` (required for `api:gen` execute) |
| `codegen.module` | StudlyCase module (`Admin`) |
| `codegen.entity` | Controller/model base name (`Hotel`) |
| `codegen.pathModel` | `Platform` \| `Tenant` |
| `codegen.sharedModel` | `true` — app model in `App\Models\{Platform\|Tenant}\*` |
| `codegen.profile` | `crud-standard` \| `patch` |
| `codegen.wire` | `search`, `detail`, `create`, `update`, `delete`, `selectItems` booleans |
| `codegen.skip` | Skip layers: `module`, `model`, `controller`, `tests` |
| `codegen.commands` | Resolved `php artisan ...` lines (api-gen writes) |
| `api.endpoints[].action` | `search`, `detail`, `create`, `update`, `delete`, `bulk-delete`, `select-items`, `setting`, `custom` |

## Domain hashtags (`tags:`)

| Tag | Spec block |
|-----|------------|
| `#call-external` | `externalCalls`, endpoint `externalCallRefs`, OpenAPI `x-external-calls` |
| `#cross-entity-service` | `services`, `serviceRefs`, `alternativesConsidered` |
| `#derived-data` | `derivedData` with `backendOnly`, `refresh` |

## Integration domain tags (no Portal FE — `/api-integration-spec`)

| Tag | Spec block |
|-----|------------|
| `#webhook-inbound` | Inbound POST; `auth` HMAC/signature; `idempotency` |
| `#webhook-outbound` | Outbound notify; `externalCalls` + retry |
| `#partner-api` | Partner-facing REST; OpenAPI `securitySchemes` |
| `#public-api` | Versioned public surface; path prefix `/v{n}/` |

Detail: `.cursor/extracts/api-integration-spec.md`

## Codegen hashtags (`tags:`)

| Tag | Generator behavior |
|-----|-------------------|
| `#gen:module` | `m:module {Module}` |
| `#gen:model-platform` | `m:model {Entity} Platform --create-migration=yes ...` |
| `#gen:model-tenant` | `m:model {Entity} Tenant ...` |
| `#gen:crud` | `m:controller` wizard with `codegen.wire` |
| `#gen:action-{search\|detail\|create\|update\|delete\|bulk-delete}` | `add:action` (profile `patch`) |
| `#gen:select-items` | `add:select-item` |
| `#gen:create-or-update:{rel}:{method}` | `m:add-createOrUpdate` |
| `#gen:auth-api` | `m:auth-api` |
| `#gen:test-module` | `m:module-test {Module} --type=all` |
| `#gen:test-smoke` | Included with `m:module` route smoke tests |
| `#skip-gen:{layer}` | Same as `codegen.skip` for one layer |
| `#manual-action:{topic}` | HANDOFF — relationship sync, custom validation |
| `#manual-service:{id}` | HANDOFF — implement Service body |
| `#manual-test:{case}` | `/unit` (standalone — `unit-coverage.md`, không testcase YAML) |

`action: setting` requires `endpoint.setting.relation` + `endpoint.setting.method` (HasOne).

## Commands

```bash
pnpm api:registry
pnpm api:gen:dry --spec docs/features/admin/hotel/01-backend-spec.yaml
pnpm api:gen:dry --spec docs/features/admin/hotel/01-backend-spec.yaml --write-spec
pnpm api:gen --spec docs/features/admin/hotel/01-backend-spec.yaml --write-spec
```

`--write-spec` writes `codegen.commands[]`, `generated/codegen.manifest.json`, and `generated/HANDOFF.md`.

Script phân tích `src/` trước khi sinh lệnh — module/model đã có thì không truyền `m:module`/`m:model`; controller đã wired thì dùng `add:action` cho endpoint thiếu; options khớp `make_help.md` (`--shared-model=no`, `--create-request=no`, `--overwrite-controller=no`, …).

## `/api-code` session order

1. Confirm `approval.status: approved` and `api:gen:dry` passed in grill.
2. `pnpm api:gen --spec ... --write-spec`
3. Read `generated/codegen.manifest.json` and `generated/HANDOFF.md` — implement `#manual-action` / `#manual-service` only.
4. `cd src && php artisan test --filter={Module}`

Do not hand-scaffold classes when `codegen.commands` already lists the generator.
