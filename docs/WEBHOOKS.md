# Webhooks

## Incoming webhooks (payment gateways)

```
POST /webhook/{gateway}
```

Where `{gateway}` is one of: `stripe`, `paypal`, `bkash`, `nagad`, `sslcommerz`.

Each driver verifies its provider's signature before processing. Configure secrets in `.env`:

```
STRIPE_WEBHOOK_SECRET=
PAYPAL_WEBHOOK_ID=
BKASH_WEBHOOK_SECRET=
```

## Webhook event log

Every received webhook is recorded in the `webhook_events` table with payload + result. Use the admin panel to inspect & replay failed deliveries.
