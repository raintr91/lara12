# DB Final Schema Consolidation (Tasks 77-81)

## Muc tieu
- Gom migration theo huong `1 create migration / 1 table`.
- Tao bo migration baseline cuoi cung de khoi tao schema moi truoc khi import du lieu cu.
- Tach bo migration baseline khoi migration lich su de de van hanh.

## Ket qua
- Tao bo migration moi tai `database/migrations_final`.
- Tong so file migration baseline: `50`.
- Khong con migration `Schema::table(...)` trong bo baseline.
- Da merge `users` schema theo trang thai cuoi (gom cacs cot tu migration alter users).
- Da tach cac migration da-bang thanh don-bang:
  - `users`, `password_reset_tokens`, `sessions`
  - `cache`, `cache_locks`
  - `jobs`, `job_batches`, `failed_jobs`
   - `ms_api_systems`, `hotel_api`

## Pham vi migration baseline
- Nguon: migration hien tai trong `database/migrations` cua API.
- Khong su dung migration nested loi `database/migrations/2026_04_03_060928_create_auth/_store_two_factor_auths_table.php`.
- Su dung migration hop le `create_store_two_factor_auths_table.php`.

## Cach dung
1. Backup DB hien tai.
2. Tao schema moi tu baseline:
   - `php artisan migrate:fresh --path=database/migrations_final --force`
3. Import du lieu DB cu vao schema moi bang quy trinh ETL/import cua team.
4. Chay script verify:
   - `bash scripts/verify_final_schema.sh`

## Luu y import du lieu cu
- Thu tu import nen theo nhom bang cha truoc, bang con sau neu co FK.
- Can map du lieu theo cac cot moi/doi kieu du lieu (neu co) truoc khi import.
- Nen import thu tren moi truong staging truoc khi chay production.

## Tinh trang tasks
- DB-01: Da quet va xac dinh nhom migration can consolidate.
- DB-02: Da chot final schema theo bo baseline `migrations_final`.
- DB-03: Da tao bo migration tong hop per-table.
- DB-04: Da loai bo migration alter trong baseline; giu index/unique/default tu create migration hien tai.
- DB-05: Da bo sung script verify schema sau migrate fresh.
