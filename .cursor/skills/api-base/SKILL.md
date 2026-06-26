---
name: api-base
description: >-
  Develop Laravel 12 API Base (module-first) in folder api: use m:* generators per
  make_help.md, Platform/Tenant entity modes, pivot M-N without model, Eloquent
  relationships in parent Action/Query, Services for unrelated cross-domain logic.
  Use when adding modules, models, controllers, migrations, actions, or API
  endpoints in the api base project (src/).
---

# API Base (Laravel 12 Module-First)

Repo: **`api/`** · Laravel root: **`src/`** · Chạy artisan từ `src/`.

**Docs:** `src/make_help.md` (bắt buộc), `src/docs/CONVENTIONS.md`, `src/docs/GENERATORS.md`

**Cursor rules:** `api-invariants.mdc`, `api-code-size.mdc`, `api-base-*.mdc` (legacy globs if any)

**Extracts:** `.cursor/extracts/` — entity, http-layer, codegen, media-s3, hashtags

---

## 0. Quy tắc vàng — dùng `make`, không scaffold tay

**Luôn đọc `src/make_help.md` trước khi tạo class.** Không tự tạo file Controller/Action/Query/Request/Resource/Model khi đã có lệnh `m:*`.

| Việc cần làm | Lệnh |
|--------------|------|
| Module mới | `php artisan m:module {Module}` |
| Model + migration | `php artisan m:model {Name} {Platform\|Tenant}` |
| CRUD đầy đủ | `php artisan m:controller {Module} {Entity} ...` |
| Thêm endpoint 1 action | `php artisan add:action {Module} {Entity} {create\|update\|delete\|search\|detail}` |
| Select-items API | `php artisan add:select-item {Module} {Entity}` |
| 1-1 setting endpoint | `php artisan m:add-createOrUpdate {Module} {Parent} {relation} {method}` |
| Auth API | `php artisan m:auth-api {Module} {Model}` |
| Sinh test | `php artisan m:module-test {Module}` |

Option: `yes/no`, `--skip-questions`, `--path-model=Platform|Tenant`, `--shared-model=yes`.

`m:model-module` — **deprecated**. Dùng `m:model`.

---

## 1. Layer architecture

```
HTTP Request
  → Controller (traits: EntryCreate/Update/Delete/Search/Detail)
      → Request (rules only)
      → Action (create/update/delete + relationship mutations)
      → Query (search/detail + filters/sorts/includes)
      → Resource (response shape)
  → Service (chỉ khi logic cross-domain / không có quan hệ Eloquent)
  → Model (App\Models\Platform|Tenant)
```

| Layer | Vai trò | Không làm |
|-------|---------|-----------|
| **Controller** | Thin wrapper, trait wiring | Business logic, query trực tiếp |
| **Request** | `rules()` | Authorization phức tạp (dùng base) |
| **Action** | Mutate DB, sync relations | HTTP, filter/search |
| **Query** | List/detail, `filters()`/`sorts()`/`includes()` | Create/update/delete |
| **Resource** | Map entity → JSON | Query DB |
| **Service** | Orchestration **không** qua relationship | Thay thế Action cho CRUD đơn giản |

Filter: `filter[field]=value` hoặc `{ "filter": { ... } }`.

---

## 1b. Kích thước code

| Giới hạn | Ngưỡng | Vượt thì |
|----------|--------|----------|
| **File / class** | ~200 dòng | Tách Action method, Service, Resource, Query concern |
| **Method** | ~20 dòng | Private methods: `transformPayload`, `syncXxx`, `applyFilters` |

Action/Query phình → Service (cross-domain) hoặc `Concerns/`. Rule: `api-code-size.mdc`.

---

## 2. Entity modes (Platform / Tenant / Pivot)

### Platform entity

- Model: `App\Models\Platform\{Name}` extends `PlatformModel`
- Migration: `src/database/migrations/platform/create_*_table.php`
- Connection: `platform`
- Generator: `m:model {Name} Platform` hoặc `m:controller ... --path-model=Platform`

### Tenant entity

- Model: `App\Models\Tenant\{Name}` extends `TenantModel`
- Migration: `src/database/migrations/tenant/create_*_table.php`
- Connection: `tentant`
- Generator: `--path-model=Tenant`
- Mỗi migration `create_*` phải có `$table->softDeletes()`

### Pivot M-N — **KHÔNG TẠO MODEL**

Bảng map M-N (vd `post_tag`, `role_user`, `product_category`):

| Làm | Không làm |
|-----|-----------|
| Migration `create_{pivot}_table.php` only | `m:model PostTag` |
| `belongsToMany` trên **2 model cha** | Controller/Action riêng cho pivot |
| `attach` / `sync` / `detach` / `withPivot` | Pivot Eloquent model class |

```php
// Post.php — parent Action gọi sync
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id')
        ->withPivot('sort_order');
}

// Action: $post->tags()->sync($payload);
// hoặc BaseAction::syncRelations($model, ['tags' => $ids])
```

### Child entity (hasMany / hasOne)

- **Có model** + migration (vd `order_items`, `article_sections`)
- Relationship trên **aggregate cha** (`Order`, `Article`, `Product`)
- Thao tác qua **Action của entity cha** hoặc nested load qua **Query `includes()`**
- Không tạo controller CRUD riêng trừ khi aggregate độc lập

### 1-1 setting (HasOne)

- Dùng `m:add-createOrUpdate` hoặc Action method: `$parent->profile()->updateOrCreate(...)`
- Parent phải khai báo `HasOne` relationship (lowerCamelCase method name)

---

## 3. Relationship vs Service — quyết định

### Có quan hệ Eloquent → Action/Query của entity cha

| Kiểu | Xử lý ở đâu | API |
|------|-------------|-----|
| belongsToMany (pivot) | Parent **Action** | `sync`, `attach`, `detach` |
| hasMany / hasOne child | Parent **Action** | `createMany`, `update`, `syncRelations` |
| belongsTo | Child Action hoặc parent payload | FK trên child |
| Eager load nested | Parent **Query** | `includes()` |

```php
// OrderAction — child qua relationship, không Service riêng cho từng row
$order->items()->delete();
$order->items()->createMany($rows);
```

`BaseAction::syncRelations()` hỗ trợ payload `{ relationName => value }` cho belongsToMany/hasMany/hasOne.

### Không có quan hệ trực tiếp → tách Service

Dùng `Modules/{Module}/Services/{Name}Service.php` khi:

- Orchestration **2+ aggregate không liên kết** qua Eloquent relationship
- External integration (webhook, CSV import, OAuth, email parser)
- Logic dùng chung nhiều Action/Job/Command

```php
// OrderAction inject Service — gửi email + cập nhật inventory không qua 1 relationship
public function __construct(
    Order $model,
    private readonly OrderFulfillmentService $fulfillment,
) { ... }
```

**Không** tạo Service cho thao tác pivot M-N đơn giản — dùng relationship trên Action.

---

## 4. Module layout

```
src/Modules/{Module}/
├── Http/Controllers/   # extends {Module}Controller
├── Http/Requests/
├── Http/Actions/       # extends {Module}Action extends BaseAction
├── Http/Queries/       # extends {Module}Query
├── Http/Resources/
├── Routes/api.php
├── Services/           # cross-domain only
└── Tests/
```

App models: `src/app/Models/Platform/`, `src/app/Models/Tenant/`.

---

## 5. Workflow thêm entity mới

### A. Aggregate CRUD (có controller)

```bash
cd src
php artisan m:controller Admin Product \
  --shared-model=yes --path-model=Platform \
  --create-request=yes --search-request=yes \
  --action-class=yes --query-class=yes --resource-class=yes \
  --wire-create=yes --wire-update=yes --wire-delete=yes \
  --wire-search=yes --wire-detail=yes \
  --skip-questions
```

### B. Pivot M-N only

1. `php artisan m:model` — **KHÔNG** (không tạo model pivot)
2. Tạo migration tay trong `database/migrations/tenant/` (hoặc platform)
3. Thêm `belongsToMany` trên 2 model cha
4. Xử lý sync trong **parent Action** (Post, User, Product…)

### C. Child nested (hasMany)

1. `php artisan m:model OrderItem Tenant --create-migration=yes --skip-questions`
2. Relationship trên parent model
3. Sync trong parent Action — **không** scaffold controller child trừ khi cần API độc lập

### D. Sau scaffold

1. Port/điền migration schema
2. Khai báo `fillable`, relationships trên model
3. Custom `Action::create/update` + `transformPayload` nếu có nested data
4. `Query::includes()` cho nested response
5. `php artisan m:module-test {Module} --type=all`

---

## 6. S3 / media — chỉ lưu path

Upload/logo/ảnh lưu S3 hoặc public disk: **DB chỉ giữ path**, không full URL.

| Giai đoạn | Cách làm |
|-----------|----------|
| **Ghi DB** (Action) | Normalize path — strip domain nếu FE gửi full URL |
| **Trả API** (Resource) | Ghép domain từ config → field `*_url` |
| **Domain** | `.env`: `PUBLIC_ASSET_BASE_URL`, `AWS_URL` — không hardcode trong code/DB |

```php
// Action — persist path only
$attributes['logo_path'] = PublicAssetUrl::normalizeForStorage($request->input('logo_path'));
// hoặc: ltrim(parse_url($url, PHP_URL_PATH), '/')

// Resource — expose
'logo_url' => PublicAssetUrl::url($this->logo_path),
// hoặc: rtrim(config('public_assets.base_url'), '/').$this->logo_path
```

Ngoại lệ: URL bên thứ ba (redirect ngoài, form external) — giữ full URL.

---

## 7. Checklist review

- [ ] Class sinh bằng `m:*` / `add:*`, không file tay trùng convention
- [ ] Pivot M-N: migration only, không model class
- [ ] Related data: relationship trên Action/Query cha, không Service thừa
- [ ] Unrelated cross-domain: Service, không nhét vào Action
- [ ] `--path-model` đúng Platform vs Tenant
- [ ] Migration đúng thư mục + `softDeletes`
- [ ] Media/S3: path trong DB, URL ghép từ `.env` khi response
- [ ] File ~≤200 dòng, method ~≤20 dòng — tách khi vượt
- [ ] Test: `m:module-test` hoặc test file generator sinh ra

**Chi tiết:** [reference.md](reference.md) · **make_help:** `src/make_help.md`
