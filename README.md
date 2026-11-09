# Enigma Payroll

Laravel Blade payroll app on **Laravel Herd** + **Homebrew MySQL**.

## Run (this machine)

1. Herd running undali (nginx + PHP).
2. Homebrew MySQL running undali:
   ```bash
   brew services start mysql
   ```
3. Browser open:
   **https://enigma-payroll.test/login**

   (HTTP auto-redirects to HTTPS after `herd secure`.)

Demo logins (password: `password`):
- `hr@enigma.test`
- `finance@enigma.test`
- `emp@enigma.test`
- `auditor@enigma.test`

## First-time setup

```bash
# DB
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS enigma_payroll CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS enigma_payroll_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

cd "/Users/praneethkumar/Sites/htdocs-from-xampp/Enigma Metaverse/backend"
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate

# .env
# APP_URL=https://enigma-payroll.test
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=enigma_payroll
# DB_USERNAME=root
# DB_PASSWORD=

php artisan migrate --seed

# Herd site (once) — park/link + HTTPS
herd link enigma-payroll
herd secure enigma-payroll
```

`APP_URL=https://enigma-payroll.test`

CSS: Tailwind CLI builds `public/css/app.css`. Blade change chesaka malli `npm run build` (or `npm run dev` watch).

## What is implemented
- Blade admin + session auth (guest middleware on login)
- Roles: hr, finance, employee, auditor (Spatie `role:` + Form Requests / Policies)
- Employees create/edit + salary structure create/update
- Attendance CSV, payroll Draft → … → Paid → Reconciled (maker-checker)
- Settings: company config + versioned statutory rates
- EPF / ESI / PT, printable payslips, loans (approve + schedule), revisions, exit Step-1, journal/bank exports

## Tests
```bash
cd backend && php artisan test
```

Tests use MySQL database `enigma_payroll_test` (see `phpunit.xml`).
