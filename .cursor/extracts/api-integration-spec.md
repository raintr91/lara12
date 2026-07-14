# API Integration Spec — Third-party & Webhook (no Portal FE)

Dùng cho `/api-integration-spec` (`/api-int-spec`), `/grill-integration-spec` (`/grill-int-spec`). **Không** đọc `../portal/docs/features/`.

Docs common: `docs/features/common/common-integration-spec.yaml`

Mirror Portal: `/legacy-spec` (code/doc làm nguồn, không FE round 1) · API portal-backed: `/api-spec`.

## Khi nào dùng

| Tình huống | Command | **Không** dùng |
|------------|---------|----------------|
| Webhook inbound (Stripe, OTA, …) | `/api-integration-spec` | `/api-spec` |
| Webhook outbound (notify partner) | `/api-integration-spec` | `/api-update-spec` portal-sync |
| Partner / public REST API xuất cho bên thứ 3 | `/api-integration-spec` | Portal testcase YAML |
| API nội bộ có Portal UI | `/api-spec` | `/api-integration-spec` |

## `feature.source` (bắt buộc khác portal-backed)

```yaml
feature:
  source:
    kind: webhook-inbound    # webhook-inbound | webhook-outbound | partner-api | public-api
    base: none               # luôn none — không có Portal FE spec
    updatedAt: YYYY-MM-DD
    integrationRefs:
      - id: stripe-events
        type: provider-doc
        path: docs/integrations/stripe/webhook-events.md
        syncedAt: null
        status: pending
      - id: legacy-handler
        type: legacy-code
        path: ../legacy/app/Http/Controllers/StripeWebhookController.php
        syncedAt: null
        status: pending
    portalRefs: []           # phải rỗng hoặc bỏ hẳn
    portalTestcaseRefs: []
```

**`integrationRefs.type`:** `provider-doc` | `partner-contract` | `legacy-code` | `openapi-external` | `runbook`

Không có `pendingTechDebt` từ portal — dùng `integrationBacklog[]` cho endpoint/event chưa merge:

```yaml
integrationBacklog:
  - id: IB-STRIPE-REFUND
    status: pending
    summary: charge.refunded handler
    expectedWhenDone:
      endpointIds: [stripe-webhook.refund]
      tags: ["#webhook-inbound"]
```

## `contexts` (không portal layout)

```yaml
contexts:
  portalLayout: none
  routePrefixes: {}
  actors:
    - webhook-sender
    - partner-client
  auth:
    - model: hmac-signature
      header: Stripe-Signature
    - model: api-key
      header: X-Api-Key
```

## Domain tags (grill thêm, không round 1)

| Tag | Khi nào |
|-----|---------|
| `#webhook-inbound` | POST callback từ bên thứ 3 |
| `#webhook-outbound` | Hệ thống gọi webhook partner |
| `#partner-api` | REST public/partner-facing |
| `#public-api` | API công khai có versioning |
| `#call-external` | Gọi HTTP ra ngoài (giữ extract cũ) |

## Contract khác portal-backed

| Chủ đề | Ghi trong spec |
|--------|----------------|
| Idempotency | `endpoint.idempotency` — event id / idempotency-key header |
| Signature | `endpoint.auth` + `security` trong OpenAPI |
| Raw payload | `derivedData` hoặc `beOnlyRequirements` — lưu log, retention |
| Retry / DLQ | `notes` + `openQuestions`; job tag grill |
| Rate limit | `endpoint.rateLimit` hoặc `notes` |
| Versioning | `feature.version` + path prefix `/v1/` |
| Error shape | Partner-facing — document trong OpenAPI, không assume FE envelope |

**Không** reuse quy tắc “match Portal FE models” — naming theo partner contract hoặc published OpenAPI.

## Output paths (giống trio)

```text
docs/features/{slug}/
├── 01-backend-spec.yaml    # source.kind ≠ portal
├── 02-openapi.yaml         # securitySchemes bắt buộc
├── 03-mock-data.yaml       # sample webhook body + partner responses
└── generated/backend-spec.md   ← pnpm docs:render
```

Slug gợi ý: `integrations/{provider}/{name}`, `partner/{name}`, `webhooks/{provider}`.

## Flow

```text
/api-integration-spec → /grill-integration-spec → approval → /api-code
```

Không nằm diagram Portal FE. Không testcase YAML.

## Grill gates (giống portal-backed)

```bash
pnpm api:gen:dry --spec docs/features/{slug}/01-backend-spec.yaml --write-spec
pnpm openapi:render
```

## Codegen profile

- Webhook handler thường `codegen.profile: patch` + `action: custom`
- Partner CRUD có thể `crud-standard` nếu module mới
- Grill quyết định `#gen:*` — không thêm ở round 1 integration-spec
