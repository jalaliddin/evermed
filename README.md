# EverMED CRM

Tibbiy muassasalar uchun zamonaviy multitenancy SaaS tizimi.

**Stack:** Laravel 13 · MySQL 8 · Vue 3 · Vuetify 3 · Docker

---

## Arxitektura

```
VPS Nginx (8094)
    └─► Docker Nginx :80
           ├─► /api/*  ─── FastCGI ──► PHP-FPM (app:9000)
           │                               └─► MySQL (mysql:3306)
           └─► /*  ──── Static SPA (Vue 3 dist)

Queue Worker  ──► MySQL (database queue)
Scheduler     ──► php artisan schedule:run (every minute)
```

### Konteynerlar

| Konteyner  | Tavsif                              |
|------------|-------------------------------------|
| `mysql`    | MySQL 8.0, `evermed_central` DB     |
| `app`      | PHP 8.3-FPM, Laravel 13             |
| `nginx`    | Nginx 1.27, Vue SPA + API proxy     |
| `queue`    | Laravel queue worker                |
| `scheduler`| Laravel scheduler (cron wrapper)    |

---

## Ishga tushirish

### Talablar

- Docker Engine ≥ 26
- Docker Compose v2
- VPS Nginx `proxy_pass http://127.0.0.1:8094;`

### 1. Clone va sozlash

```bash
git clone <repo> med.eversoft.uz
cd med.eversoft.uz

# .env faylini sozlang (APP_KEY, TELEGRAM_BOT_TOKEN va h.k.)
nano backend/.env
```

### 2. Build va ishga tushirish

```bash
# Barcha imagelarni build qilish + konteynerlarni ishga tushirish
make deploy

# yoki alohida:
docker compose build
docker compose up -d
```

### 3. Demo ma'lumotlarni yuklash

```bash
make seed
```

Bu buyruq quyidagilarni yaratadi:
- Super admin: `admin@eversoft.uz` / `admin123`
- Demo klinika: slug `demo`, 5 foydalanuvchi, 3 shifokor, 50 bemor, 30 kunlik tarix

---

## Muhim buyruqlar

```bash
make ps          # Konteynerlar holati
make logs        # Barcha loglar (real-time)
make shell       # App konteyneri ichiga kirish
make seed        # Seeder ishga tushirish
make fresh       # DB reset + re-seed (BARCHA ma'lumotlar o'chadi!)
make down        # Barcha konteynerlarni to'xtatish
make build       # Imagelarni qayta build qilish (no-cache)
```

Qo'lda:

```bash
# App konteyneri ichida artisan buyruqlari
docker compose exec app php artisan <buyruq>

# Tenant migratsiyasini alohida ishga tushirish
docker compose exec app php artisan tenants:migrate --force

# Queue monitor
docker compose exec queue php artisan queue:monitor
```

---

## Yangilash (deployment)

```bash
# Kodni pull qiling
git pull origin main

# Imagelarni qayta build qilib restart
make build
docker compose up -d

# Yangi migratsiyalar bo'lsa
docker compose exec app php artisan migrate --force
```

---

## Muhit sozlamalari (backend/.env)

Docker compose fayliga kiritilgan environment o'zgaruvchilari `.env` dan ustunlik qiladi:

| O'zgaruvchi | Docker qiymati | Tavsif |
|-------------|----------------|--------|
| `DB_HOST`   | `mysql`        | MySQL konteyner nomi |
| `APP_ENV`   | `production`   | Kesh faollashadi |
| `APP_URL`   | `http://med.eversoft.uz` | To'liq URL |

Qolgan barcha o'zgaruvchilar `backend/.env` dan o'qiladi.

---

## Foydalanuvchi turlari

| Rol           | Login                     | Yo'nalish      |
|---------------|---------------------------|----------------|
| Super Admin   | `admin@eversoft.uz`       | `/admin`       |
| Tenant Admin  | `admin@demo.com`          | `/dashboard`   |
| Shifokor      | `doctor@demo.com`         | `/dashboard`   |
| Registrator   | `receptionist@demo.com`   | `/dashboard`   |

Barcha demo parollar: `password123`

---

## Modullar

- **Qabullar** — Sana bo'yicha jadval, holat workflow (pending → confirmed → in_progress → completed)
- **Tashriflar** — Xizmatlar, to'lov, diagnoz, retsept, ESC/POS chek
- **Bemorlar** — To'liq profil, tashrif tarixi, qidirish/filter
- **Shifokorlar** — Ish jadvali, statistika, bugungi qabullar
- **Xizmatlar** — Kategoriyalar, narxlar, davomiylik
- **Inventar** — Kirim/chiqim, kam qolgan mahsulotlar ogohlantirishlari
- **Hisobotlar** — Kunlik daromad, top xizmatlar, shifokorlar natijalari, Excel/PDF eksport
- **Telegram** — Bot integratsiyasi, kunlik hisobot (18:00)
- **Super Admin** — Tenantlar, obunalar boshqaruvi

---

## Texnik tafsilotlar

- **Multitenancy:** `stancl/tenancy ^3.8` — har bir klinika uchun alohida MySQL DB (`evermed_tenant_{slug}`)
- **Auth:** Laravel Sanctum (SPA token-based)
- **Tenant identifikatsiya:** `X-Tenant` header (`InitializeTenancyByRequestData`)
- **Queue:** Database driver, `queue` konteyneri ishlatadi
- **ESC/POS:** `mike42/escpos-php` (tarmoq printer orqali)
- **Eksport:** `maatwebsite/excel` + `barryvdh/laravel-dompdf`

---

## Obuna modeli

**150 000 so'm/oy** — cheksiz foydalanuvchilar, barcha modullar kiradi.
