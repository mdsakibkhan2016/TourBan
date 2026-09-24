# TourBan

A tourism and travel booking website built with PHP, MySQL, and Bootstrap 5.

## Features

- Homepage with hero section and smart search
- Destinations catalog, services, about, and contact pages
- User registration, login, session-based dashboard, and profile editing
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

## Groq Chatbot Configuration

1. Create an account at [console.groq.com](https://console.groq.com/keys) and generate a free API key.
2. Set it in `.env` (local) or your hosting environment panel (production):

   ```env
   GROQ_API_KEY=gsk_your_real_key
   GROQ_MODEL=llama-3.1-8b-instant
   ```

3. The browser only calls `api/chatbot.php`. The PHP endpoint reads `GROQ_API_KEY` server-side and calls `https://api.groq.com/openai/v1/chat/completions`. **The key is never sent to the client.**
4. If `GROQ_API_KEY` is missing, the API returns HTTP 503: `AI assistant is not configured.`

## Database Setup

Schema (created by `config/init_database.php` when `APP_ENV=local`):

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

Passwords are stored with `password_hash()` (bcrypt).

## Deployment Steps

1. Push the project to your Git repository.
2. Create a MySQL database and user on your host.
3. Deploy the code (Git integration, FTP, or file manager).
4. Add environment variables (see platform table above) or upload `.env`.
5. Set `APP_ENV=production` and `APP_DEBUG=false`.
6. Set `APP_URL` to your live URL (no trailing slash), e.g. `https://your-domain.com`.
7. Run database initialization once (local only), or import the SQL schema manually.
8. Ensure the web root points at the project folder (where `index.php` lives).
9. Verify: homepage, register, login, dashboard, chatbot.

### Shared hosting checklist (cPanel / Hostinger)

- [ ] PHP 8.1+ selected
- [ ] `pdo_mysql` and `curl` enabled
- [ ] `.env` uploaded (or env vars set in panel)
- [ ] Database created and user attached
- [ ] `.htaccess` present in web root
- [ ] `APP_ENV=production`

## InfinityFree production setup

GitHub Actions deploys Git-tracked files to `/htdocs/` (workflow: `.github/workflows/deploy.yml`). `.env` is **not** deployed — you must add it on the server.

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

### 3. Create the database + `users` table

1. vPanel → **MySQL Databases**: create database + user, attach user to DB.
2. Import this SQL (phpMyAdmin or the DB tools):

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

`config/init_database.php` is **blocked** when `APP_ENV=production` — run the SQL above manually instead.

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
