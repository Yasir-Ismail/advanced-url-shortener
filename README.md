# Advanced URL Shortener with Analytics

A production-grade URL shortener built with **Core PHP + MySQL**. No frameworks, no external APIs — just clean backend logic with collision-safe short code generation, click logging, expiry controls, and a full analytics dashboard.

---

## Features

- **Cryptographically-safe short codes** — `random_int()` + Base62, collision-checked against DB
- **Click analytics** — every redirect is logged with IP, user agent, referer, timestamp
- **Expiry controls** — date-based expiry AND click-limit expiry
- **Manual disable** — toggle links active/inactive
- **Dashboard** — total stats, per-link analytics, browser breakdown, IP tracking, daily click charts
- **Clean SaaS UI** — custom CSS, no Bootstrap, professional design
- **API endpoints** — JSON API for creating/toggling/deleting links
- **Security** — prepared statements, CSRF tokens, input validation, XSS escaping

---

## Requirements

- **XAMPP** or **WAMP** (Apache + PHP 7.4+ + MySQL 5.7+)
- Apache `mod_rewrite` enabled

---

## Setup Instructions

### 1. Clone / Copy to Web Root

Place the project folder in your web root:

```
C:\xampp\htdocs\url-shortener\
```

### 2. Create the Database

Open phpMyAdmin or MySQL CLI and run the schema:

```sql
SOURCE C:/xampp/htdocs/url-shortener/database/schema.sql;
```

Or paste the contents of `database/schema.sql` into phpMyAdmin's SQL tab.

### 3. Configure Database Connection

Edit `config/db.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'url_shortener');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Set the Base URL

In `config/db.php`, update `BASE_URL` to match your local setup:

```php
define('BASE_URL', 'http://localhost/url-shortener/');
```

### 5. Enable mod_rewrite

Ensure Apache's `mod_rewrite` is enabled. In XAMPP:

1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Remove the `#` to uncomment it
4. Ensure `AllowOverride All` is set for the `htdocs` directory
5. Restart Apache

### 6. Open in Browser

Navigate to:

```
http://localhost/url-shortener/
```

---

## Folder Structure

```
/url-shortener
│
├── .htaccess              # Apache rewrite rules → front controller
├── index.php              # Front controller / router
│
├── config/
│   └── db.php             # Database config, constants, PDO singleton
│
├── includes/
│   ├── helpers.php         # Sanitization, validation, CSRF, formatting
│   ├── shortcode.php       # CSPRNG-based short code generation
│   ├── links.php           # Link CRUD & status logic
│   └── clicks.php          # Click logging & analytics queries
│
├── public/
│   ├── index.php           # Create link page (landing)
│   ├── dashboard.php       # Analytics dashboard (overview)
│   └── redirect.php        # Redirect handler (performance-critical)
│
├── admin/
│   ├── links.php           # Link management (toggle, delete)
│   └── analytics.php       # Per-link deep analytics
│
├── api/
│   ├── create.php          # POST /api/create — JSON API
│   ├── toggle.php          # POST /api/toggle — toggle active state
│   └── delete.php          # POST /api/delete — delete link
│
├── templates/
│   ├── header.php          # HTML head + navigation
│   ├── footer.php          # HTML footer + JS
│   ├── 404.php             # Clean 404 page
│   └── error.php           # Generic error page (disabled/expired)
│
├── assets/
│   ├── css/
│   │   └── style.css       # Full SaaS-style design system
│   └── js/
│       └── app.js          # Clipboard, alerts, confirm dialogs
│
└── database/
    └── schema.sql          # Full MySQL schema with indexes & FKs
```

---

## Routes

| URL                    | Method | Description                        |
|------------------------|--------|------------------------------------|
| `/`                    | GET    | Create link page                   |
| `/dashboard`           | GET    | Analytics dashboard                |
| `/admin/links`         | GET    | Manage links (toggle/delete)       |
| `/admin/analytics?id=` | GET    | Per-link analytics                 |
| `/api/create`          | POST   | Create link (JSON API)             |
| `/api/toggle`          | POST   | Toggle link active state           |
| `/api/delete`          | POST   | Delete link                        |
| `/{short_code}`        | GET    | Redirect to original URL           |

---

## Short Code Generation

- Uses `random_int()` (CSPRNG) — cryptographically secure
- Base62 alphabet: `a-zA-Z0-9`
- Default length: 7 characters (62^7 ≈ 3.5 trillion combinations)
- Collision check: queries DB before insert, retries up to 10 times
- **Never uses auto-increment IDs** for code generation

---

## Security

- All database queries use **PDO prepared statements**
- All HTML output escaped with `htmlspecialchars()`
- CSRF tokens on all state-changing forms
- URL validation restricts to `http://` and `https://` schemes only
- No open redirects — only DB-stored URLs are redirected to

---

## API Usage

```bash
# Create a link
curl -X POST http://localhost/url-shortener/api/create \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com/long-path", "max_clicks": 100}'

# Toggle a link
curl -X POST http://localhost/url-shortener/api/toggle \
  -H "Content-Type: application/json" \
  -d '{"link_id": 1}'
```

---

## License

MIT