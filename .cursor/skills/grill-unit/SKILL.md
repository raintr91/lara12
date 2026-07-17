---
name: grill-unit
extractBundle: grill-unit
description: /grill-unit — refine Vitest unit tests.
disable-model-invocation: true
---

# /grill-unit — Unit coverage audit (dev lane)

Diagram: `base-docs/platform/toolchain/UNIT-PHASE-DIAGRAM.md` (2 diagrams: unit lane + `#needs-unit-test` lifecycle)  
After: `.cursor/skills/unit/SKILL.md` done (vitest scoped green, `needsUnit` clear)

**Extracts:** `extractBundle: grill-unit` → `.cursor/extracts/extract-registry.json`

## Role

**Coverage + reqIds + edge logic** — không tạo file smoke, không gọi `portal:unit-gen` (quay `/unit` nếu thiếu **file**).

## Input (token-thin)

1. `unit.manifest.json` — `files[]`, `reqIds`
2. Kết quả `pnpm exec vitest run <entity paths> --coverage`
3. Spec — `requirements` cho `reqIds` trong manifest only (logic acceptance, không UI)

**Do not:** inventory repo, read pages/components, Playwright, re-run full `portal:unit-gen` unless báo user quay `/unit`.

## Checklist

- [ ] Mỗi `codegen.manifest` logic file có test trong `manifest.files` hoặc `commonBaselines`
- [ ] Mỗi `reqIds` có ≥1 `it()` assert behavior (không chỉ smoke pass)
- [ ] Coverage V8 trên scope feature (models/validations/services/composables entity) — team target thường **100%**
- [ ] Loại trừ hợp lý: `*.types.ts`, barrel `index.ts`, mocks (test qua composable/service)
- [ ] Không test UI trong `tests/unit/`
- [ ] Không fake pass (`skip`/`todo` che gap) trừ khi documented

## Output format

Bảng gap (nếu còn):

| Source file | Uncovered | Đề xuất 1 case | reqId |
|-------------|-----------|----------------|-------|

- **Pass:** coverage + reqIds OK → unit lane done; E2E (`/test`) là pipeline khác
- **Fail file thiếu:** chuyển `/unit` (không tự gen hàng loạt ở grill)

## Rules

- Boundary mocks only — align `portal-unit-test-common.md`
- Does not replace `/unit`
