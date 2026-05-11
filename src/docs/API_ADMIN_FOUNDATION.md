# Admin API Foundation

Tai lieu nay chot convention cho dot migrate Admin dau tien.

## Response contract

Success:

```json
{
  "success": true,
  "code": 200,
  "message": "Success",
  "data": {},
  "meta": null,
  "trace_id": "..."
}
```

Error:

```json
{
  "success": false,
  "code": 422,
  "error": "Validation Error",
  "message": "Invalid input data",
  "errors": {},
  "trace_id": "...",
  "debug": null
}
```

## Search contract

- Pagination: `page`, `per_page`
- Sort uu tien: `sort=-created_at`
- Tuong thich route cu: `order_by=created_at&sorted_by=desc`
- Filter: `filter[field]=value`

Search response tra ve `data` la mang item, `meta.pagination` chua thong tin phan trang.

## Admin module wave 1

- Dashboard
- Users
- Countries
- Hotels
- Chains
- Otas

Muc tieu dot nay la dung duoc CRUD API va migration tong hop ban dau, chua thay the toan bo business flow cua project cu.