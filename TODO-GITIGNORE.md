# TODO — Toolkit-managed `.gitignore` cleanup

Repo không hand-maintain ignore của toolkit. Mỗi toolkit `init` phải merge
đúng artifact local mà nó thực sự sinh ra.

## Do

- [x] Giữ ignore repo/stack và CodeGraph (`.codegraph/`, `.codegraph-*/`).
- [x] Gỡ entry toolkit hard-code sẵn (`.cursor/`, agent wiring, maps,
      install-state); không copy template tĩnh vào repo.
- [x] Gỡ khỏi Git harness/map/install-state đang track; giữ product code.
- [x] Khi toolkit TODO hoàn thành, chạy lại `init` và xác nhận chỉ
      actual-written local targets được merge; agent không chọn/global wiring
      không được thêm.
- [x] Verify init hai lần không trùng và nhiều toolkit dùng `.cursor/` an toàn.

## Contract

Toolkit `src/install/gitignore.ts` (Platform DNA · Codegenkit · Processkit).
CodeGraph ngoài contract này — repo tự giữ `.codegraph/`.

## Cross-repo index routing (skill/rule)

Cross-repo index không gộp thành một graph khổng lồ (workspace cha → đơ). Cross
= skill/rule biết gọi **đúng index của repo cần**, per-repo.

- [x] Sửa skill/rule của lane này để route đúng nguồn:
  - Architecture ID / C4 path → Hubdocs (`HUBDOCS_ROOT`), không CodeGraph.
  - IR / registry / gen → pointer kit (`CODEGENKIT_DOCS_ROOT`,
    `TESTKIT_DOCS_ROOT`, `TESTKIT_TESTS_ROOT`).
  - Symbol / call-graph của repo X → MCP CodeGraph của repo X
    (`--project-root` = checkout X), không phải index repo đang mở.
  - Rule local: `.cursor/rules/cross-repo-index-routing.mdc` (Codegenkit init).
- [x] Khi init lane, Platform DNA tự đọc `platform-repos.local.json` /
      `legacy-repos.local.json` và merge `codegraph-<key>` vào
      `.cursor/mcp.json`; member không tự khai đường dẫn checkout.
  - Fix ở platform-dna v0.4.0: `codegraph:wire` return sớm trước profile
    detect nên không còn báo `role=be, not docs`. Repo chưa index thì in
    lệnh `codegraph init`. (Bootstrap cài cần update lên v0.4.0.)
- [x] Chỉ wire root vật lý tồn tại/đã có `.codegraph/`; repo chưa index thì in
      lệnh `cd <root> && codegraph init`. Không init/scan workspace cha và
      không commit đường dẫn máy.
- [x] ArtifactGraph local-only: không dùng AG docs làm index chung cho repo khác.

Installed local (gitignored): `/api` · `/grill-api` · `/business-impact-review`.
`/platform-ai` không sync vào destination (toolkit source only).
