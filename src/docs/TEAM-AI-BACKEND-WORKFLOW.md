# Team AI Backend Workflow

Backend là phase 3b của team flow, nhưng chia thành 2 bước nhỏ để giảm rủi ro:

```text
Portal phase 1 spec/testcases
  ↓
Step 1: /api-spec
  Backend contract + OpenAPI YAML + mock data
  ↓ review/comment/chỉnh spec
Step 2: /api-code
  Laravel implementation theo spec đã chốt
```

Step 1 không code Laravel. Step 2 không tự thiết kế lại contract nếu chưa có review.

## Input Từ Portal

Backend agent đọc từ portal:

```text
../portal/docs/features/{slug}/
├── spec.yaml
├── testcases/*.yaml
└── generated/
```

`spec.yaml` và testcase YAML giúp hiểu requirement, route, auth, mock endpoint, form behavior, validation, empty/error/permission cases.

## Step 1: Backend API Spec

Mục tiêu: bóc tách requirement thành API contract để FE/BE review trước khi code.

Agent làm:

1. Đọc portal `spec.yaml`, `testcases/*.yaml`, generated Markdown nếu có.
2. Phân tích cụm chức năng, không chỉ từng màn riêng lẻ.
3. Xác định module/API prefix theo actor và portal layout: `/admin`, `/chain`, `/store`, hoặc portal riêng.
4. Xác định entity/model: Platform/Tenant, aggregate root, child entity, pivot M-N.
5. Bóc relationship: `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`.
6. Bóc endpoint cần có: search/detail/create/update/delete/select-items/summary/bulk/import/export.
7. Quyết định API nào dùng lại được và API nào phải tách riêng.
8. Xác định request/response, validation, filter/sort/include, permission.
9. Sinh backend spec YAML, OpenAPI YAML, mock request/response data.
10. Sinh Markdown review cho VitePress.

Output:

```text
docs/features/{slug}/
├── 01-backend-spec.yaml
├── 02-openapi.yaml
├── 03-mock-data.yaml
└── generated/
    └── backend-spec.md
```

Sau Step 1, team review/comment/chỉnh contract. Chưa scaffold code.

## Spec Evolution Khi Team Làm Song Song

FE có thể tiếp tục chỉnh `portal/docs/features/{slug}/spec.yaml` trong khi BE đang hoặc đã nhận spec cũ. Khi đó backend agent **không tạo feature spec mới** nếu thay đổi vẫn thuộc cùng cụm chức năng/bounded context.

Ví dụ:

```text
Admin quản lý chain
  ↓ BE đã có backend spec chain
FE phát hiện cần thêm child function setting chain
  ↓
BE update spec chain cũ, không tạo feature setting-chain riêng nếu setting thuộc aggregate Chain
```

Quy tắc update:

1. Đọc lại portal `spec.yaml` và `testcases/*.yaml` mới nhất.
2. Đọc backend spec hiện có: `docs/features/{slug}/01-backend-spec.yaml`.
3. So sánh requirement/testcase mới với backend spec cũ.
4. Thêm `changeLog` entry mô tả thay đổi, nguồn FE, ngày, và impact.
5. Update entity/relationship/endpoints/OpenAPI/mock data liên quan.
6. Giữ endpoint cũ nếu đã được FE/BE dùng, trừ khi changeLog ghi rõ breaking change được duyệt.
7. Ghi `openQuestions` nếu thay đổi FE chưa đủ dữ liệu để quyết định model/API.

Chỉ tạo spec mới khi:

- Đây là bounded feature mới, không thuộc aggregate/module cũ.
- Actor/portal/prefix hoàn toàn khác và contract độc lập.
- Cần API version mới vì breaking change lớn đã được duyệt.
- User yêu cầu tách feature spec riêng.

Nếu FE thêm child function thuộc aggregate cũ, backend spec cũ phải được mở rộng:

```text
Chain
├── search/detail/create/update
└── setting chain
    ├── relationship hasOne/hasMany
    ├── setting endpoint
    ├── request/response
    └── mock data
```

## Step 2: Backend Code

Mục tiêu: implement Laravel API Base theo spec đã chốt.

Agent làm:

1. Đọc `docs/features/{slug}/01-backend-spec.yaml`.
2. Đọc `src/make_help.md` bắt buộc.
3. Dùng generator `m:*` / `add:*`, không scaffold tay khi generator hỗ trợ.
4. Tạo module/model/migration/controller/request/action/query/resource theo spec.
5. Implement model fillable + relationships.
6. Implement request validation.
7. Implement query filters/sorts/includes.
8. Implement action create/update/delete + relationship sync.
9. Implement resource response đúng OpenAPI contract.
10. Chạy verify liên quan.

Code vẫn follow API Base:

```text
Controller → Request / Action / Query / Resource → Model
```

Service chỉ dùng cho logic cross-domain hoặc external integration, không thay Action cho CRUD/relationship đơn giản.

## Endpoint Reuse Rules

- Detail API dùng lại cho màn detail và edit current data.
- Select/autocomplete dùng `select-items`, không dùng list full.
- Summary/dashboard không mặc định gom một endpoint lớn.
- Tách endpoint khi block có permission, cache, refresh rate, source data, pagination, hoặc lifecycle khác.
- Pivot M-N không có model, sync qua relationship trên Action cha.
- Child hasMany/hasOne có model nếu là entity thật, nhưng thao tác qua aggregate cha nếu không độc lập.

## Swagger / OpenAPI

OpenAPI được viết bằng YAML, không dùng decorator/annotation trong Laravel.

Local tooling:

```bash
pnpm openapi:lint
pnpm openapi:bundle
pnpm openapi:preview
pnpm swagger:dev
```

- `openapi:lint`: kiểm tra YAML bằng Redocly.
- `openapi:bundle`: bundle YAML ra `docs/public/openapi/openapi.yaml`.
- `openapi:preview`: xem Redoc preview local.
- `swagger:dev`: build Swagger UI static từ YAML rồi mở VitePress docs.
