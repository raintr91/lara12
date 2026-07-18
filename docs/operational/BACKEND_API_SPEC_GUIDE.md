# Backend API Spec Guide

Guide này dùng cho Step 1 `/api-spec`: phân tích FE spec thành backend API contract.

## 0. Update Spec Cũ Hay Tạo Spec Mới

Team làm song song nên FE có thể update portal spec sau khi BE đã có backend spec. Mặc định:

- Cùng feature slug/cụm chức năng → update spec cũ.
- Child function thuộc aggregate cũ → update spec cũ.
- Endpoint bổ sung cho màn mới cùng domain → update spec cũ.
- Model/relationship bổ sung cho entity cũ → update spec cũ.

Chỉ tạo spec mới nếu thay đổi là bounded feature độc lập hoặc user yêu cầu tách.

Ví dụ không tạo spec mới:

```text
admin quản lý chain
  + setting chain
```

`setting chain` là child function của aggregate `Chain`, nên update:

```text
docs/features/chain/01-backend-spec.yaml
docs/features/chain/02-openapi.yaml
docs/features/chain/03-mock-data.yaml
docs/features/chain/generated/backend-spec.md
```

Mỗi lần update phải thêm `changeLog`:

```yaml
changeLog:
  - id: CHG-CHAIN-002
    source: portal-spec
    summary: Add chain setting child function.
    impact:
      - Add hasOne ChainSetting relationship.
      - Add PUT /admin/chains/{id}/setting endpoint.
    breaking: false
```

Nếu BE đã implement một phần, changeLog phải ghi rõ implementation impact để Step 2 biết cần migration/action/request/resource nào phải sửa.

### Portal sync & version

Khi Portal đổi sau khi đã có backend YAML:

| Việc | Command |
|------|---------|
| Portal `*.spec.yaml` / testcase đổi | `/api-update-spec {slug}` |
| Chỉ requirement BE (không đổi FE contract) | `/api-update-spec {slug} --be-only` |
| Sau sync — codegen + gates | `/grill-api-spec {slug}` |

Chi tiết: `.cursor/extracts/api-spec-sync.md`

- `source.portalRefs[]` — track từng portal spec file (`synced` \| `stale` \| `pending`)
- `pendingTechDebt[]` — portal function chưa merge vào contract (export, login-as, …)
- `beOnlyRequirements[]` — thay đổi BE không ảnh hưởng FE
- `feature.version` semver — patch / minor / major (breaking → reset approval)

## 0b. Integration / Partner / Webhook (không Portal FE)

Khi API **không** có Portal `*.spec.yaml` (webhook, partner export, public API):

| Việc | Command đầy đủ | Alias (ngắn) |
|------|----------------|--------------|
| Contract v1 | `/api-integration-spec {slug}` | `/api-int-spec {slug}` |
| Audit + codegen | `/grill-integration-spec {slug}` | `/grill-int-spec {slug}` |
| Implement | `/api-code {slug}` | — |

- Integration API spec now lives in the integration repo: [git](https://github.com/raintr91/integration)
- Template: `docs/templates/backend-api-integration.yaml`
- Extract: `.cursor/extracts/api-integration-spec.md`
- `feature.source.base: none`, `integrationRefs[]` thay `portalRefs`
- `integrationBacklog[]` thay `pendingTechDebt` (portal defer)
- **Không** testcase YAML; **không** đối chiếu FE models

## 1. Đọc Requirement Theo Cụm Chức Năng

Không phân tích từng màn cô lập nếu feature là một cụm liên quan.

Ví dụ cụm Hotel có thể gồm:

- list/search
- create
- detail
- edit
- setting tabs
- select hotel cho màn khác
- dashboard/summary

Phải hiểu cụm trước rồi mới quyết định endpoint/module.

## 2. Xác Định Portal Layout

Prompt có thể dẫn tới nhiều tổ chức FE:

```text
Một portal:
  /admin/...
  /chain/...
  /store/...

Nhiều portal:
  admin portal
  chain portal
  store portal
```

Backend không map máy móc theo folder FE. Chọn module/API prefix theo actor, permission, ownership dữ liệu, và bounded context.

Gợi ý:

- `Admin`: quản trị platform, master data, tenant provisioning.
- `Chain`: nghiệp vụ chuỗi/tenant owner.
- `Store`: nghiệp vụ cửa hàng/đơn vị vận hành.
- Module domain riêng nếu feature đủ lớn và dùng chung nhiều actor.

## 3. Xác Định Entity Model

Với mỗi entity, quyết định:

- Platform hay Tenant.
- Aggregate root hay child entity.
- Có API độc lập hay chỉ thao tác qua parent.
- Field nào fillable, field nào computed/read-only.
- Soft delete có cần không.

Platform entity:

- global/master data/config.
- model `App\Models\Platform\*`.
- migration trong `database/migrations/platform`.

Tenant entity:

- dữ liệu thuộc tenant.
- model `App\Models\Tenant\*`.
- migration trong `database/migrations/tenant`.

Pivot M-N:

- không tạo model.
- chỉ migration + `belongsToMany`.
- sync qua Action của aggregate cha.

## 4. Bóc Relationship

| Relationship | Khi nào dùng | Cách implement |
|---|---|---|
| `belongsTo` | Entity con giữ FK tới parent | FK trên child, validate exists |
| `hasOne` | Setting/profile 1-1 | `updateOrCreate` qua parent Action |
| `hasMany` | Child rows thuộc aggregate | sync/create/update qua parent Action nếu không độc lập |
| `belongsToMany` | Tag/role/category M-N | pivot migration only, `sync` qua parent Action |

Nếu logic chạm nhiều aggregate không có relationship trực tiếp, dùng Service.

## 4b. Service Hashtags

### `#call-external`

Dùng khi API gọi/nhận dữ liệu từ bên thứ ba: payment, webhook, SMS, shipping, OAuth, ERP.

- Spec thêm `tags: [call-external]` và `externalCalls`.
- OpenAPI thêm tag `call-external` và `x-external-calls`.
- Code sinh Service/integration client.
- Không đặt external HTTP call trong Action, Query, Controller, Request, Resource.

### `#cross-entity-service`

Dùng rất ít khi một API cần xử lý 2 entity nội bộ độc lập.

Trước khi dùng phải loại trừ:

1. Có Eloquent relationship → dùng parent Action/Query và relationship APIs.
2. Entity thứ 2 chỉ là side effect, FE không cần result → dùng Event/Observer/Job.
3. Có thể tách API an toàn → tách API.

Nếu vẫn cần synchronous orchestration, spec thêm `tags: [cross-entity-service]`, `services`, endpoint `serviceRefs`, và ghi `alternativesConsidered`.

Code vẫn có Action/Query cho API entry; Action/Query gọi Service cho phần orchestration.

## 5. Bóc Endpoint

Màn hình thường map như sau:

| UI need | Endpoint |
|---|---|
| List/table/search | `GET /{prefix}/{entities}` |
| Detail | `GET /{prefix}/{entities}/{id}` |
| Edit initial data | dùng lại detail |
| Create form submit | `POST /{prefix}/{entities}` |
| Edit form submit | `PUT /{prefix}/{entities}/{id}` hoặc `PATCH` |
| Delete | `DELETE /{prefix}/{entities}/{id}` |
| Dropdown/select | `POST /{prefix}/{entities}/select-items` |
| HasOne setting | `PUT /{prefix}/{entities}/{id}/{setting}` |
| Bulk action | endpoint riêng |
| Import/export | endpoint riêng, thường Service |
| Dashboard summary | tách theo block nếu lifecycle khác |

## 6. Khi Nào Reuse API

Reuse khi cùng shape, cùng permission, cùng lifecycle:

- detail page + edit form current data.
- create/edit form options dùng chung select-items.
- list API dùng cho table và simple search nếu field đủ.

Không reuse khi:

- dashboard block cần cache/refresh riêng.
- mobile/portal khác có field permission khác.
- endpoint trả nested data nặng làm list chậm.
- form cần data phụ không thuộc entity detail.

## 7. Request / Response Contract

Mỗi endpoint cần ghi:

- method/path/id/purpose.
- auth/permission.
- query params hoặc body.
- validation rules.
- response resource.
- error shape chính: 401/403/404/422.
- reusable mock sample.

Laravel 422 field errors phải align để FE map vào form.

## 8. Filter / Sort / Include

Search endpoint cần ghi rõ:

```yaml
query:
  pagination: true
  filters:
    - name
    - status
  sorts:
    - created_at
    - name
  includes:
    - company
```

Chỉ expose filter/sort/include thật sự cần cho UI hoặc stable API.

## 9. Definition Of Done Step 1

- Có `01-backend-spec.yaml`.
- Có `02-openapi.yaml`.
- Có `03-mock-data.yaml`.
- Member chạy `pnpm docs:render` → `generated/backend-spec.md` để review (agent không viết tay).
- Endpoint có purpose và reusedBy rõ.
- Entity mode và relationship đã được quyết định.
- Open questions được ghi lại, không bị giấu trong code.
