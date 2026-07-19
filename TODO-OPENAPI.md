# TODO — Move OpenAPI SSOT to base-docs

OpenAPI **source** không còn thuộc repo `api`. Theo cấu trúc mới: contract SSOT
(api spec + `02-openapi.yaml` + `03-mock-data.yaml`) sống trên **base-docs**
(docs hub). Repo `api` chỉ giữ **code API thật** (Laravel `src/`). Codegen/unitgen/
`registries/` do Codegenkit sync khi `init` (gitignored, không commit); bản PHP
unitgen xem `codegenkit/TODO-UNITGEN-LARAVEL.md`. Bundle/preview/swagger là build
artifact, không commit.

Đã xoá bên `api` (nội dung giữ lại bên dưới để không mất):
- `openapi/base.yaml`, `openapi/api.yaml`, `openapi/index.md`
- `public/openapi/`, `public/swagger/` (build output)
- `package.json` scripts `openapi:*` + devDeps `@redocly/cli`, `swagger-ui-dist`,
  `mermaid`, `dayjs`, `debug`, `@braintree/sanitize-url`

## Cần làm trên base-docs (tôi sẽ sang xử lý)

- [ ] Chốt vị trí OpenAPI. **Đề xuất: per-feature** cạnh api spec —
      `product/**/code/API-*/02-openapi.yaml`.
      Lý do: unitgen resolve `02-openapi.yaml` **cùng thư mục** với
      `01-backend-spec.yaml` (`resolveOpenApiPath` = `dirname(specFile)/02-openapi.yaml`).
      Đặt
      per-feature là chạy được ngay, không cần sửa runner.
- [ ] Health/base fragment: đưa vào catalog chung
      `product/shared/api-catalog/` (hiện chỉ có `index.md`) hoặc feature
      `common`. Nội dung gốc giữ ở mục "Preserved" dưới.
- [ ] Template đã có sẵn trên base-docs: `templates/api/openapi.yaml` — dùng làm
      chuẩn khi author `02-openapi.yaml`.
- [ ] Xác nhận `/api-spec` (Codegenkit) ghi `02-openapi.yaml` vào feature folder
      trên hub (SSOT), repo `api` không giữ bản gốc.
- [ ] Bundle/serve (Redocly/Swagger) nếu vẫn cần preview: đặt tooling ở docs hub
      (đọc per-feature `02-openapi.yaml`), không đặt lại ở `api`.

## Ảnh hưởng phía code (không chặn)

- `unitgen` openapi-shape test vẫn hoạt động vì đọc openapi cạnh `--spec` (hub).
  Chỉ cần đảm bảo `02-openapi.yaml` nằm cạnh `01-backend-spec.yaml`.
- Test PHP giữ nguyên: `AssertsResourceOpenApiKeys`,
  `HotelResourceOpenApiShapeBehaviorTest` — assert shape, không phụ thuộc vị trí
  file nguồn.

---

## Preserved — `openapi/base.yaml` (health fragment)

```yaml
openapi: 3.1.0
info:
  title: API Base
  version: 0.1.0
  license:
    name: Proprietary
    identifier: LicenseRef-Proprietary
  description: >
    Base OpenAPI fragment (health).
servers:
  - url: http://api.base.local
    description: Local API server
tags:
  - name: Health
    description: Local documentation smoke endpoint.
paths:
  /health:
    get:
      operationId: healthCheck
      summary: Health check placeholder
      tags:
        - Health
      security: []
      responses:
        '200':
          description: API is reachable
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/HealthResponse'
              example:
                status: ok
        '500':
          description: Unexpected server error
        '404':
          description: Endpoint not found
components:
  schemas:
    HealthResponse:
      type: object
      required:
        - status
      properties:
        status:
          type: string
          example: ok
```

## Preserved — feature fragment đã có (chain/hotel)

`openapi/api.yaml` từng merge `docs/features/chain/hotel/02-openapi.yaml` với tag
`ChainHotels` (path `/health` + chain hotel list). Bản gốc feature nằm ở base-docs
theo feature folder; nếu cần khôi phục, xem git history `openapi/api.yaml`.
