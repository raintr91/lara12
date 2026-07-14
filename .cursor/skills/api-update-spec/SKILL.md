---
name: api-update-spec
description: >-
  /api-update-spec command — sync backend contract when Portal specs change,
  merge deferred portal child functions, or apply BE-only requirement updates
  without affecting FE contract. Use after Portal /spec or /grill-with-docs updates.
disable-model-invocation: true
---

# /api-update-spec — Portal Sync & BE-Only Updates

No Laravel code. No `codegen` / `#gen:*` — grill adds those after sync.

Shared extracts: `.cursor/extracts/api-spec-sync.md`, `spec-evolution.md`, `entity-relationship.md`, `derived-data.md`, `agent-discipline.md`

## Modes

| Mode | Prompt | Sửa gì |
|------|--------|--------|
| **portal-sync** (default) | `/api-update-spec chain/hotel` | Diff portal `*.spec.yaml` + testcases → update `01/02/03` YAML |
| **be-only** | `/api-update-spec chain/hotel --be-only` | Chỉ `beOnlyRequirements`, `derivedData`, validation nội bộ — **không** đổi FE contract |

## Input

- Existing `docs/features/{slug}/01-backend-spec.yaml` (required)
- Portal folder: `../portal/docs/features/{slug}/*.spec.yaml`, `testcases/*.yaml`
- `source.portalRefs` trong spec hiện tại (tạo nếu thiếu)

## Workflow (portal-sync)

1. Resolve slug; đọc backend YAML trio + `pendingTechDebt`
2. Scan toàn bộ portal `*.spec.yaml` trong folder (không chỉ `spec.yaml`)
3. Diff requirements, endpoints, acceptance vs backend contract
4. Update in-place:
   - `api.endpoints`, `requests`, `responses`, OpenAPI, mock
   - `requirements.covered` / `deferred`
   - `pendingTechDebt` — thêm mới hoặc mark `done` khi merged
   - `source.portalRefs[].status` — `stale` nếu portal đổi chưa sync, `synced` sau pass
5. Bump `feature.version` per `api-spec-sync.md`
6. `changeLog` entry: `source: portal-spec`, impact, `breaking`
7. **Không** thêm `codegen` / `#gen:*` / `approval` — handoff `/grill-api-spec`
8. Update `.harness/progress.md` when present — **không** viết `generated/backend-spec.md` (dùng `pnpm docs:render`)

## Workflow (be-only)

1. User mô tả thay đổi BE (derived field, query filter, internal validation)
2. Verify **không** đổi path, response keys, pagination envelope visible to FE
3. Append `beOnlyRequirements[]`, `derivedData` if needed
4. `changeLog` `source: be-only`; patch version bump
5. Update OpenAPI/mock **chỉ** nếu documented public error shape changes (hiếm)

## Output

Same paths as `/api-spec`:

```text
docs/features/{slug}/
├── 01-backend-spec.yaml   # updated in place
├── 02-openapi.yaml
├── 03-mock-data.yaml
└── generated/backend-spec.md   ← pnpm docs:render
```

## Done

- Portal delta reflected or explicitly listed in `pendingTechDebt` / `openQuestions`
- `source.portalRefs` current
- `changeLog` + version bumped
- Handoff: `/grill-api-spec {slug}` (re-run gates + codegen tags)

## Guardrails

- Do not split slug folder for child functions in same bounded context
- Do not rename API fields for FE convenience
- External integrations → record `openQuestions`; grill adds `#call-external`
- Export/import/custom → add endpoint stub + `pendingTechDebt.expectedWhenDone` if not merging this session
