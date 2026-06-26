# HTTP Layer (API Base)

## Controller

- Extend `{Module}Controller`; traits `EntryCreateTrait`, …
- Thin: delegate to Action/Query; no direct DB queries

## Action

- `create`/`update`/`delete` + relationship mutations here
- Transaction: `$this->transaction(fn () => ...)`

## Query

- `filters()`, `sorts()`, `includes()`; `filter[field]=value`

## Request

- `rules()` only

## Resource

- Map model → JSON; no DB queries

Routes: `src/Modules/{Module}/Routes/api.php`
