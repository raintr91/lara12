# Make Command Help

Tài liệu này tổng hợp các command generator chính trong dự án, cách dùng, option quan trọng và ví dụ thực tế.

## Mục Lục

1. [Tóm tắt nhanh](#tóm-tắt-nhanh)
2. [Quy ước chung](#quy-ước-chung)
3. [Giá trị yes/no](#giá-trị-yesno)
4. [Chi tiết từng command](#chi-tiết-từng-command)
5. [Workflow gợi ý](#workflow-gợi-ý)
6. [Lưu ý](#lưu-ý)

## Tóm tắt nhanh

| Command | Mục đích | Ghi chú |
| --- | --- | --- |
| `m:module` | Tạo module mới | Sinh base classes cho module |
| `m:model` | Tạo app model | Có thể sinh migration/factory/seeder |
| `rm:model` | Xóa app model đã generate | Xóa model + migration + factory + seeder + model test |
| `m:model-module` | Tạo module model | Deprecated |
| `m:request` | Tạo module request | Kế thừa `<Module>Request` |
| `m:action` | Tạo module action | Kế thừa `<Module>Action` |
| `m:query` | Tạo module query | Có thể sinh criteria |
| `m:criteria` | Tạo module criteria | Kế thừa `<Module>Criteria` |
| `m:middleware` | Tạo module middleware | Kế thừa `<Module>Middleware` |
| `m:resource` | Tạo module resource | Kế thừa `<Module>Resource` |
| `m:controller` | Tạo controller wizard | Có nhiều bước hỏi và wiring tự động |
| `m:module-test` | Tạo test theo từng class/layer | Controller -> Feature, layer khác -> Unit |
| `m:auth-api` | Tạo auth API scaffold | Sinh controller/routes/requests auth |

## Quy ước chung

1. Tên module dùng dạng `StudlyCase`.
2. Hầu hết command module sẽ tự chuẩn hóa suffix.
3. Một số command có chế độ hỏi `y/n` tương tác.
4. Nếu truyền trước option `yes/no`, command sẽ lấy luôn giá trị đó và không hỏi lại bước tương ứng.
5. Nếu thêm `--skip-questions`, các bước chưa truyền option sẽ không hỏi nữa mà dùng giá trị mặc định.

### Ví dụ chuẩn hóa suffix

```text
Robot + m:request -> RobotRequest
User + m:action  -> UserAction
User + m:query   -> UserQuery
```

## Giá trị yes/no

### Giá trị hợp lệ

```text
yes, y, 1, true
no, n, 0, false
```

## Chi tiết từng command

## `m:module`

### 📌 Command

```bash
php artisan m:module {ModuleName}
```

### 📖 Mục đích

Tạo module mới và sinh các abstract base class cho module như controller, middleware, request, resource, action, query.

Mặc định sau khi tạo module:

- Xóa `Tests/Feature/ExampleTest.php` và `Tests/Unit/ExampleTest.php`
- Sinh `Tests/Feature/ModuleRouteFilesTest.php`
- Sinh `Tests/Feature/RouteRegistrationSmokeTest.php`

### ⚙️ Options

```bash
--force
--disabled
--plain
--api
--with-web
```

### ▶️ Examples

```bash
php artisan m:module Admin
php artisan m:module Report --with-web
php artisan m:module Cms --force --disabled
```

## `m:model`

### 📌 Command

```bash
php artisan m:model {Name}
```

### 📖 Mục đích

Tạo model app trong `app/Models` và có thể tạo thêm migration, factory, seeder.

Ngoài ra command sẽ luôn tạo test riêng cho từng model tại:

- `tests/Unit/Models/<Model>ModelTest.php`

### ⚙️ Options

```bash
--force
--create-model=yes|no
--create-migration=yes|no
--create-factory=yes|no
--create-seeder=yes|no
--create-another-migration=yes|no
--skip-questions
```

### ▶️ Examples

```bash
php artisan m:model Robot
php artisan m:model Robot --create-migration=yes --create-factory=yes --create-seeder=no
php artisan m:model Robot --create-model=yes --create-migration=yes --create-factory=no --create-seeder=no --skip-questions
php artisan m:model Robot --create-model=yes --create-migration=no --create-factory=no --create-seeder=no --skip-questions
```

## `rm:model`

### 📌 Command

```bash
php artisan rm:model {Name}
```

### 📖 Mục đích

Xóa toàn bộ file thường được tạo bởi `m:model` cho một app model:

- `app/Models/<Model>.php`
- `tests/Unit/Models/<Model>ModelTest.php`
- `database/factories/<Model>Factory.php`
- `database/seeders/<Model>Seeder.php`
- `database/migrations/*_create_<table>_table.php`

### ⚙️ Options

```bash
--yes
```

### ▶️ Examples

```bash
php artisan rm:model Robot
php artisan rm:model Robot --yes
```

## `m:model-module`

> Deprecated. Ưu tiên dùng `m:model` cho app model.

### 📌 Command

```bash
php artisan m:model-module {Module} {Name}
```

### 📖 Mục đích

Tạo model trong module.

### ⚙️ Options

```bash
--force
--migration
--factory
--seeder
--create-model=yes|no
--create-migration=yes|no
--create-factory=yes|no
--create-seeder=yes|no
--create-another-migration=yes|no
--factory-file=yes|no
--seeder-file=yes|no
--skip-questions
```

### ▶️ Examples

```bash
php artisan m:model-module Admin Robot
php artisan m:model-module Admin Robot --factory --seeder
php artisan m:model-module Admin Robot --create-model=yes --create-factory=yes --factory-file=yes --create-seeder=no --skip-questions
```

## `m:request`

### 📌 Command

```bash
php artisan m:request {Module} {Name}
```

### 📖 Mục đích

Tạo request trong module, kế thừa base request của module.

### ⚙️ Options

```bash
--force
```

### ▶️ Examples

```bash
php artisan m:request Admin RobotCreate
php artisan m:request Admin RobotSearch --force
```

## `m:action`

### 📌 Command

```bash
php artisan m:action {Module} {Name}
```

### 📖 Mục đích

Tạo action trong module, kế thừa base action của module.

### ⚙️ Options

```bash
--force
```

### ▶️ Examples

```bash
php artisan m:action Admin Robot
php artisan m:action Admin CreateUserAction --force
```

## `m:query`

### 📌 Command

```bash
php artisan m:query {Module} {Name}
```

### 📖 Mục đích

Tạo query trong module, và có thể tạo thêm criteria tương ứng.

### ⚙️ Options

```bash
--force
--with-criteria
--create-criteria=yes|no
--skip-questions
```

### ▶️ Examples

```bash
php artisan m:query Admin Robot
php artisan m:query Admin Robot --with-criteria
php artisan m:query Admin Robot --create-criteria=yes --skip-questions
```

## `m:criteria`

### 📌 Command

```bash
php artisan m:criteria {Module} {Name}
```

### 📖 Mục đích

Tạo criteria trong module, kế thừa base criteria của module.

### ⚙️ Options

```bash
--force
```

### ▶️ Examples

```bash
php artisan m:criteria Admin Robot
php artisan m:criteria Admin RobotStatus --force
```

## `m:module-test`

### 📌 Command

```bash
php artisan m:module-test {Module}
```

### 📖 Mục đích

Sinh test nhỏ theo từng class trong module, phân tầng đúng chuẩn:

- `Controller` -> `Tests/Feature/Http/Controllers/*Test.php`
- `Action|Query|Request|Resource` -> `Tests/Unit/Http/.../*Test.php`

Nếu muốn controller test ở Unit để test độc lập, dùng `--controller-layer=unit`.

Lưu ý về model dùng chung:

- Model ở `App\\Models\\...` đặt test tại `tests/Unit/Models/*`
- Không đặt app model test trong `Modules/*/Tests` để tránh coupling theo module

### ⚙️ Options

```bash
--type=all|controller|action|query|request|resource
--class=
--force
--include-base
--controller-layer=feature|unit
```

### ▶️ Examples

```bash
php artisan m:module-test Admin
php artisan m:module-test Admin --type=controller
php artisan m:module-test Admin --type=controller --controller-layer=unit
php artisan m:module-test Admin --type=action --class=CreateUser
php artisan m:module-test Admin --type=all --force
```

## `m:middleware`

### 📌 Command

```bash
php artisan m:middleware {Module} {Name}
```

### 📖 Mục đích

Tạo middleware trong module.

### ⚙️ Options

```bash
--force
```

### ▶️ Examples

```bash
php artisan m:middleware Admin Audit
php artisan m:middleware Admin AuditMiddleware --force
```

## `m:resource`

### 📌 Command

```bash
php artisan m:resource {Module} {Name}
```

### 📖 Mục đích

Tạo resource trong module.

### ⚙️ Options

```bash
--force
```

### ▶️ Examples

```bash
php artisan m:resource Admin Robot
php artisan m:resource Admin UserResource --force
```

## `m:controller`

### 📌 Command

```bash
php artisan m:controller {Module} {Name}
```

### 📖 Mục đích

Tạo controller module và có thể sinh thêm request, action, query, resource, shared model, wiring action trait và select-items API.

### ⚙️ Options điều khiển từng bước

```bash
--create-request=yes|no
--search-request=yes|no
--action-class=yes|no
--query-class=yes|no
--resource-class=yes|no
--shared-model=yes|no
--overwrite-controller=yes|no
--wire-create=yes|no
--wire-update=yes|no
--wire-delete=yes|no
--wire-search=yes|no
--wire-detail=yes|no
--select-items=yes|no
--skip-questions
```

### ▶️ Examples

```bash
php artisan m:controller Admin Robot
php artisan m:controller Admin Robot --create-request=yes --search-request=yes --action-class=yes --query-class=yes
php artisan m:controller Admin Robot --wire-create=yes --wire-update=yes --wire-delete=yes --wire-search=yes --wire-detail=yes --select-items=yes --skip-questions
php artisan m:controller Admin Robot --create-request=no --search-request=no --resource-class=no --shared-model=no --skip-questions
```

## `m:auth-api`

### 📌 Command

```bash
php artisan m:auth-api {Module} {Model}
```

### 📖 Mục đích

Sinh auth controller, auth routes, auth requests và bảo đảm có Authenticatable model trong `App\\Models`.

### ⚙️ Options

```bash
--force
--auth-middleware=auth:sanctum
--auth-controller=yes|no
--auth-routes=yes|no
--register-request=yes|no
--login-request=yes|no
--forgot-request=yes|no
--reset-request=yes|no
--change-password-request=yes|no
--authenticatable-model=yes|no
--skip-questions
```

### ▶️ Examples

```bash
php artisan m:auth-api Admin User
php artisan m:auth-api Admin UserModel --auth-middleware=auth
php artisan m:auth-api Admin User --auth-controller=yes --auth-routes=yes --register-request=yes --login-request=yes --forgot-request=yes --reset-request=yes --change-password-request=yes --skip-questions
```

## Workflow gợi ý

### 1. Tạo module mới

```bash
php artisan m:module Admin
```

### 2. Tạo app model

```bash
php artisan m:model Robot --create-migration=yes --create-factory=yes --skip-questions
```

### 3. Tạo controller CRUD đầy đủ

```bash
php artisan m:controller Admin Robot \
  --create-request=yes \
  --search-request=yes \
  --action-class=yes \
  --query-class=yes \
  --resource-class=yes \
  --shared-model=no \
  --wire-create=yes \
  --wire-update=yes \
  --wire-delete=yes \
  --wire-search=yes \
  --wire-detail=yes \
  --select-items=yes \
  --skip-questions
```

### 4. Tạo auth API cho module

```bash
php artisan m:auth-api Admin User --auth-controller=yes --auth-routes=yes --skip-questions
```

## Lưu ý

1. Nếu truyền option y/n từ đầu, command sẽ không hỏi lại bước đó.
2. Nếu không truyền option, command sẽ hỏi như cũ.
3. Nếu thêm `--skip-questions`, mọi bước chưa truyền option sẽ dùng default của bước đó và không hỏi nữa.
4. Với các command không có option y/n riêng, hành vi vẫn giữ đơn giản như cũ.
