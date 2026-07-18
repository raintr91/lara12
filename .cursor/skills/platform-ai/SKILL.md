---
name: platform-ai
description: /platform-ai — maintain code-lane harness (.cursor) on this repo.
disable-model-invocation: true
---

# /platform-ai — harness (code repo)

Only when **editing** skills / rules / extracts on **this code repository** — do not implement product features here.

## SSOT

| | |
|--|--|
| Harness | `.cursor/` in the open repository |
| Gen / registry | **Codegenkit** (`laravel`) |
| Handbook / architecture | **base-docs** |

## Skills (keep)

BE: `/api` · `/grill-api` · `/platform-ai` · `/business-impact-review`

Package owners install/sync their own skills via `platform-dna` / `codegenkit init`. Do not copy Nuxt/portal FE skill text into this BE repo.

## Done

- [ ] Code-lane harness only; no docs-authoring or FE skills
- [ ] Minimal alwaysApply (`platform-ai.mdc` from Platform DNA)
