# TourBan

A tourism and travel booking website built with PHP, MySQL, and Bootstrap 5.

## Features

- Homepage with hero section and smart search
- Destinations catalog, services, about, and contact pages
- User registration with **email OTP verification**, login, session-based dashboard, and profile editing
- **Forgot password** via email OTP + reset
- **Persistent remember-me** (hashed DB tokens, 30 days)
- Booking system (create, view, cancel) with **checkout payment page**
- **Payment architecture** — sandbox gateway built in; SSLCommerz, Stripe, and PayPal ready (credential-driven)
- **Email notifications** via PHPMailer SMTP (OTP, password reset, booking confirmation, status changes)
- **Admin panel** — dashboard stats, user roles, destinations, bookings, settings
- AI travel chatbot powered by Groq Cloud (server-side API proxy)

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.1+ (PDO, sessions) |
| Database | MySQL 8 / MariaDB 10.4+ |
| Frontend | HTML5, CSS3, JavaScript (ES6), Bootstrap 5 |
| AI | Groq Cloud API (`https://api.groq.com/openai/v1/chat/completions`) |
| Icons / Fonts | Font Awesome, Google Fonts (CDN) |

## Project Structure

```text
TourBan/
├── index.php, about.php, ...     # Public pages
├── login.php, register.php       # Auth pages
├── dashboard.php, profile.php    # Logged-in pages
├── api/                          # JSON endpoints (login, register, logout, profile, chatbot)
├── assets/                       # css, js, images
├── config/                       # env loader, database, DB init script
├── includes/                     # header, footer, auth, chatbot UI
├── .env.example                  # Environment variable template
├── .htaccess                     # Apache security / caching
└── README.md
```

## Requirements

- PHP 8.1 or newer with `pdo_mysql` and `curl` extensions
- MySQL 8 or MariaDB 10.4+
- Apache with `mod_rewrite` (or Nginx with equivalent rules)
- A [Groq Cloud](https://console.groq.com/keys) API key for the chatbot (optional but recommended)

## Installation (Local Development)

1. **Clone the repository**

   ```bash
   git clone https://github.com/mdsakibkhan2016/TourBan.git
   cd TourBan
   ```

2. **Create your environment file**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` and set your database credentials and API keys.

3. **Create the database and tables**

   ```bash
   mysql -u root -p -e "CREATE DATABASE tourban_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

   Then open (with `APP_ENV=local` in `.env`):

   ```
   http://localhost:8000/config/init_database.php
   ```

   Or import the `users` table manually (see `config/init_database.php` for the schema).

4. **Start the PHP development server**

   ```bash
   php -S localhost:8000
   ```

5. **Open the site**

   Visit `http://localhost:8000`.

## Environment Variables

Copy `.env.example` to `.env`. **Never commit `.env`.**

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_URL` | Prod | Public site URL, no trailing slash (empty = domain root). Example: `https://example.com` |
| `APP_DEBUG` | No | `true` only in local development |
| `APP_ENV` | Yes | `local` or `production` |
| `DB_HOST` | Yes | Database host (`localhost` or hosting provider host) |
| `DB_NAME` | Yes | Database name |
| `DB_USER` | Yes | Database username |
| `DB_PASS` | Yes | Database password |
| `DB_CHARSET` | No | Default `utf8mb4` |
| `GROQ_API_KEY` | Chatbot | Your Groq API key from [console.groq.com/keys](https://console.groq.com/keys) |
| `GROQ_MODEL` | No | Default `llama-3.1-8b-instant` |
| `MAIL_HOST` | Email | SMTP host (e.g. `smtp.gmail.com`). Empty = fall back to PHP `mail()` |
| `MAIL_PORT` | Email | SMTP port, default `587` |
| `MAIL_USERNAME` | Email | SMTP username / email address |
| `MAIL_PASSWORD` | Email | SMTP password (e.g. Gmail app password) |
| `MAIL_FROM` | Email | From address |
| `MAIL_FROM_NAME` | Email | From display name |
| `MAIL_ENCRYPTION` | Email | `tls` (default), `ssl`, or `none` |
| `PAYMENT_GATEWAY` | No | Default `sandbox` (simulated checkout) |
| `SSLCOMMERZ_STORE_ID` | SSLCommerz | Empty until SSLCommerz is enabled |
| `SSLCOMMERZ_STORE_PASSWORD` | SSLCommerz | Empty until SSLCommerz is enabled |
| `SSLCOMMERZ_SANDBOX` | SSLCommerz | `1` = test mode, `0` = live |
| `STRIPE_SECRET_KEY` | Stripe | Empty until Stripe is enabled |
| `STRIPE_WEBHOOK_SECRET` | Stripe | Empty until Stripe is enabled |
| `PAYPAL_CLIENT_ID` | PayPal | Empty until PayPal is enabled |
| `PAYPAL_CLIENT_SECRET` | PayPal | Empty until PayPal is enabled |

### Where to set environment variables by platform

| Platform | How to set variables |
|----------|----------------------|
| **cPanel / Hostinger shared hosting** | Upload a `.env` file next to `index.php` (file manager), **or** add the same keys under *Setup PHP Environment Variables* / *Environment* in the control panel. |
| **Render** | Dashboard → Service → *Environment* tab → add key/value pairs. |
| **Railway** | Project → Service → *Variables* tab. |
| **Vercel** | Project → Settings → *Environment Variables*. |
| **Netlify** | Site → Site configuration → *Environment variables*. |
| **DigitalOcean App Platform** | App → *Settings → Environment variables*. |
| **VPS (Apache/Nginx + PHP-FPM)** | Set variables in the systemd unit, Nginx/Apache vhost `env` directives, or a `.env` file in the web root (blocked by `.htaccess`). |
| **AWS / GCP / Azure** | Use the platform secret manager or service environment configuration. |

Real server environment variables always override values in `.env`.

## GitHub Actions deployment (InfinityFree)

On every push to `main`, the workflow `.github/workflows/deploy.yml`:

1. Checks out the repository
2. **Generates a temporary `.env` from GitHub Actions secrets** (never committed)
3. Uploads the project (including `.env`) to `ftpupload.net:/htdocs/`
4. **Deletes the temporary `.env`** from the CI workspace

### Required GitHub secrets

Repository → **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Example / source |
|--------|------------------|
| `FTP_SERVER` | `ftpupload.net` |
| `FTP_USERNAME` | `if0_XXXXXXXX` (panel → FTP Details) |
| `FTP_PASSWORD` | Hosting account password |
| `APP_URL` | `https://your-site.infinityfreeapp.com` |
| `DB_HOST` | `sql.infinityfree.com` |
| `DB_NAME` | `if0_XXXXXXXX_dbname` |
| `DB_USER` | `if0_XXXXXXXX_user` |
| `DB_PASS` | MySQL user password |
| `GROQ_API_KEY` | Key from [console.groq.com/keys](https://console.groq.com/keys) |
| `GROQ_MODEL` | `llama-3.1-8b-instant` |
| `MAIL_HOST` | e.g. `smtp.gmail.com` (leave empty to use PHP `mail()`) |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | SMTP account email |
| `MAIL_PASSWORD` | SMTP password / app password |
| `MAIL_FROM` | e.g. `no-reply@your-domain.com` |
| `MAIL_FROM_NAME` | `TourBan` |
| `MAIL_ENCRYPTION` | `tls` |
| `PAYMENT_GATEWAY` | `sandbox` (default) |
| `SSLCOMMERZ_STORE_ID` | SSLCommerz store id (optional) |
| `SSLCOMMERZ_STORE_PASSWORD` | SSLCommerz store password (optional) |
| `SSLCOMMERZ_SANDBOX` | `1` |
| `STRIPE_SECRET_KEY` | Stripe secret key (optional) |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook secret (optional) |
| `PAYPAL_CLIENT_ID` | PayPal client id (optional) |
| `PAYPAL_CLIENT_SECRET` | PayPal client secret (optional) |

`.env` is git-ignored and **must never be committed**. Only GitHub stores these values.

## Groq Chatbot Configuration

1. Create an account at [console.groq.com](https://console.groq.com/keys) and generate a free API key.
2. Set it in `.env` (local) or your hosting environment panel (production):

   ```env
   GROQ_API_KEY=gsk_your_real_key
   GROQ_MODEL=llama-3.1-8b-instant
   ```

3. The browser only calls `api/chatbot.php`. The PHP endpoint reads `GROQ_API_KEY` server-side and calls `https://api.groq.com/openai/v1/chat/completions`. **The key is never sent to the client.**
4. If `GROQ_API_KEY` is missing, the API returns HTTP 503: `AI assistant is not configured.`

## Payments

Booking checkout is wired end-to-end: creating a booking lands the user on
`payment.php?ref=TB-XXXXXXXX`, which posts to `api/payment.php`.

| Gateway | Env credentials | Behaviour |
|---------|-----------------|-----------|
| `sandbox` (default) | none | Simulated charge, marks payment `paid` + booking `confirmed`. Works out of the box. |
| `sslcommerz` | `SSLCOMMERZ_STORE_ID` + `SSLCOMMERZ_STORE_PASSWORD` | API handler ready — activates once credentials exist and the Session API call is enabled |
| `stripe` | `STRIPE_SECRET_KEY` | API handler ready — activates once credentials exist and the Checkout Session call is enabled |
| `paypal` | `PAYPAL_CLIENT_ID` + `PAYPAL_CLIENT_SECRET` | API handler ready — activates once credentials exist and the Orders v2 call is enabled |

Security properties:

- Amount is always read from the `bookings` row — never from client input
- CSRF token required (`X-CSRF-Token`); owner-only access to a booking's payment
- Payments are idempotent: a paid booking cannot be charged twice (HTTP 409)
- Unconfigured gateways return HTTP 501 — no fake or real charges without credentials
- Payment rows live in the `payments` table (status: `pending`, `paid`, `failed`, `refunded`)

## Database Setup

**Production:** import `database.sql` once via phpMyAdmin (or MySQL client). It creates all tables and seed destinations.

**Upgrades:** run `migrations.sql` on an existing database (adds `users.role`, `users.is_verified`, `users.is_active`, `destinations.category`, `payments` table — each statement is safe to re-run or skip if the object exists).

**Local development (optional):** with `APP_ENV=local`, open `config/init_database.php` to create only the legacy `users` table.

<details>
<summary>Legacy users-only schema (init_database.php)</summary>

```sql
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    birthdate DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

</details>

Passwords are stored with `password_hash()` (bcrypt).

## Deployment Steps

1. Push to `main` — GitHub Actions generates `.env` from secrets and deploys via FTP.
2. Import `database.sql` once (phpMyAdmin on your host).
3. Confirm all GitHub secrets are set (table above).
4. Verify: homepage, register, login, dashboard, chatbot.

### Shared hosting checklist (cPanel / Hostinger)

- [ ] PHP 8.1+ selected
- [ ] `pdo_mysql` and `curl` enabled
- [ ] Database created and user attached; `database.sql` imported
- [ ] `.htaccess` present in web root
- [ ] GitHub secrets configured (or `.env` uploaded manually)

## InfinityFree production setup

GitHub Actions deploys Git-tracked files to `/htdocs/` and **creates `.env` on the server during deploy** from repository secrets (see “GitHub Actions deployment” above). You do not need to upload `.env` manually when using CI.

Manual fallback:

1. vPanel → File Manager → `htdocs/` → upload `.env` (from `.env.example` filled in).
2. Or vPanel → **Setup PHP Environment Variables**.

`.htaccess` blocks web access to `.env`.

### 1. Place `.env` on InfinityFree

1. Create the file locally from `.env.example` (do not commit it).
2. In InfinityFree **vPanel → File Manager**, open `htdocs/` (same folder as `index.php`).
3. Upload/`.env` there so the path is `htdocs/.env`.
4. Alternative: vPanel → **Setup PHP Environment Variables** and add the same keys (server env vars override `.env`).

`.htaccess` blocks web access to `.env`.

### 2. Required environment variables

| Variable | Required | Example / notes |
|----------|----------|-----------------|
| `APP_URL` | Prod | `https://your-site.infinityfreeapp.com` (no trailing slash). Used for CSS/JS/image links and navigation. |
| `APP_ENV` | Yes | `production` |
| `APP_DEBUG` | Yes | `false` |
| `DB_HOST` | Yes | `sql.infinityfree.com` (check vPanel → MySQL Databases) |
| `DB_NAME` | Yes | e.g. `if0_12345678_tourban_db` |
| `DB_USER` | Yes | e.g. `if0_12345678_admin` |
| `DB_PASS` | Yes | MySQL user password from vPanel (not your account password) |
| `GROQ_API_KEY` | Chatbot | Groq key from [console.groq.com/keys](https://console.groq.com/keys) — leave empty to get the friendly “not configured” error |
| `GROQ_MODEL` | No | `llama-3.1-8b-instant` (default) |

Optional: `DB_CHARSET` (default `utf8mb4`).

### 3. Create the database

1. vPanel → **MySQL Databases**: create database + user, attach user to DB.
2. Import **`database.sql`** via phpMyAdmin (all tables + seed destinations).
3. `config/init_database.php` is **blocked** when `APP_ENV=production` — always use `database.sql` in production.

### 4. Verify after deploy

- [ ] Homepage: CSS/fonts/images load (no unstyled page)
- [ ] Register → new row in `users`
- [ ] Login → dashboard shows user name
- [ ] Chatbot without `GROQ_API_KEY` → “AI assistant is not configured.” (HTTP 503), not a blank failure
- [ ] Chatbot with key set → real replies

## Security Notes

- `.env` is git-ignored and blocked by `.htaccess`
- API keys are read only on the server
- SQL uses prepared statements (PDO)
- Session cookies: `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS
- Error details are logged with `error_log()`, not shown to users when `APP_DEBUG=false`
- Chatbot input is length-validated; UI renders messages via `textContent` (XSS-safe)

## Git

```bash
git add .
git commit -m "Your message"
git push origin main
```

Do **not** commit `.env` or real API keys.

## License

Web & Internet Programming Lab project. All rights reserved.

## Contact

- Email: tourban@gmail.com
- Repository: https://github.com/mdsakibkhan2016/TourBan
