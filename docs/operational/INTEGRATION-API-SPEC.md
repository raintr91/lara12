# Integration & Webhook API Spec

Contract **không** có Portal FE — partner API, public API, webhook inbound/outbound.

> **Tên ngắn (docs):** `/api-int-spec` · `/grill-int-spec` — cùng skill với command đầy đủ bên dưới. Chi tiết: [common integration spec](../features/common/generated/common-integration-spec).

## Commands

| Bước | Đầy đủ | Alias (ngắn) | Skill |
|------|--------|--------------|-------|
| Contract | `/api-integration-spec` | `/api-int-spec` | `.cursor/skills/api-integration-spec/SKILL.md` |
| Audit | `/grill-integration-spec` | `/grill-int-spec` | `.cursor/skills/grill-integration-spec/SKILL.md` |

Sau grill → `/api-code` (cùng codegen pipeline với portal-backed features).

## Khác `/api-spec`

| Portal-backed | Integration |
|---------------|-------------|
| `source.portalRefs` | `source.integrationRefs` |
| `pendingTechDebt` | `integrationBacklog` |
| `contexts.portalLayout` | `portalLayout: none` |
| Portal testcase YAML | **Không** dùng |

## Tài liệu

- Template: [backend-api-integration.yaml](../templates/backend-api-integration.yaml)
- Extract: `.cursor/extracts/api-integration-spec.md`
- Guide §0b: [Backend API Spec Guide](./BACKEND_API_SPEC_GUIDE.md#0b-integration--partner--webhook-không-portal-fe)

## Prompt mẫu

```
/api-int-spec integrations/stripe/charge
/grill-int-spec integrations/stripe/charge
/api-code integrations/stripe/charge
```

Không nằm diagram Portal FE trong [Team AI Backend Workflow](./TEAM-AI-BACKEND-WORKFLOW.md).
