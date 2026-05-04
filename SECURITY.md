# Security policy

## Reporting a vulnerability

Please **do not** open a public issue for security vulnerabilities.

Email: `security@yourdomain.example` with:
- A description of the issue
- Steps to reproduce
- (Optional) a proposed fix

We aim to acknowledge reports within 72 hours.

## Supported versions

Only the latest minor version receives security fixes during this initial release phase.

## Scope

In scope:
- Authentication & session handling
- Authorization (account, admin, API)
- CSRF, XSS, injection, RCE
- Webhook signature verification
- File upload / path traversal

Out of scope:
- Self-XSS in admin-only fields where the operator owns the data
- Issues that require a fully compromised admin account
- Outdated dependencies that are not exploitable in default config
