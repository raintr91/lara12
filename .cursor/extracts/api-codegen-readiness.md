# API Codegen Readiness (post–/grill-api-spec)

**Who adds:** `/grill-api-spec` only — not `/api-spec`.

After grill, spec must pass:

```bash
pnpm api:gen:dry --spec docs/features/.../01-backend-spec.yaml
pnpm openapi:lint
```

## Pipeline

| Phase | Spec shape |
|-------|------------|
| `/api-spec` | Contract v1 — entities, endpoints, requests/responses; domain tags only |
| `/grill-api-spec` | Codegen-ready — add blocks below + `#gen:*` + `approval` |
| `/api-code` | `pnpm api:gen` then agent HANDOFF only |

Template v1: `docs/templates/backend-api.yaml`  
Feature example: `docs/features/_template/01-backend-spec.yaml`

## Enrich from contract v1

| Target | Source |
|--------|--------|
| `codegen.module` | `modules[0].name` or actor-based module |
| `codegen.entity` | Primary `entities[0].name` |
| `codegen.pathModel` | `entities[0].mode` (`Platform` \| `Tenant`) |
| `codegen.profile` | `crud-standard` for new module; `patch` for add-endpoint only |
| `codegen.wire` | From `api.endpoints[].action` |
| `api.endpoints[].action` | Map UI: list→`search`, detail→`detail`, create→`create`, edit→`update`, delete→`delete`, dropdown→`select-items`, setting tab→`setting` |
| `tags:` | See `api-codegen-tags.md` |
| `approval.status` | Set `reviewed` after grill; `approved` before `/api-code` |
| `codegen.commands` | Run `api:gen:dry --write-spec` or let grill list commands |

### Endpoint action map

| UI need | `action` |
|---------|----------|
| List/search table | `search` |
| Detail + edit init | `detail` |
| Create submit | `create` |
| Update submit | `update` |
| Delete | `delete` |
| Bulk delete | `bulk-delete` |
| Select dropdown | `select-items` |
| HasOne setting tab | `setting` (+ `setting.relation`, `setting.method`) |
| Non-standard | `custom` + `#manual-action` |

### Default tags for new CRUD (grill adds)

```yaml
tags:
  - "#gen:module"
  - "#gen:model-platform"   # or #gen:model-tenant
  - "#gen:crud"
  - "#gen:test-module"
```

Add `#manual-action:relationships` when `entities[].relationships` non-empty.

## Grill exit checklist

1. Portal spec/testcases ↔ backend YAML aligned.
2. `codegen` block + every endpoint has `action`.
3. Domain hashtags + `externalCalls` / `services` when tagged.
4. `approval.status` at least `reviewed`; blockers in `openQuestions`.
5. `pnpm api:gen:dry --spec <file>` exits 0.
6. `pnpm openapi:lint` exits 0.

Handoff → `/api-code` only after steps 5–6 pass and `approval: approved`.

## Non-CRUD endpoints (export, import, custom)

When endpoint `action: custom` is in scope:

| Need | Spec | Tags |
|------|------|------|
| Export/import/report file | `services[]`, `serviceRefs` | `#manual-service:{id}` |
| Mail/payment/webhook | `externalCalls[]` | `#call-external` — **ask provider in grill** |
| Multi-aggregate sync | `services[]`, `alternativesConsidered` | `#cross-entity-service` |

If portal spec exists but not merged → `pendingTechDebt` (`api-spec-sync.md`), not silent defer.

## Portal sync between grill passes

If portal changed since last grill → `/api-update-spec` first, then re-run grill + gates.
