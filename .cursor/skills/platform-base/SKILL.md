---
name: platform-base
description: Nuxt 4 app conventions — layers, testId, E2E; see invariants + ARCHITECTURE.md
disable-model-invocation: true
---

# Platform Base (Nuxt 4)

Auth-first Nuxt 4 · Vue 3 · Pinia · vee-validate+Zod · shadcn-vue · Playwright.

**Rules:** `platform-invariants.mdc`, `platform-contract-naming.mdc`, glob `platform-base-*`, `platform-code-size`, `platform-component-split`.

**Docs:** `docs/operational/ARCHITECTURE.md`, `E2E-TESTIDS.md` · **E2E skill:** `test/SKILL.md`

**Maps (only when cross-repo):** `legacy/project-config.md` — progressive resolve; never dump full `platform-repos.json`. Hub: `docs/operational/PROJECT-MAPS.md`.

## Layers

`pages/components` → `composables` → `services` + `stores` → `models` + `validations` → `$apiFetch`

| Tầng | Không làm |
|------|-----------|
| page/component | `$apiFetch` |
| composable | HTTP chi tiết |
| service | Pinia state |
| models | import stores/services/composables |

**New feature order:** models → service → store? → composable → validations? → page + testId

**Form:** `useApiForm` + `validations/` (chặt) + `models/` (API contract lỏng).

## UI tiers

`ui/` → `molecules/Mo*` → `organisms/Data*|OrGlobal*` · list shell: `DataListPage` · dashboard: `layouts/dashboard.vue`

## testId (summary)

`{module}-{field|action}-input|btn|dialog|alert` · auth: `auth-login-*` · `page.getByTestId()` · no `#id`/class selectors.

**Detail:** [reference.md](reference.md) · `E2E-TESTIDS.md`

## E2E

```bash
pnpm test:e2e    # NUXT_PUBLIC_E2E=1, port 3005
```

After `goto`: `assertLayoutIntegrity(page)` · specs `tests/e2e/**/*.spec.ts`

## Agent checklist

- [ ] 4 layers respected · file ~≤200 lines · testId on interactives
- [ ] `/test` for full E2E — `/prototype` only smoke skeleton if needed
