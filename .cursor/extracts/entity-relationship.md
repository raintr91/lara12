# Entity & Relationship

## Platform vs Tenant

- Platform: `m:model X Platform` → `App\Models\Platform\X`, migration `platform/`
- Tenant: `m:model X Tenant` → `App\Models\Tenant\X`, migration `tenant/`
- Each `create_*` migration: `$table->softDeletes()`

## Pivot M-N — NO MODEL

- Migration only; no `m:model` for pivot table
- `belongsToMany` on both parents; sync in parent **Action**
- No Controller/Action dedicated to pivot

## Child hasMany/hasOne

- Has model + migration; sync via parent **Action**

## Relationship vs Service

| Case | Use |
|------|-----|
| Eloquent relationship | Parent **Action** / **Query** `includes()` |
| Unrelated domains | **Service** (`Modules/*/Services/`) |
| Webhook, import, OAuth | **Service** (`#call-external`) |
