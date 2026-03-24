# Setup Project c-procurement

Project ini telah di-clean. Folder besar seperti `node_modules` dan `vendor` telah dihapus untuk menghemat ukuran. Ikuti langkah-langkah berikut untuk setup:

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL

## 1. Install Dependencies

### Backend (PHP)

```bash
composer install
```

### Frontend (Node.js)

```bash
npm install
```

## 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate
```

Edit `.env` dengan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=db_erd
DB_USERNAME=root
DB_PASSWORD=
```

## 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

## 4. Build Frontend Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

## 5. Start Development Server

```bash
# Terminal 1: PHP Server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

Akses aplikasi di: http://localhost:8000

---

## Cleanup Summary

File/folder yang telah dihapus:

- ✅ `node_modules/` (~400-500MB)
- ✅ `vendor/` (~200-300MB)
- ✅ `dist/` (build output)
- ✅ `public/hot` (Vite dev file)
- ✅ `public/build/` (build output)
- ✅ `.phpunit.result.cache`
- ✅ `postman/` (API testing)
- ✅ `storage/logs/*` (generated)
- ✅ `bootstrap/cache/*` (generated)
- ✅ `docs/releases/`
- ✅ `user_satriamerahputih.txt`
- ✅ `nul` (unnecessary file)

**Ukuran berkurang: ~800MB-1GB**

---

## Timezone Configuration

Aplikasi ini sudah dikonfigurasi untuk menggunakan timezone **Asia/Jakarta** (WIB).

Konfigurasi di: `config/app.php` dan `.env`

---

## Development Commands

```bash
# Run tests
npm run test

# Format code
npm run lint

# Type check
npm run type-check

# Build for production
npm run build
```

---

## Deployment

Untuk production deployment:

1. Pastikan `.env` sudah dikonfigurasi dengan benar
2. Jalankan: `composer install --no-dev && npm run build`
3. Upload folder ke server
4. Jalankan migrations: `php artisan migrate --force`
