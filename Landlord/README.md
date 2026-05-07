# TenureX — Portfolio Premium Management

A property-management web application for landlords. Built as a clean rebuild on top of Tailwind CSS, plain JavaScript, and (planned) PHP + MySQL on the backend.

---

## 1. Project Structure

```
new-build/
├── index.html                 # Landing redirect
├── css/
│   └── style.css              # Custom CSS (Tailwind handles 99%)
├── js/
│   └── script.js              # Shared JS (3-dot menus, etc.)
├── pages/                     # All UI pages (one file per route)
│   ├── dashboard.html
│   ├── messages.html
│   ├── my-properties.html
│   ├── add-property.html
│   ├── edit-property.html
│   ├── rental-requests.html
│   ├── rental-request-detail.html
│   ├── active-tenants.html
│   ├── rent-tracking.html
│   ├── earnings.html
│   ├── maintenance.html
│   ├── maintenance-detail.html
│   ├── log-request.html
│   ├── technician-request.html
│   └── settings.html
├── partials/                  # PHP includes (sidebar, header, etc.)
│   ├── head.php
│   ├── sidebar.php
│   ├── header.php
│   └── foot.php
├── backend/                   # PHP backend (when wired up)
│   ├── config.example.php
│   ├── db.php
│   └── api/
│       └── properties.php     # Sample REST endpoint
├── db/
│   └── schema.sql             # MySQL schema for all entities
└── README.md
```

---

## 2. Tech Stack

| Layer       | Technology                              |
|-------------|-----------------------------------------|
| Markup      | HTML5                                   |
| Styling     | Tailwind CSS (CDN, no build)            |
| Client JS   | Plain JavaScript (ES5-friendly)         |
| Backend     | PHP 8+ (planned)                        |
| Database    | MySQL 8 / MariaDB 10+                   |
| Dev server  | Python `http.server` or `php -S`        |

No frameworks. No build step. The code stays readable and explainable.

---

## 3. Running Locally

### Static (HTML preview only)
```bash
python -m http.server 8000 --directory new-build
# open http://localhost:8000
```

### Full stack (PHP backend)
```bash
cp new-build/backend/config.example.php new-build/backend/config.php
# edit DB credentials inside config.php

mysql -u root -p < new-build/db/schema.sql

php -S localhost:8000 -t new-build
# open http://localhost:8000
```

---

## 4. Migrating HTML pages to PHP

The `partials/` folder already contains the reusable sidebar, header, etc. To convert any page to PHP:

1. Rename `pages/foo.html` → `pages/foo.php`
2. Replace the `<head>`, sidebar, and top header blocks with `<?php include __DIR__ . '/../partials/head.php'; ?>` etc.
3. See `pages/dashboard.php` for a fully-converted reference.

---

## 5. Coding Conventions

- **Naming**: kebab-case for files (`rental-requests.html`), camelCase for JS variables, snake_case for PHP variables and SQL columns.
- **Forms**: every input has a `name` attribute. Every form has `method` and `action`.
- **Modals**: `hidden fixed inset-0 bg-gray-500/70 z-50 flex items-center justify-center` — see `rental-requests.html` for the pattern.
- **Icons**: inline SVG (no icon library) so they're tweakable per-page.
- **Comments**: each non-trivial section starts with `<!-- ============ NAME ============ -->`.

---

## 6. Database Schema (overview)

See `db/schema.sql` for full SQL. Tables:

- `users` — landlords (and future tenants/staff)
- `properties` — owned assets
- `units` — rooms/apartments inside a property
- `tenants` — active lease holders
- `rental_requests` — applications awaiting review
- `leases` — signed lease agreements
- `payments` — rent ledger
- `maintenance_requests` — issue tracker
- `technicians` — repair contractors
- `messages` — chat threads

---

## 7. Security Checklist (when wiring up PHP)

- [ ] Use **PDO** with prepared statements (never concatenate SQL)
- [ ] Store passwords with `password_hash()` / verify with `password_verify()`
- [ ] Validate every form field on the server, not just the browser
- [ ] Add a CSRF token to every POST form (`<input type="hidden" name="_csrf" ...>`)
- [ ] Escape all output with `htmlspecialchars()`
- [ ] Keep `config.php` out of git — use `.gitignore`
- [ ] Set `session.cookie_httponly = 1` and `session.cookie_secure = 1` in production
- [ ] Validate file uploads (mime type, size, extension whitelist)

---

## 8. Author

Built by Sadik Salman as a portfolio / coursework project. Designs sourced from Figma, rebuilt for clean, explainable code.
