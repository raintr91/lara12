---
name: platform-mark
extractBundle: platform-mark
description: /platform-mark — member marks spec/code for common UI, composables, and technical tags.
disable-model-invocation: true
---

# /platform-mark (portal)

Member-driven annotation — **not** `/spec` or `/dev-grill-docs`. Namespace chung với fast-api-base cho logic; UI dùng `portal-design.registry.json`.

**Extracts:** `platform-mark.md` · `platform-mark-detect.md` · `platform-design-registry.md`  
**Hub:** `docs/operational/PLATFORM-MARK.md`

## Two registries

| Layer | Registry | Tags |
|-------|----------|------|
| UI (Mo*, shell, widget) | `registries/design.registry.json` | `#needs-component:`, `#needs-ui:`, `#shell:`, `#ui:` |
| Logic (hook, service, helper) | `registries/common.registry.json` | `#common:*`, `#needs-common:*` |

Validate:

```bash
pnpm portal:registry
pnpm platform-common:registry
```

## Portal paths

- UI: `src/components/molecules/`, `src/components/organisms/`
- Logic: `src/hooks/`, `src/services/shared/`, `src/lib/`
- Spec SSOT: `docs/features/yaml/.../ir/spec.yaml` — `tags:`, `marks[]`

## Input modes

| Mode | Member says |
|------|-------------|
| Spec UI | "cột status dùng MoStatusChip common" |
| Spec logic | "export CSV thành common service" |
| Code | "đoạn parse API response này promote common" |
| Promote | sau `/prototype` — Mo* implement → registry `implemented` |

## Mark kinds

| kind | Tag | Registry | Spec |
|------|-----|----------|------|
| needs-component | `#needs-component: {slot}:MoXxx[:prop]` | design (Mo*) | `tags:` + optional `marks[]` |
| needs-ui | `#needs-ui: {Widget}` | design `planned` | `tags:` |
| common-ui | Mo* `implemented` in design registry | design | promote after `/prototype` |
| common | `#common:{id}` | platform-common | `marks[]` + `commonRefs[]` |
| needs-common | `#needs-common:{id}` | platform-common `planned` | `marks[]` |
| call-external | `#call-external` | — | `technicalMarks[]` + `externalCalls[]` |
| cross-entity-service | `#cross-entity-service` | — | `technicalMarks[]` + `services[]` |
| derived-data | `#derived-data` | — | `technicalMarks[]` + `derivedData` |

Reference common UI bundles: `docs/common/yaml/` (list-page, status-chip, …).

## Workflow

1. Resolve feature + `ir/spec.yaml` (or code path)
2. Read `tags:`, `marks[]`, `technicalMarks[]`, both registries — optional `artifactgraph_analyze`
3. Apply **one** mark per session concern
4. UI mark → upsert `registries/design.registry.json` · logic → `registries/common.registry.json`
5. If `implemented` → refactor to common path · add/update unit test when applicable
6. If `planned` → `generated/HANDOFF.md` or `openQuestions`
7. Validate: `artifactgraph_gen` `registryValidate` / `commonRegistry` **hoặc** `pnpm portal:registry` · `pnpm platform-common:registry`
8. `artifactgraph_remember` subject (vd. `column:status`) sau confirm B — lần sau local hit

## Grill integration

Grill **detects** (`platform-mark-detect.md` + `artifactgraph_grill_check`) and **asks member locally** — không auto-tag, **không cloud**.  
Member chọn B → chạy `/platform-mark` trong cùng session.

Lanes: `/dev-grill-docs` (common candidates table) · `/grill-prototype` (HANDOFF vs Mo* on disk) · `/grill-with-docs`.

## Example — status chip

```text
/platform-mark
Task: cột status dùng chip chung
Scope: admin/hotel/list ir/spec.yaml
```

→ `#needs-component: cell-status:MoStatusChip:label` in `tags:`  
→ or promote existing Mo* → design registry `implemented`

## Example — export helper

```text
/platform-mark
Task: toolbar export CSV thành common
Scope: hotel-list
```

→ `#common:export-csv` · registry `export.csv` · path `src/services/shared/export-csv.ts`

## Do not

- Full contract rewrite (`/spec`, `/dev-grill-docs` bulk)
- Auto-tag without member (grill asks first)
- Put Mo* molecules in `platform-common.registry.json` (design registry only)
- Skip registry validate for `#common:*`

Handoff: re-run `/dev-grill-docs` or `pnpm portal:gen:dry` after spec marks change.
