---
name: api-integration-spec
description: >-
  /api-integration-spec command for backend API contract without Portal FE base —
  third-party partner APIs, public APIs, inbound/outbound webhooks. Use when there
  is no ../portal/docs/features spec; source is provider docs, partner contract,
  or legacy code.
disable-model-invocation: true
---

# /api-integration-spec — Integration Contract (no Portal FE)

**Không** đọc Portal `*.spec.yaml` / `testcases/*.yaml`. **Không** Laravel code.

Mirror Portal `/legacy-spec` (nguồn = doc/code) + API trio output giống `/api-spec`.

Shared extracts: `.cursor/extracts/api-integration-spec.md`, `entity-relationship.md`, `call-external.md`, `agent-discipline.md`, `verify-gate.md`

## When (use this instead of `/api-spec`)

- Webhook inbound / outbound
- REST API xuất cho partner / bên thứ 3
- Public API có versioning riêng
- Reverse-engineer từ legacy handler hoặc provider OpenAPI

If Portal FE spec exists for the same UX → use `/api-spec`, not this command.

## Input

```text
User: provider name, event list, partner PDF/OpenAPI URL, legacy path
docs/templates/backend-api-integration.yaml
docs/operational/BACKEND_API_SPEC_GUIDE.md  # § Integration
Optional: ../legacy/... controller routes
Optional: docs/integrations/{provider}/*.md (repo-local notes)
```

## Output

```text
docs/features/{slug}/
├── 01-backend-spec.yaml    # feature.source.kind + integrationRefs
├── 02-openapi.yaml         # securitySchemes required
├── 03-mock-data.yaml       # webhook samples, partner request/response
└── generated/backend-spec.md   ← pnpm docs:render
```

Slug ví dụ: `integrations/stripe/charge`, `partner/acme/v1-hotels`, `webhooks/ota/booking`.

## Workflow

1. Set `feature.source.kind`, `base: none`, `integrationRefs[]` — **empty** `portalRefs`
2. `contexts.portalLayout: none`; document `contexts.auth` (API key, HMAC, OAuth)
3. Inventory events/endpoints từ provider doc hoặc legacy code — mark `inferredFromCode` in `notes`
4. Entities, idempotency keys, dedup, raw payload policy → `decisions` / `beOnlyRequirements`
5. `api.endpoints` — thường `action: custom`; partner list có thể `search`/`detail`
6. OpenAPI: `securitySchemes`, webhook request body schema, error codes partner-facing
7. Mock: representative webhook JSON + ack response
8. Domain tags only: `#webhook-inbound`, `#webhook-outbound`, `#partner-api`, `#public-api`, `#call-external`
9. **No** `codegen`, **no** `#gen:*`, **no** `approval` beyond `draft` — grill adds
10. `integrationBacklog[]` cho event/endpoint defer (thay `pendingTechDebt` portal)
11. `openQuestions` thay vì đoán signature, retry, retention
12. Update `.harness/progress.md` when present

## Round 1 — do not add

- `codegen` block, `#gen:*`, `codegen.commands`
- `source.portalRefs` / portal testcase refs
- Portal layout, FE model naming rules

## Done

- YAML trio + `source.kind` + `integrationRefs`
- Ready for `/grill-integration-spec`
- Member: `pnpm docs:render`
