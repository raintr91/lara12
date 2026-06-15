# API Base — Reference

Bổ sung cho [SKILL.md](SKILL.md). Repo `api/`, Laravel `src/`.

---

## make_help — lệnh thường dùng

Chạy từ `api/src/`:

```bash
# Module
php artisan m:module Admin

# Model
php artisan m:model Product Platform --create-migration=yes --create-factory=yes --skip-questions
php artisan m:model OrderItem Tenant --create-migration=yes --skip-questions

# CRUD wizard
php artisan m:controller Admin Product \
  --shared-model=yes --path-model=Platform \
  --create-request=yes --search-request=yes \
  --action-class=yes --query-class=yes --resource-class=yes \
  --wire-create=yes --wire-update=yes --wire-delete=yes \
  --wire-search=yes --wire-detail=yes --select-items=yes \
  --skip-questions

# Bổ sung endpoint
php artisan add:action Admin Product search
php artisan add:select-item Admin Product

# 1-1 setting
php artisan m:add-createOrUpdate Admin User profile updateProfile

# Auth
php artisan m:auth-api Admin User --skip-questions

# Tests
php artisan m:module-test Admin --type=all
```

Yes/no: `yes|y|1|true` · `no|n|0|false`

---

## Entity decision tree

```
Bảng mới?
├── Chỉ map M-N giữa 2 entity có sẵn?
│   └── YES → migration pivot ONLY + belongsToMany (NO model, NO controller)
├── Child 1-N / 1-1 của aggregate cha?
│   └── YES → m:model + relationship trên cha + sync trong cha Action
├── Aggregate độc lập (CRUD API riêng)?
│   └── YES → m:controller + --path-model=Platform|Tenant
└── Read-only từ hệ thống cũ?
    └── Model Legacy ở project con (saas-api), không thêm vào api base
```

---

## Pivot M-N examples (generic)

| Pivot table | Parents | Model? |
|-------------|---------|--------|
| `post_tag` | Post ↔ Tag | **No** |
| `role_user` | User ↔ Role | **No** |
| `product_category` | Product ↔ Category | **No** |

```php
// Post.php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id')
        ->withPivot('sort_order');
}
```

---

## Relationship patterns in Action

### belongsToMany via BaseAction

```php
return $this->createModel($attributes, ['tags' => $pivotPayload]);
// BaseAction::syncRelations → $model->tags()->sync($value)
```

### hasMany manual sync

```php
private function syncItems(Order $order, array $rows): void
{
    $order->items()->delete();
    if ($rows !== []) {
        $order->items()->createMany($rows);
    }
}
```

### HasOne updateOrCreate (setting)

```php
public function updateProfile(int $userId, array $data): mixed
{
    $user = $this->model::query()->findOrFail($userId);
    return $user->profile()->updateOrCreate(['user_id' => $userId], $data);
}
```

### Query eager load

```php
public function includes(): array
{
    return ['customer', 'items', 'items.product'];
}
```

---

## S3 / public assets

**Quy tắc:** DB lưu path/key — domain ghép lúc response.

| Giai đoạn | Pattern |
|-----------|---------|
| Ghi DB | Path tương đối (`/uploads/1/logo.png`, `assets/foo.png`) |
| Trả API | `config('public_assets.base_url')` + path → `*_url` |
| Strip input | Bỏ `PUBLIC_ASSET_BASE_URL` / `AWS_URL` nếu FE gửi full URL |

**.env (project con có thể thêm `config/public_assets.php`)**

```env
PUBLIC_ASSET_BASE_URL=https://cdn.example.com
AWS_URL=https://bucket.s3.amazonaws.com
FILESYSTEM_DISK=s3
```

Project con (vd saas-api) có thể dùng helper `App\Support\Media\PublicAssetUrl` — api base quy định convention, implement helper khi cần.

---

## When to use Service (unrelated models)

| Case | Example |
|------|---------|
| External webhook/CSV | `CsvImportService` |
| OAuth/token bên thứ ba | `OAuthTokenService` |
| Cross-aggregate orchestration | `OrderFulfillmentService` |
| Gửi notification đa kênh | `NotificationDispatchService` |

**Rule:** Nếu 2 model **có** relationship đã khai báo → **không** tạo Service chỉ để attach/sync — dùng Action cha.

---

## DB connections

| Connection | Migration path | Models |
|------------|----------------|--------|
| `platform` | `src/database/migrations/platform/` | `App\Models\Platform\*` |
| `tentant` | `src/database/migrations/tenant/` | `App\Models\Tenant\*` |

---

## Controller trait wiring

```php
class ProductController extends AdminController
{
    use EntryCreateTrait, EntryUpdateTrait, EntryDeleteTrait;
    use EntrySearchTrait, EntryDetailTrait;

    public function create(ProductCreateRequest $request, ProductAction $action)
    {
        return $this->entryCreate($request, $action);
    }
}
```

---

## Test locations

| Class | Test path |
|-------|-----------|
| Controller | `src/Modules/{M}/Tests/Feature/Http/Controllers/` |
| Action/Query/Request/Resource | `src/Modules/{M}/Tests/Unit/Http/...` |
| App model | `src/tests/Unit/Models/` (không trong Modules/Tests) |

---

## Docs index

| File | Nội dung |
|------|----------|
| `src/make_help.md` | Full command reference |
| `src/docs/CONVENTIONS.md` | Controller/Request/Query patterns |
| `src/docs/GENERATORS.md` | Wizard, select-items, createOrUpdate |
