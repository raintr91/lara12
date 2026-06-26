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

Shared extracts: `.cursor/extracts/spec-evolution.md`, `entity-relationship.md`, `derived-data.md`, `verify-gate.md`

Hashtags (read extract when tag present):

- `#call-external` → `.cursor/extracts/call-external.md`
- `#cross-entity-service` → `.cursor/extracts/cross-entity-service.md`

## Input

Portal (when slug given):

```text
../portal/docs/features/{slug}/spec.yaml
../portal/docs/features/{slug}/testcases/*.yaml
```

Guides: `src/docs/BACKEND_API_SPEC_GUIDE.md`, `src/docs/templates/backend-api.yaml`, `openapi.yaml`, `mock-data.yaml`

## Output

```text
src/docs/features/{slug}/
├── 01-backend-spec.yaml
├── 02-openapi.yaml
├── 03-mock-data.yaml
└── generated/backend-spec.md
```

Update in place for same bounded context; see `spec-evolution.md`.

## Workflow (summary)

1. Feature group, module prefix, Platform/Tenant, aggregates, pivot M-N, relationships
2. Split endpoints by lifecycle, permission, pagination, payload weight
3. Reuse detail API for detail + edit initial data; `select-items` for dropdowns
4. Request/response, validation, filters, errors; OpenAPI + mock from spec
5. Record `openQuestions` instead of silent guesses

## Done

- YAML trio + generated review doc exist
- Ready for `/grill-api-spec` or `/api-code` after review

Handoff to `/grill-api-spec` when contract is new, large, or cross-portal/legacy.
