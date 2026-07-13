---
name: api-spec
description: >-
  /api-spec command for backend API contract. Analyze Portal feature
  spec/testcases into backend spec YAML, OpenAPI YAML, and mock data before
  Laravel implementation.
disable-model-invocation: true
---

# /api-spec — Backend API Contract

No Laravel code in this step.

Shared extracts: `.cursor/extracts/spec-evolution.md`, `api-spec-sync.md`, `entity-relationship.md`, `derived-data.md`, `agent-discipline.md`, `verify-gate.md`

Hashtags (read extract when tag present):

- `#call-external` → `.cursor/extracts/call-external.md`
- `#cross-entity-service` → `.cursor/extracts/cross-entity-service.md`

## Input

Portal (when slug given):

```text
../portal/docs/features/{slug}/*.spec.yaml
../portal/docs/features/{slug}/testcases/*.yaml
```

**Không có Portal FE** (webhook, partner API, public API) → dùng `/api-integration-spec`, không dùng command này.

Output slug mirrors portal folder path (e.g. `chain/hotel` → `docs/features/chain/hotel/`).

On first pass, seed `source.portalRefs` + `pendingTechDebt` for portal specs not yet merged — see `api-spec-sync.md`.

Guides: `docs/operational/BACKEND_API_SPEC_GUIDE.md`, `docs/templates/backend-api.yaml`, `openapi.yaml`, `mock-data.yaml`

## Output

```text
docs/features/{slug}/
├── 01-backend-spec.yaml
├── 02-openapi.yaml
├── 03-mock-data.yaml
└── generated/backend-spec.md   ← pnpm docs:render (not agent)
```

Member review: `pnpm docs:render` then `pnpm docs:dev`.

Update in place for same bounded context; see `spec-evolution.md`.

## Workflow (summary)

1. Feature group, module prefix, Platform/Tenant, aggregates, pivot M-N, relationships
2. Split endpoints by lifecycle, permission, pagination, payload weight
3. Reuse detail API for detail + edit initial data; `select-items` for dropdowns
4. Request/response, validation, filters, errors; OpenAPI + mock from spec
5. Record `openQuestions` instead of silent guesses; defer unmerged portal specs to `pendingTechDebt`
6. Domain tags only (`#call-external`, `#cross-entity-service`) — **no** `#gen:*` or `codegen` block (grill adds those)
7. Update `.harness/progress.md` when present

## Done

- YAML trio exists
- Ready for `/grill-api-spec` (not `/api-code` directly)
- Member may run `pnpm docs:render` for review markdown
