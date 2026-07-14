# API Spec Sync — Portal ↔ Backend

Dùng cho `/api-update-spec`, `/api-spec` (mở rộng cụm), `/grill-api-spec` (re-audit sau sync).

## Khi nào dùng

| Tình huống | Command |
|------------|---------|
| Portal `*.spec.yaml` / testcase đổi sau khi đã có `01-backend-spec.yaml` | `/api-update-spec` |
| Bổ sung portal child function vào cùng bounded context (export, login-as, …) | `/api-update-spec` hoặc `/api-spec` (cùng slug) |
| Thay đổi **chỉ BE** (derived field, validation nội bộ, cache, index) — **không** đổi FE contract | `/api-update-spec --be-only` |
| Sau sync — enrich codegen, tags, gates | `/grill-api-spec` |

## `source.portalRefs` — theo dõi từng file Portal

Mỗi portal spec file trong cụm (mirror `*.spec.yaml` trong folder):

```yaml
source:
  portalRefs:
    - id: chain-hotel-list
      path: ../portal/docs/features/chain/hotel/chain-hotel-list.spec.yaml
      syncedAt: 2026-06-27
      status: synced          # synced | stale | pending
    - id: chain-hotel-export-report
      path: ../portal/docs/features/chain/hotel/chain-hotel-export-report.spec.yaml
      syncedAt: null
      status: pending
  portalTestcaseRefs:
    - id: chain-hotel-list
      path: ../portal/docs/features/chain/hotel/testcases/chain-hotel-list.yaml
      syncedAt: 2026-06-27
```

**`/api-update-spec` workflow:**

1. Liệt kê mọi `../portal/docs/features/{slug}/*.spec.yaml` + `testcases/*.yaml`
2. So với `source.portalRefs` + nội dung `01-backend-spec.yaml` hiện tại
3. Diff `requirements`, `api.endpoints`, `acceptance`, `openQuestions` portal
4. Cập nhật backend YAML **in-place** (cùng slug folder)
5. Bump `feature.version` theo bảng semver (dưới)
6. Thêm `changeLog` entry `source: portal-spec` hoặc `source: be-only`
7. Cập nhật `portalRefs[].syncedAt` + `status`
8. Chuyển item `pendingTechDebt` → `done` khi endpoint đã vào contract; thêm mới nếu portal có spec chưa sync

## `pendingTechDebt` — việc chưa vào contract (mirror portal defer)

Portal dùng `#phase-api`, `#wire-only`, `#manual-composable`. Backend dùng block rõ ràng:

```yaml
pendingTechDebt:
  - id: TD-CHAIN-HOTEL-EXPORT
    status: pending           # pending | in-progress | done | cancelled
    source: portal
    portalRef: chain-hotel-export-report
    portalRequirementIds:
      - REQ-CHAIN-HOTEL-EXPORT-001
      - REQ-CHAIN-HOTEL-EXPORT-002
    summary: POST /hotels/export-report — xlsx 開封率
    expectedWhenDone:
      endpointIds:
        - chain-hotel.export-report
      tags:
        - "#manual-service:export-open-rate-report"
      action: custom
    blockedBy: []
    notes: Parent list embeds UI; contract in separate portal spec file.
```

**Quy tắc:**

- Grill **không** claim “cụm xong” nếu `pendingTechDebt` còn `pending` **và** portal đã có spec tương ứng — ghi rõ trong handoff “cụm FE chưa đủ BE”.
- Pilot / incremental OK: list shipped, export vẫn `pending` — **phải** có `pendingTechDebt` entry, không chỉ `requirements.deferred`.
- Khi `/api-update-spec` merge export → set `status: done`, remove khỏi pending hoặc mark done.

## `beOnlyRequirements` — BE không đổi FE contract

```yaml
beOnlyRequirements:
  - id: BE-REQ-HOTEL-001
    title: Exclude internal audit users from managers eager-load
    description: Filter at Query level; response shape unchanged for FE.
    addedAt: 2026-06-27
    affects:
      - ChainHotelSearchQuery
    feImpact: none
```

Chỉ thêm/sửa qua `/api-update-spec --be-only`:

- `beOnlyRequirements`, `derivedData`, validation nội bộ, `openQuestions` BE
- **Không** đổi `api.endpoints[].path`, response field names, pagination envelope
- `changeLog.source: be-only`; version bump **patch** only

## Semver `feature.version`

| Thay đổi | Bump | Ví dụ |
|----------|------|-------|
| Clarify mock, typo, openQuestions | patch | 0.1.0 → 0.1.1 |
| Endpoint/field **additive** (optional), portal sync không breaking | minor | 0.1.0 → 0.2.0 |
| Đổi path, rename field, đổi pagination envelope | major | 0.2.0 → 1.0.0 + `breaking: true` |
| `be-only` | patch | 0.2.0 → 0.2.1 |

Sau breaking: `approval.status` reset `draft` → cần `/grill-api-spec` lại.

## Non-CRUD checklist (grill bắt buộc khi endpoint trong scope)

| UI / portal need | `action` | Spec blocks | Tags |
|------------------|----------|-------------|------|
| Export CSV/XLSX/PDF | `custom` | `services[]`, `serviceRefs` | `#manual-service:{id}` |
| Import bulk | `custom` | `services[]` | `#manual-service:{id}` |
| Mail/SMS/payment/webhook | `custom` | `externalCalls[]` | `#call-external` + **hỏi provider** |
| 2 aggregate orchestration | `custom` | `services[]`, `alternativesConsidered` | `#cross-entity-service` |
| Login-as / handoff token | `custom` | auth notes | `#manual-action:chain-scope` (hoặc tương đương) |

**Grill phải hỏi lại** (hoặc `openQuestions` bắt buộc) trước `approved` khi thấy mail, payment, OAuth, webhook — chưa có `externalCalls` + provider.

## Handoff sau sync

```text
/api-update-spec → /grill-api-spec → (approval) → /api-code
```

Nếu chỉ `be-only` và không đổi codegen plan: grill có thể skip `api:gen:dry` regen nếu endpoints không đổi — vẫn chạy `openapi:lint` nếu sửa OpenAPI.
