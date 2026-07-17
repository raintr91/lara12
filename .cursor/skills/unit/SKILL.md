---
name: unit
extractBundle: unit
description: /unit — Vitest unit tests via portal:unit-gen.
disable-model-invocation: true
---

# /unit — Portal Vitest (dev lane)

Diagram: `base-docs/platform/toolchain/UNIT-PHASE-DIAGRAM.md`  
Hub: `base-docs/platform/toolchain/PORTAL-CODEGEN.md`

**Extracts:** `extractBundle: unit` → `.cursor/extracts/portal-unit-workflow.md`

## Load policy

| Load | Do not load |
|------|-------------|
| `{function}/generated/unit.manifest.json` | `legacy/*`, E2E testcase YAML |
| `{function}/generated/UNIT-HANDOFF.md` | Full `tests/unit/` inventory |
| `{function}/ir/spec.yaml` — codegen, reqIds filter | `codegen/readiness` full doc |
| Source files listed in manifest gap | `pages/`, `components/` |

## Workflow

0. **Artifactgraph:** `analyze` / `gaps` on `ir/spec.yaml`; `artifactgraph_gen` `unitGen` hoặc `unitGenDry` khi MCP wired (else step 1).
1. `pnpm portal:unit-gen --spec base-docs Code / `--id`` (fallback).
2. Clear `needsUnit[]` per manifest.
3. `pnpm exec vitest run <paths from manifest.written>`
4. Pattern mới ổn → promote `registries/unit-test.registry.json` + `remember`.

**Do not read:** legacy-api-migration, design registry full scan.
**Cloud:** chỉ khi pattern không có trong unit registry — dùng `cloudPromptSlice`, không dump registry.

## Handoff

→ `/grill-unit`
