# Team AI Backend Workflow

Progressive disclosure: **một session = một command**. Shared snippets: `.cursor/extracts/`

```text
Portal spec/testcases
  ↓
/api-spec  →  backend YAML + OpenAPI + mock
  ↓
/grill-api-spec  →  audit contract (optional, recommended)
  ↓ review
/api-code  →  Laravel implementation
```

## Commands

| Command | Skill | Output |
|---------|-------|--------|
| `/api-spec` | `.cursor/skills/api-spec/` | `src/docs/features/{slug}/01-backend-spec.yaml`, `02-openapi.yaml`, `03-mock-data.yaml` |
| `/grill-api-spec` | `.cursor/skills/grill-api-spec/` | Audit/fix contract gaps before code |
| `/api-code` | `.cursor/skills/api-code/` | Laravel modules per spec |
| `/api` | `.cursor/skills/api/` | Router — defaults to spec if no approved backend spec |

Hashtags in spec (read matching extract):

- `#call-external` → `.cursor/extracts/call-external.md`
- `#cross-entity-service` → `.cursor/extracts/cross-entity-service.md`

## Input from Portal

```text
../portal/docs/features/{slug}/spec.yaml
../portal/docs/features/{slug}/testcases/*.yaml
```

## Rules vs skills

| Tầng | Ví dụ |
|------|-------|
| alwaysApply | `api-invariants.mdc` |
| globs | `team-flow-api-spec`, `team-flow-api-code`, `api-code-size` |
| opt-in skill | `api-spec`, `grill-api-spec`, `api-code` |

## OpenAPI tooling

```bash
pnpm openapi:lint
pnpm openapi:bundle
pnpm openapi:preview
pnpm swagger:dev
```

## Prompt mẫu

```
/api-spec bóc API từ portal spec admin-hotel-list
/grill-api-spec soi backend spec hotel list trước khi code
/api-code implement module Hotel theo spec đã chốt
```

Legacy commands `team-phase3-backend-*` → dùng `/api-spec`, `/api-code`.
