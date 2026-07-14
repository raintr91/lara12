---
name: update-spec-legacy
extractBundle: update-spec-legacy
description: /update-spec-legacy — delta from legacy evidence.
disable-model-invocation: true
---

# /update-spec-legacy — Legacy delta patch

**Extracts:** `extractBundle: update-spec-legacy` → `.cursor/extracts/extract-registry.json`

## Load policy

| Load | Do not load |
|------|-------------|
| `_legacy.trace.yaml` — **one slice** for target function | Full module re-archaeology |
| `bundle.legacy` delta | `codegen/*`, `test/*`, prototype |
| Evidence pointer from trace/refs | Legacy repo (unless `#legacy-recheck`) |

## Workflow

1. Receive delta from `/update-spec` or grill gap.
2. Patch trace slice + `bundle.legacy` (behaviors, fields, ui) with confidence.
3. **Micro-read** legacy file/symbol only when tagged `#legacy-recheck`.
4. `pnpm spec:split` + `pnpm legacy-trace:validate` if trace changed.
5. Handoff → `/bqa-grill-docs` or `/dev-grill-docs` per section touched.

## Done

- Delta backed by evidence pointer or explicit `openQuestions`.
- No full bundle replacement unless user confirms scope explosion.
