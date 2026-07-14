# Platform Base — Reference

Bổ sung chi tiết cho [SKILL.md](SKILL.md). Đọc khi cần template code đầy đủ hoặc tra bảng naming.

---

## Service template

```ts
// services/work-order.service.ts
import type { WorkOrder } from '~/models/work-order/work-order.types'
import { WorkOrderListSchema } from '~/models/work-order/work-order.schema'
import { parseApiData } from '~/services/shared/parseApiData'

export function createWorkOrderService(api: typeof $apiFetch) {
  return {
    async list(params?: Record<string, string>) {
      const res = await api('/api/work-orders', { query: params })
      return parseApiData(WorkOrderListSchema, res)
    }
  }
}
```

## Composable list template

```ts
// composables/work-order/useWorkOrderList.ts
export function useWorkOrderList() {
  const { $apiFetch } = useNuxtApp()
  const service = createWorkOrderService($apiFetch)
  const items = ref<WorkOrder[]>([])
  const loading = ref(false)

  async function load() {
    loading.value = true
    try {
      items.value = await service.list()
    } finally {
      loading.value = false
    }
  }

  return { items, loading, load }
}
```

## Page + testId template

```vue
<script setup lang="ts">
import { useWorkOrderList } from '~/composables/work-order/useWorkOrderList'

const { items, loading, load } = useWorkOrderList()
onMounted(load)
</script>

<template>
  <div>
    <DataPageHeader test-id="work-orders-page" title="Work Orders">
      <template #actions>
        <Button test-id="work-orders-create-btn">Create</Button>
      </template>
    </DataPageHeader>

    <MoBreadcrumbNav
      test-id="work-orders-breadcrumb"
      :items="[{ label: 'Home', href: '/' }, { label: 'Work Orders' }]"
    />

    <div v-if="loading" data-testid="work-orders-loading">Loading…</div>

    <table v-else data-testid="work-orders-table">
      <tr
        v-for="row in items"
        :key="row.id"
        data-testid="work-order-row"
        :data-work-order-id="row.id"
      >
        <td>{{ row.name }}</td>
        <td>
          <Button test-id="work-order-edit-btn">Edit</Button>
        </td>
      </tr>
    </table>
  </div>
</template>
```

## Playwright spec template

```ts
import { expect, test } from '@playwright/test'
import { assertLayoutIntegrity } from './helpers/assertLayoutIntegrity'
import { mockAuthenticatedSession } from './helpers/session'

test.describe('Work orders', () => {
  test.beforeEach(async ({ page }) => {
    await mockAuthenticatedSession(page)
    await page.goto('/work-orders')
    await assertLayoutIntegrity(page)
  })

  test('shows page shell', async ({ page }) => {
    await expect(page.getByTestId('work-orders-page')).toBeVisible()
    await expect(page.getByTestId('work-orders-create-btn')).toBeVisible()
  })
})
```

---

## Hierarchy testId đầy đủ

```text
customers-page
├── customers-page-title
├── customers-page-description
├── customers-breadcrumb
│   ├── customers-breadcrumb-item-0
│   ├── customers-breadcrumb-link-0
│   └── customers-breadcrumb-current
├── customers-search-input
├── customers-create-btn
├── customers-table
│   └── customer-row (data-customer-id)
├── customer-delete-dialog
│   ├── customer-delete-dialog-title
│   ├── customer-delete-dialog-content
│   ├── customer-delete-dialog-cancel-btn
│   └── customer-delete-dialog-confirm-btn
└── customer-detail-page
    ├── customer-name-wrapper
    ├── customer-name-label
    ├── customer-name-input
    ├── customer-name-error
    └── customer-save-btn
```

## Auth login reference ids

`pages/auth/login.vue`:

| testId | Element |
|--------|---------|
| `auth-login-page` | Root |
| `auth-login-form` | Form |
| `auth-login-email-input` | Email |
| `auth-login-password-input` | Password |
| `auth-login-submit-btn` | Submit |
| `auth-login-error-alert` | API error |
| `auth-login-validation-error` | Field errors |
| `auth-login-forgot-link` | Forgot password |
| `auth-login-logo` | Logo |
| `auth-login-subtitle` | HTTPS notice |

## Global app shell ids (cố định)

| testId | Component |
|--------|-----------|
| `app-toast` | Toast container |
| `app-toast-title` | Toast title |
| `app-toast-message` | Toast body |
| `app-toast-close` | Close |
| `app-dialog` | Dialog root |
| `app-dialog-content` | Panel |
| `app-dialog-title` | Title |
| `app-dialog-message` | Body |
| `app-dialog-confirm-btn` | Confirm |
| `app-dialog-cancel-btn` | Cancel |

## Layout integrity options

```ts
assertLayoutIntegrity(page, {
  selector: '[data-testid]',       // default
  rootSelector: 'body',
  skipTestIds: ['app-toast', 'app-dialog', ...],
  minPageHeight: 80,               // *-page shells
  minControlSize: 20,              // buttons/inputs
  overflowTolerance: 2,            // px
  minOverlapArea: 64,              // px²
  skipOverlap: false
})
```

## Thư mục quan trọng

```
portal/
├── pages/                 # Routes
├── composables/           # Orchestration
├── services/              # HTTP
├── stores/                # Pinia state
├── models/                # Zod + types
├── validations/           # Form schemas
├── components/
│   ├── ui/                # shadcn primitives
│   ├── molecules/         # Mo* composites
│   └── organisms/         # Page sections
├── middleware/            # auth, guest, rbac
├── tests/e2e/             # Playwright
├── docs/
│   ├── ARCHITECTURE.md
│   └── E2E-TESTIDS.md
└── utils/testId.ts
```

## HTTP / E2E env

| Biến | Mục đích |
|------|----------|
| `NUXT_PORT` | Dev server |
| `NUXT_E2E_PORT` | E2E server (default 3005) |
| `NUXT_PUBLIC_E2E=1` | API base = same origin |
| `PLAYWRIGHT_SKIP_WEBSERVER=1` | Test against running app |
| `PLAYWRIGHT_BASE_URL` | Remote/staging URL |
