# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial scaffold of the reseller platform.
- Lightweight MVC core (Router, Container, Database, Session, View, …).
- Domain, hosting, email, SSL, VPS, dedicated product abstractions.
- Unified provisioning pipeline + Mock provisioners.
- Registrar abstraction with Mock, Namecheap, Spaceship, Cloudflare, OpenSRS drivers.
- Payment gateway abstraction with Manual, Wallet, Stripe, PayPal, bKash, Nagad, SSLCommerz drivers.
- Authentication: login, register, password reset, email verification, magic-link, 2FA (TOTP).
- Account dashboard, profile, security, services, domains, orders, invoices, wallet, tickets.
- Admin dashboard, customers, domains, services, orders, invoices, TLD pricing, settings.
- Public REST API v1 (domain check, services, orders).
- Tailwind CSS via CDN, dark mode toggle, responsive layouts.
- i18n (English + Bangla) with namespace-based language files.
- Web-based install wizard for shared hosting.
- CLI commands: migrate, seed, queue:work, schedule:run, …
