# Laravel 12 Module Base

Base project cho kiến trúc module-first với bộ generator command tùy biến cho controller, request, action, query, resource, auth API và các thành phần liên quan.

## Quick Links

- Quy ước code: [docs/CONVENTIONS.md](docs/CONVENTIONS.md)
- Generator overview: [docs/GENERATORS.md](docs/GENERATORS.md)
- Make command full help: [make_help.md](make_help.md)
- Cursor AI (skill + rules): [../.cursor/skills/api-base/SKILL.md](../.cursor/skills/api-base/SKILL.md)

## Highlights

- CRUD controller dùng các trait đơn nhiệm như `EntryCreateTrait`, `EntryUpdateTrait`, `EntryDeleteTrait`, `EntrySearchTrait`, `EntryDetailTrait`.
- Response/error contract đã được gom về base layer dùng chung.
- Query hỗ trợ `filter[field]=value` hoặc payload dạng `{filter:{...}}`.
- Có hạ tầng `select-items API` cho dữ liệu đổ vào UI select box.
- Có wizard command để scaffold controller và auth API theo convention của repo.

## Core Commands

```bash
php artisan m:module Admin
php artisan m:model User
php artisan m:controller Admin User
php artisan m:auth-api Admin User
```

Xem đầy đủ cú pháp, option `yes|no`, và `--skip-questions` trong [make_help.md](make_help.md).

## Auth Notes

Dự án đang dùng Bearer token auth qua Laravel Sanctum.

### Cài Sanctum

```bash
composer require laravel/sanctum:^4.1
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Yêu cầu

- Model authenticatable cần dùng `Laravel\Sanctum\HasApiTokens`.
- Protected routes của module Admin đang dùng `auth:sanctum`.
- Có thể override middleware khi scaffold auth API bằng `--auth-middleware=auth`.

## Troubleshooting

### Login trả về token = null

Nguyên nhân thường gặp:

1. Sanctum chưa được cài.
2. Model chưa dùng `HasApiTokens`.
3. Migration `personal_access_tokens` chưa chạy.

Kiểm tra nhanh:

```bash
php artisan tinker
App\Models\User::first()->createToken('api')->plainTextToken
```

## Project Notes

Repo này ưu tiên tài liệu thực dụng theo convention nội bộ thay vì giữ nguyên phần mô tả mặc định của Laravel.
