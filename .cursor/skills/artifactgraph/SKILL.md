---
name: artifactgraph
extractBundle: artifactgraph
description: /artifactgraph — local-first MCP (grill confirm local; cloud = compressed slice only).
disable-model-invocation: true
---

# /artifactgraph

Package: [raintr91/artifactgraph](https://github.com/raintr91/artifactgraph)

**Hub:** [ARTIFACTGRAPH](docs/operational/ARTIFACTGRAPH.md) · [INTERNALS](docs/operational/ARTIFACTGRAPH-INTERNALS.md)  
**Init guide:** package `docs/INIT.md` · hooks: `artifactgraph-phase-hooks.md` · rule `artifactgraph.mdc`

## Setup (once / machine + per product)

```bash
# Package on PATH
curl -fsSL https://raw.githubusercontent.com/raintr91/artifactgraph/main/install.sh | bash

# Wire agents — interactive ↑↓ · Space · Enter (global by default)
# Prefer project MCP (token) — not global
artifactgraph init --location=local --target=cursor --yes
# Interactive: artifactgraph init  (default location = local)

# Wire THIS product repo
cd ~/workspace/portal   # or nextjs, …
artifactgraph init-project
artifactgraph rebuild
```

```powershell
irm https://raw.githubusercontent.com/raintr91/artifactgraph/main/install.ps1 | iex
```

| Lệnh | Phạm vi |
|------|---------|
| `init` | Agents (Cursor / Claude / Kilo) — **không** phải từng feature repo |
| `init-project` | `artifactgraph.json` trong base hiện tại |
| `install` | Alias cũ của `init` (deprecated) |

## Local-first protocol

```text
index (rebuild from product registries)
  → analyze / grill / parity → member A/B/C (LOCAL)
  → artifactgraph_gen allowlist (docs/fe/unit/e2e)
  → cloudPromptSlice ONLY if #needs-* still missing
  → promote registry+hbs IN PRODUCT REPO → rebuild + remember
```

**SSOT:** `registries/` + templates stay in product repo (skills/docs promote). MCP **indexes** only.

| Do locally | Do NOT use cloud for |
|------------|----------------------|
| Match shells/common/unit/e2e from index | Asking “common hay chỉ feature?” |
| `specSplit` / `docsRender` / `gen` / `unitGen` / `testcaseGen` | Dumping full design registry |
| Confirm blocks / parity-drift A/B/C | Regenerating whole page when one slot missing |
| Wire Mo* / pattern already in product registry | Writing registry JSON from cloud |

## Tools

`artifactgraph_projects` · `init` (MCP = product brownfield) · `rebuild` · `analyze` · `gaps` · `grill_check` · **`parity_check`** · `remember` (`kind=grill|parity`) · `gen` · `status`

## Parity / context-orphan

After `/legacy-spec`: `parity_check` ingest `parityFindings[]` + `contextOrphans[]`.  
- **parity-drift** → `askUser` A/B/C (ép thống nhất) + `remember`  
- **context-orphan** → warn only (`usesData` ⊄ `screenData`); không confirm. Extract `legacy/parity.md`.

## Phase skills

See **artifactgraph-phase-hooks.md**.
