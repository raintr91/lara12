# Conventions

This repository follows a **module-first** structure (nwidart/laravel-modules) plus a small set of shared “app-level” base classes.

## Module layout

A typical module lives under:

- `Modules/<Module>/Http/Controllers`
- `Modules/<Module>/Http/Requests`
- `Modules/<Module>/Http/Actions`
- `Modules/<Module>/Http/Queries`
- `Modules/<Module>/Http/Resources`
- `Modules/<Module>/Routes/api.php`

Routes are generally grouped like:

- `Modules/<Module>/Routes/api.php` (main module group)
- `Modules/<Module>/Routes/auth.php` (auth endpoints, included from api.php)

## Controller pattern (CRUD)

For standard CRUD/search endpoints, controllers typically:

- extend the module base controller (example: `Modules/Admin/Http/Controllers/AdminController`)
- use small single-purpose traits:
  - `EntryCreateTrait`
  - `EntryUpdateTrait`
  - `EntryDeleteTrait`
  - `EntrySearchTrait`
  - `EntryDetailTrait`
- provide thin wrapper methods calling the trait:
  - `create`, `update`, `delete`
  - `search` (pagination)
  - `getDetail`

The “business work” is delegated to:

- **Action** classes for create/update/delete
- **Query** classes for list/search/detail

## Request pattern

Requests should:

- extend the module base request (example: `Modules/Admin/Http/Requests/AdminRequest`)
- define `rules()` only (authorization + error formatting is centralized in `App\Http\Requests\BaseRequest`)

## Query pattern

Queries should:

- extend the module base query (example: `Modules/Admin/Http/Queries/AdminQuery`)
- implement `newQuery(): Builder` returning `Model::query()`
- declare allowed criteria via:
  - `filters()`
  - `sorts()`
  - `includes()`

### Filter syntax

Filters are passed from the client as:

- `filter[field]=value` (query string) OR
- `{ "filter": { "field": "value" } }` (request body)

Declare allowed filters in query classes using the tuple syntax:

- `['name', 'like']`
- `['status', '=']`

The filtering behavior is implemented by `App\Http\Queries\Criteria\FilterCriteria`.

## Select-items API pattern (for FE selects)

Some screens need to populate a `<select>` by fetching a minimal list of entities (often `id` + display `name` + optional `info`).

This repo provides a reusable select-items infrastructure:

- Request base: `App\Http\Requests\SelectItemRequest`
- Query trait: `App\Http\Queries\Traits\SelectItemQueryTrait`
- Controller trait: `App\Http\Controllers\Traits\SelectItemControllerTrait`
- Resource base: `App\Http\Resources\SelectItemResource`

### Client payload

The FE request payload shape:

- `filter` (optional): same shape as search
- `key` (string): field used for select value
- `name` (string[]): one or more fields used to build the select label
- `info` (string[], optional): extra scalar fields or relationships to load

Notes:

- `key` and every item in `name[]` must be a scalar field allowed by the model (fillable or primary key).
- `info[]` is best-effort: unknown fields/relations are ignored by the query layer.

### Response shape

Every item is returned as:

- `key`: the selected key value
- `name`: label string (concatenated if multiple name fields)
- `info`: object containing any extra data requested

### Info format

`info[]` supports:

- scalar fields: `status`
- relationship name: `company` (loads relationship, output placed in `info.company`)
- dotted relationship field: `company.name` (loads relationship, only selected fields)

