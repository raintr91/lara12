---
name: grill-api-spec
description: >-
  /grill-api-spec command for auditing backend API contract YAML after /api-spec.
  Use to verify Portal spec alignment, endpoint coverage, OpenAPI/mock consistency,
  hashtag tags, and readiness for /api-code before Laravel implementation.
disable-model-invocation: true
---

# /grill-api-spec — Backend Contract Audit

After `/api-spec`, before `/api-code`. Does not implement Laravel.

Shared extracts: `.cursor/extracts/spec-evolution.md`, `entity-relationship.md`, `call-external.md`, `cross-entity-service.md`, `derived-data.md`, `verify-gate.md`

## Goal

- Backend contract matches Portal `spec.yaml` + testcases
- OpenAPI and mock data align with `01-backend-spec.yaml`
- Hashtags and edge cases documented for implementation

## Workflow

1. Resolve feature slug; read Portal spec/testcases and backend YAML trio
2. Cross-check requirements vs endpoints, entities, permissions, validation, errors
3. Fix clear gaps in YAML/OpenAPI/mock **in scope** (no PHP)
4. Ask user only for product decisions; use codebase/Portal evidence otherwise
5. Record handoff in Vietnamese: complete, gaps, blockers for `/api-code`

## Checklist

- Every Portal action/screen with API need has an endpoint (or explicit N/A in spec)
- No legacy page-init APIs (`GET` render login/create); SPA-init vs detail API reuse
- Platform/Tenant mode and pivot M-N (no model) correct per entity
- Detail API reused for detail + edit initial data where applicable
- Filters/sorts/includes/pagination match list UI contract
- Request/response keys match Portal FE contract naming (no rename-only mapping)
- Validation and error shapes sufficient for FE forms
- `#call-external`: `externalCalls`, OpenAPI `x-external-calls`, secrets from env
- `#cross-entity-service`: `services`, `alternativesConsidered`, OpenAPI `x-services`
- `derivedData` documented when present (`backendOnly`, refresh strategy)
- `changeLog` / `openQuestions` updated if Portal spec evolved
- Mock data covers main happy paths and key error cases

## Guardrails

- Do not scaffold Laravel classes
- Do not replace `/api-spec`; audit and tighten contract only
- No "ready for code" without reading backend YAML evidence

## Done

- Contract gaps closed or listed as `openQuestions`
- Team can start `/api-code` with approved spec
