# Public REST API v1

Base URL: `https://yourdomain.com/api/v1`

All endpoints return JSON. Authenticated endpoints require a Bearer token via the `Authorization` header.

## Authentication

Issue an API token from **Account → Profile → API tokens** (or programmatically via the database).

```
Authorization: Bearer <token>
```

## Endpoints

### Check domain availability

```
GET /api/v1/domains/check?name=example.com
```

Response:
```json
{
  "domain": "example.com",
  "available": false,
  "currency": "USD",
  "register_price": 9.99
}
```

Public (no auth).

### List my services

```
GET /api/v1/services
Authorization: Bearer <token>
```

Response:
```json
{
  "data": [
    { "id": 1, "type": "domain", "label": "example.com", "status": "active" }
  ]
}
```

### List my orders

```
GET /api/v1/orders
Authorization: Bearer <token>
```

## Rate limiting

Default: 60 requests per minute per token. Returns `429 Too Many Requests` when exceeded.

## Errors

```json
{ "error": "validation_failed", "message": "The given data was invalid.", "details": { ... } }
```

| Code | Meaning |
| --- | --- |
| 200 | OK |
| 201 | Created |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not found |
| 422 | Validation failed |
| 429 | Too many requests |
| 500 | Server error |
