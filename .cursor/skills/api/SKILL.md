---
name: api
description: >-
  /api router for backend workflow. Routes to /api-spec (contract) or /api-code
  (implementation) based on context.
disable-model-invocation: true
---

# /api — Backend Router

Two steps — one command per session:

| Step | Command | Skill |
|------|---------|-------|
| 1 Contract | `/api-spec` | `.cursor/skills/api-spec/SKILL.md` |
| 1b Audit | `/grill-api-spec` | `.cursor/skills/grill-api-spec/SKILL.md` |
| 2 Code | `/api-code` | `.cursor/skills/api-code/SKILL.md` |

- `/api` without approved `01-backend-spec.yaml` → default **Step 1** (`/api-spec`)
- `/api` with approved spec + explicit implement → **Step 2** (`/api-code`)
- Do not skip contract review for new features

Doc: `src/docs/TEAM-AI-BACKEND-WORKFLOW.md`
