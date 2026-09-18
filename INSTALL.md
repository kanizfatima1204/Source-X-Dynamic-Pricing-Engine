# Installation

## Commands
```bash
cd source-x-dynamic-pricing
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Development (two terminals):
```bash
php artisan serve
npm run dev
```

Tests:
```bash
php artisan test
```
