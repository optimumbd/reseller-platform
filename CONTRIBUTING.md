# Contributing

Thanks for your interest in contributing!

## Development setup

```bash
git clone https://github.com/optimumbd/reseller-platform.git
cd reseller-platform
cp .env.example .env
php bin/console key:generate
# create a MySQL DB, set DB_* in .env
php bin/console migrate
php bin/console seed
php -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000.

## Code style

- PHP 8.1+, `declare(strict_types=1)`
- PSR-4 namespaces under `App\`
- Pure PHP — do not introduce build steps
- Templates use Tailwind utility classes via the CDN
- Add a CHANGELOG entry under `[Unreleased]` for any user-facing change

## Pull requests

- One topic per PR
- Include screenshots for UI changes
- Keep changes minimal and focused

## Reporting bugs

Open an issue with:
1. PHP version and DB version
2. Reproduction steps
3. Expected vs actual behavior
