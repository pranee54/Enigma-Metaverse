# Enigma Payroll (backend)

Laravel Blade app. **MySQL only** (Herd + Homebrew MySQL).

Setup / demo logins: see root [`../README.md`](../README.md).

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan test
```
