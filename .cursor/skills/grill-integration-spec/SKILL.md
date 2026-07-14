---
name: grill-integration-spec
description: >-
  /grill-integration-spec command for auditing integration/partner/webhook backend
  YAML after /api-integration-spec. No Portal FE cross-check. Verifies auth,
  idempotency, OpenAPI security, codegen readiness, and api:gen:dry gates.
disable-model-invocation: true
---

# /grill-integration-spec — Integration Contract Audit

After `/api-integration-spec`, before `/api-code`. No Laravel. **No Portal spec.**

Shared extracts: `.cursor/extracts/api-integration-spec.md`, `api-codegen-readiness.md`, `api-codegen-tags.md`, `call-external.md`, `entity-relationship.md`, `agent-discipline.md`, `verify-gate.md`

## Goal

- Contract đủ cho implement webhook/partner API
- OpenAPI `securitySchemes` + mock khớp `01-backend-spec.yaml`
- Codegen-ready: `pnpm api:gen:dry` + `pnpm openapi:render`

## Checklist

### Source & scope

- [ ] `feature.source.base: none` và `feature.source.kind` đúng
- [ ] `portalRefs` rỗng — không claim portal sync
- [ ] `integrationRefs[]` có ít nhất một nguồn hoặc `openQuestions` ghi rõ thiếu doc
- [ ] `integrationBacklog` cho event/endpoint chưa ship (nếu pilot từng phần)

### Auth & security

- [ ] Mỗi endpoint có `auth.model` (hmac-signature, api-key, oauth, …)
- [ ] OpenAPI `securitySchemes` + operation `security` khớp
- [ ] Secrets chỉ env name — không hardcode trong YAML

### Webhook / partner behavior

- [ ] Idempotency / dedup key documented (`endpoint.idempotency` hoặc entity field)
- [ ] Inbound: signature verify **before** business logic (decision hoặc notes)
- [ ] Outbound: retry, timeout, `#call-external` + `externalCalls[]` nếu gọi ra ngoài
- [ ] Partner error shape — không assume Portal `ApiResponse` nếu contract khác
- [ ] Rate limit / versioning ghi trong spec hoặc `openQuestions`

### Codegen

- [ ] `api.endpoints[].action` set (`custom` cho webhook; CRUD nếu partner REST)
- [ ] `codegen.module`, `profile` (`patch` thường cho webhook)
- [ ] `#gen:*` + `#manual-service` / `#manual-action` cho handler body
- [ ] Non-CRUD: `services[]` hoặc `externalCalls[]` + tags

### Gates

```bash
pnpm api:gen:dry --spec docs/features/{slug}/01-backend-spec.yaml --write-spec
pnpm openapi:render
```

- [ ] Both exit 0

## Guardrails

- Do not require Portal testcase or FE model alignment
- Do not scaffold PHP
- No "ready for code" without gate evidence

## Done

- `approval.status`: `reviewed` (or `approved` if signed off)
- `codegen.commands[]` via dry-run `--write-spec`
- Handoff `/api-code` after `approved`
