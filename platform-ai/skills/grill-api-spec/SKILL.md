---
name: grill-api-spec
description: >-
  /grill-api-spec command for auditing backend API contract YAML after /api-spec.
  Use to verify Portal spec alignment, endpoint coverage, OpenAPI/mock consistency,
  hashtag tags, codegen readiness, and readiness for /api-code before Laravel implementation.
disable-model-invocation: true
---

# /grill-api-spec — Backend Contract Audit

After `/api-spec`, before `/api-code`. Does not implement Laravel.

Shared extracts: `.cursor/extracts/spec-evolution.md`, `api-spec-sync.md`, `entity-relationship.md`, `call-external.md`, `cross-entity-service.md`, `derived-data.md`, `agent-discipline.md`, `api-codegen-readiness.md`, `api-codegen-tags.md`, `verify-gate.md`

## Goal

- Backend contract matches Portal `spec.yaml` + testcases (**skip Portal cross-check when `feature.source.base: none`** — use `/grill-integration-spec`)
- OpenAPI and mock data align with `01-backend-spec.yaml`
- Spec is **codegen-ready** for `pnpm api:gen:dry`
- Hashtags and edge cases documented for implementation

## Workflow

1. Resolve feature slug; read Portal spec/testcases and backend YAML trio
2. Cross-check requirements vs endpoints, entities, permissions, validation, errors
3. Enrich spec: `codegen`, `api.endpoints[].action`, `#gen:*` tags, `approval` (see `api-codegen-readiness.md`)
4. Fix clear gaps in YAML/OpenAPI/mock **in scope** (no PHP)
5. Run gates (repo root): `pnpm api:gen:dry --spec docs/features/{slug}/01-backend-spec.yaml --write-spec` and `pnpm openapi:render`
6. Ask user only for product decisions; use codebase/Portal evidence otherwise
7. Record handoff in Vietnamese in `.harness/progress.md` when present — not `generated/backend-spec.md`
8. Remind member: `pnpm docs:render` for review docs

## Checklist

- Every Portal action/screen with API need has an endpoint (or explicit N/A in spec)
- Every endpoint has `action` (search|detail|create|update|delete|select-items|setting|custom)
- `codegen.module`, `codegen.entity`, `codegen.pathModel`, `codegen.wire` populated
- `#gen:*` tags match profile (`crud-standard` vs `patch`)
- No legacy page-init APIs (`GET` render login/create); SPA-init vs detail API reuse
- Platform/Tenant mode and pivot M-N (no model) correct per entity
- Detail API reused for detail + edit initial data where applicable
- Filters/sorts/includes/pagination match list UI contract
- Request/response keys match Portal FE contract naming (no rename-only mapping)
- Validation and error shapes sufficient for FE forms
- `#call-external`: `externalCalls`, OpenAPI `x-external-calls`, secrets from env
- `#cross-entity-service`: `services`, `alternativesConsidered`, OpenAPI `x-services`
- `derivedData` documented when present (`backendOnly`, refresh strategy)
- `changeLog` / `openQuestions` / `pendingTechDebt` updated if Portal spec evolved
- **Non-CRUD** (export/import/custom): `action: custom`, `services[]` + `#manual-service` OR `externalCalls[]` + `#call-external` — ask provider for mail/payment/webhook
- `pendingTechDebt` reviewed — không claim “cụm xong” nếu portal spec tồn tại mà item vẫn `pending` (trừ pilot có ghi rõ trong handoff)
- Mock data covers main happy paths and key error cases
- `pnpm api:gen:dry` exits 0; `pnpm openapi:render` exits 0

## Guardrails

- Do not scaffold Laravel classes
- Do not replace `/api-spec`; audit and tighten contract only
- No "ready for code" without `api:gen:dry` + openapi lint evidence

## Done

- Contract gaps closed or listed as `openQuestions`
- `approval.status`: `reviewed` (or `approved` if team signed off)
- `codegen.commands[]` written via `api:gen:dry --write-spec`
- Team can start `/api-code` after setting `approval.status: approved`
- Member runs `pnpm docs:render` for review markdown
