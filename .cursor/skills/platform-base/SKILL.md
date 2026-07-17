---
name: platform-base
description: Nuxt 4 FE conventions — layers, testId; gen first; see invariants (glob)
disable-model-invocation: true
---

# Platform Base (Nuxt 4 FE)

Auth-first Nuxt 4 · Pinia · vee-validate+Zod · shadcn · Playwright.

**Rules (FE globs):** `platform-invariants.mdc`, `platform-contract-naming.mdc`, `platform-base-*`, size/split/import.

**Hub:** `base-docs/platform/toolchain/ARCHITECTURE.md`, `E2E-TESTIDS.md` · **E2E:** `/test`

## Gen trước

1. `pnpm portal:gen --id …` / Artifactgraph `gen` từ `ir/spec.yaml`
2. AI chỉ gap: Mo* / `#needs-*` chưa có trong registry
3. Không viết boilerplate layer bằng tay nếu gen đã cover

## Layers (tóm tắt)

`pages/components` → `composables` → `services`+`stores` → `models`+`validations` → `$apiFetch`  
Không `$apiFetch` ở page/component. Form: `useApiForm`.

UI: `ui/` → `Mo*` → `Data*|OrGlobal*` · shell `DataListPage`.

testId: `{module}-{field|action}-input|btn|dialog|alert` · `page.getByTestId()`.

## Checklist

- [ ] 4 tầng · file gọn · testId interactive
- [ ] Full E2E → `/test` (không dump E2E rules khi chỉ prototype)
