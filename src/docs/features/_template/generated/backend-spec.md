# Hotel Management Backend Spec

Example output for Step 1 `/api-spec`.

## Source

- Portal spec: `../portal/docs/features/hotel/spec.yaml`
- Portal testcases: `../portal/docs/features/hotel/testcases`

## Module Plan

| Module | Entity | Mode | Aggregate |
|---|---|---|---|
| Admin | Hotel | Platform | yes |

## Change Log

| ID | Summary | Breaking |
|---|---|---|
| `CHG-HOTEL-001` | Initial backend contract for hotel list, detail, and create. | no |

## Decisions

| ID | Decision | Reason |
|---|---|---|
| `DEC-HOTEL-001` | Reuse `hotel.detail` for detail page and edit initial form data. | Same permission, response shape, and lifecycle. |

## Endpoints

| ID | Method | Path | Purpose | Reused By |
|---|---|---|---|---|
| `hotel.search` | GET | `/admin/hotels` | List hotels | `/hotels` |
| `hotel.detail` | GET | `/admin/hotels/{id}` | Detail and edit current data | `/hotels/{id}`, `/hotels/{id}/edit` |
| `hotel.create` | POST | `/admin/hotels` | Create hotel | `/hotels/create` |

## Request

`HotelCreateRequest`

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, string, max:255 |

## Response

`HotelResource`

| Field | Type |
|---|---|
| `id` | integer |
| `name` | string |

## OpenAPI

- [Feature OpenAPI YAML](../02-openapi.yaml)
- Swagger UI after build: `/swagger/`

## Mock Data

- `hotel.search.success`
- `hotel.detail.success`
- `hotel.create.success`
- `hotel.create.validation`

## Open Questions

- Confirm whether Hotel is Platform or Tenant-owned in the real product.
