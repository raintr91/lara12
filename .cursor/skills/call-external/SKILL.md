---
name: call-external
description: >-
  Marks and designs backend APIs that call or receive third-party/external
  systems such as payment, shipping, SMS, OAuth, webhook, ERP, or external APIs.
  Use when the spec mentions #call-external, third-party APIs, payment,
  webhooks, or external integrations.
disable-model-invocation: true
---

# #call-external

Use when an API calls or receives data from a third-party system.

## Spec

Add `call-external` to tags and describe `externalCalls`:

```yaml
tags:
  - call-external

externalCalls:
  - id: payment.createIntent
    provider: Stripe
    direction: outbound
    trigger: POST /admin/orders/{id}/pay
    purpose: Create payment intent.
    service: Modules\Order\Services\Payment\CreatePaymentIntentService
    timeoutMs: 10000
    retry:
      enabled: false
      reason: Payment retry requires idempotency.
    idempotency:
      required: true
      key: order_payment_attempt_id
    secrets:
      - PAYMENT_PROVIDER_SECRET
```

Endpoint references:

```yaml
api:
  endpoints:
    - id: order.pay
      tags:
        - call-external
      externalCallRefs:
        - payment.createIntent
```

## OpenAPI

Add tag `call-external` and vendor extension:

```yaml
x-external-calls:
  - provider: Stripe
    service: CreatePaymentIntentService
    direction: outbound
    idempotencyRequired: true
```

## Implementation

- Generate Service/integration client.
- Do not put external HTTP calls in Action, Query, Controller, Request, or Resource.
- Controller may call Service for pure integration endpoints.
- If local DB mutation is required, Service owns external orchestration and transaction boundary design.
- Secrets come from config/env, never spec/code literals.
- Log provider request id, event id, status, and failure reason.
