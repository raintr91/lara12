---
name: platform-ai
extractBundle: platform-ai
description: /platform-ai — maintain code-lane harness (.cursor) on this repo.
disable-model-invocation: true
---

# /platform-ai — harness (code repo)

Chỉ khi **sửa** skills / rules / extracts trên **repo code này** — không viết feature app.

## SSOT

| | |
|--|--|
| Harness | `.cursor/` tại **repo đang mở** (edit in-place) |
| Gen / gaps / tags suggest | **Artifactgraph MCP** (`analyze` · `gen` · `suggest_tags` · `rebuild`) — không skill docs song song |
| Handbook / spec / grill docs | **base-docs** (workspace khác) |
| E2E plans YAML | **base-tests** (workspace khác) |

## Skills code (giữ)

FE: `/platform-base` · `/platform-mark` · `/wire` · `/test` · `/grill-test` · `/unit` · `/grill-unit` · `/model`  
BE / fullstack thêm: `/api` · `/grill-api`

Không còn: `/prototype` · router docs · skill `/artifactgraph` (dùng MCP).

## Done

- [ ] Chỉ đụng harness code-lane; không nhồi docs skills vào FE/BE
- [ ] Rule alwaysApply tối thiểu (`platform-ai.mdc`)
