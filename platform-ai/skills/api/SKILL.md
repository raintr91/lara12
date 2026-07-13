---
name: api
description: >-
  /api router for backend workflow. Routes to /api-spec (contract) or /api-code
  (implementation) based on context.
disable-model-invocation: true
---

# /api — Backend Router

| Step | Command | Skill |
|------|---------|-------|
| 1 Contract (Portal) | `/api-spec` | `.cursor/skills/api-spec/SKILL.md` |
| 1 Contract (integration) | `/api-integration-spec` | `.cursor/skills/api-integration-spec/SKILL.md` |
| 1 sync | `/api-update-spec` | `.cursor/skills/api-update-spec/SKILL.md` |
| 1b Audit (Portal) | `/grill-api-spec` | `.cursor/skills/grill-api-spec/SKILL.md` |
| 1b Audit (integration) | `/grill-integration-spec` | `.cursor/skills/grill-integration-spec/SKILL.md` |
| 2 Code | `/api-code` | `.cursor/skills/api-code/SKILL.md` |

Router rules:

- No `01-backend-spec.yaml` + **Portal-backed** → **Step 1** (`/api-spec`)
- No `01-backend-spec.yaml` + **webhook / partner / no FE** → **Step 1 integration** (`/api-integration-spec`)
- `feature.source.base: none` or `source.kind` webhook/partner → **integration** skills, not `/api-spec`
- Portal specs changed / merge deferred child functions → **Step 1 sync** (`/api-update-spec`)
- BE-only requirement (no FE contract change) → **Step 1 sync** (`/api-update-spec --be-only`)
- Spec exists but not codegen-ready → **Step 1b** (`/grill-api-spec` or `/grill-integration-spec` by `source.kind`)
- `approval.status` not `approved` → **Step 1b** (or wait for review)
- `approval.status: approved` + explicit implement → **Step 2** (`/api-code`)

Do not skip `/grill-api-spec` for new features, cross-portal, or legacy-derived contracts.

PHPUnit coverage: **không** qua router `/api` — dùng `/unit` riêng (`.cursor/rules/team-flow-unit.mdc`).

Doc: `docs/operational/TEAM-AI-BACKEND-WORKFLOW.md`
