# Platform mark — member annotation (portal)

> Skill: `/platform-mark` · Registries: `platform-common.registry.json` + `portal-design.registry.json`

## When to use

Member (or agent after grill prompt) marks **spec** and/or **code** — **not** during round-1 `/spec` or bulk `/dev-grill-docs`.

Grill lanes ask first; member confirms → apply mark in same session.

## Mark kinds — logic (`platform-common.registry.json`)

| kind | Tag | Spec block |
|------|-----|------------|
| common | `#common:{id}` | `marks[]` + optional `commonRefs[]` on routes/endpoints |
| needs-common | `#needs-common:{id}` | `marks[]` — registry `status: planned` |
| call-external | `#call-external` | `technicalMarks[]` + `externalCalls[]` — SSOT `base-docs/product/shared/integrations/call-external.md` |
| cross-entity-service | `#cross-entity-service` | `technicalMarks[]` + `services[]` — SSOT `base-docs/product/shared/integrations/cross-entity-service.md` |
| derived-data | `#derived-data` | `technicalMarks[]` + `derivedData` — SSOT `base-docs/product/shared/data-model/derived-data.md` |

## Mark kinds — UI (`portal-design.registry.json`)

| kind | Tag | Spec / gen |
|------|-----|------------|
| needs-component | `#needs-component: {slot}:MoXxx[:prop]` | `tags:` — portal-gen HANDOFF until Mo* exists |
| needs-ui | `#needs-ui: {Widget}` | `tags:` — widget `planned` in design registry |
| common-ui | Mo* in design registry `implemented` | promote after `/prototype`; re-run `portal:gen` |

Common UI SSOT: `base-docs/product/common/yaml/` · promotion: `DESIGN-REGISTRY-PROMOTION.md`

## Spec block — `marks[]` (portal `ir/spec.yaml`)

```yaml
marks:
  - id: MK-STATUS-CHIP-001
    kind: needs-component
    tag: "#needs-component: cell-status:MoStatusChip:label"
    registryId: MoStatusChip
    reason: "Cột status render custom"
    source: dev-grill-docs

  - id: MK-EXPORT-001
    kind: common
    tag: "#common:export-csv"
    registryId: export.csv
    reason: "Toolbar export lặp ở 2 list"
    source: platform-mark
```

Logic marks may also set `commonRefs: [export.csv]` on `api.endpoints[]` when endpoint uses shared helper.

## Registry — platform common

Each `entries.{id}` in `registries/common.registry.json`:

- `status`: `planned` | `implemented`
- `tag`: `#common:{kebab-id}`
- `kind`: `dependency` | `helper` | `service` | `policy` | `client` | `hook`
- `path`, `symbol` — required when `implemented`
- `usedBy[]` — feature module or route
- `aliasIndex` — member vocabulary → entry id

Validate: `pnpm platform-common:registry`

## Registry — design (UI)

Mo*, shells, widgets: `registries/design.registry.json`  
Validate: `pnpm portal:registry`

Do **not** duplicate Mo* entries in `platform-common.registry.json`.

## Workflow (each `/platform-mark` session)

1. Resolve scope: feature slug, `ir/spec.yaml`, optional code paths
2. Read `tags:`, `marks[]`, `technicalMarks[]`, both registries
3. Apply member intent — one concern per mark
4. Update spec YAML (`tags:` and/or `marks[]`)
5. Upsert correct registry entry
6. If `implemented` → refactor to common layer + test when applicable
7. If `planned` → HANDOFF / `openQuestions`
8. Validate both registries when scripts available

## Do not

- Rewrite full contract like `/spec`
- Auto-mark without member confirmation (grill asks first)
- Emit Mo* from portal-gen (prototype implements; see HANDOFF)
