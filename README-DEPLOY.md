# Venable & Vine — Deployment Guide

## Requirements

- cPanel hosting with PHP 8.0+ and MySQL 5.7+ / MariaDB 10.3+
- PDO MySQL extension enabled
- `fileinfo` extension (for upload validation)

## Deployment Steps

### 1. Upload files

Upload the project to your hosting account. Point your domain document root to the `public/` folder:

**Option A (recommended):** Set document root to `public/`  
**Option B:** Move contents of `public/` into `public_html/` and place `includes/`, `config/`, `sql/` one level above (outside web root if possible)

### 2. Run the web installer (recommended)

Visit `https://yourdomain.com/install.php` in your browser. The installer walks you through:

1. **Database** — enter cPanel MySQL credentials (tests connection, saves `config/database.php`)
2. **Tables** — creates all database tables automatically from `sql/schema.sql`
3. **Admin** — create the first admin login

**Delete `public/install.php` immediately after setup.**

### Manual alternative

If you prefer phpMyAdmin instead of the installer:

1. In cPanel → MySQL Databases, create a database and user
2. Copy `config/database.example.php` to `config/database.php` and fill in credentials
3. Import `sql/schema.sql` via phpMyAdmin
4. Visit `/install.php` only for Step 3 (admin account), or create admin via SQL

### 3. Configure settings

Log in at `/admin/login.php` and visit **Settings** to:

- Upload logo and favicon
- Set contact notification email
- Add social media URLs
- Add Google Maps API key (for embedded maps)
- Add reCAPTCHA keys (optional, for spam protection)

## Admin Features

| Page | Purpose |
|------|---------|
| Page Builder | Drag-and-drop homepage sections |
| Menu | Categories & items with prices, photos, dietary tags |
| Events | Schedule truck locations with map support |
| Gallery | Photo uploads with drag reorder |
| Reviews | Approve customer reviews |
| Contacts | Contact form inbox |
| Newsletter | Collect & export emails; Mailchimp-ready |
| Users | Manage 2–5 admin accounts (owners only) |
| Ordering (Soon) | Phase 2 placeholder |

## Phase 2: Online Ordering

Database tables `orders` and `order_items` are pre-created. See `/admin/ordering.php` for planned features.

## Mailchimp Integration (Future)

Add `mailchimp_api_key` and `mailchimp_list_id` in Settings. The `NewsletterService::syncToMailchimp()` method in `includes/NewsletterService.php` is a stub ready to wire up.

## Security Checklist

- [ ] Delete `install.php` after setup
- [ ] Use strong admin passwords
- [ ] Keep `config/database.php` outside public web root if possible
- [ ] Enable HTTPS
- [ ] Set up reCAPTCHA for public forms

## Git Version Control

See [README-GIT.md](README-GIT.md) for how to push the project to GitHub and deploy updates to cPanel.

## Local Development

If using XAMPP/WAMP, point virtual host document root to `public/` and create a local MySQL database matching `config/database.php`.

## Brand Assets

Legacy images are not included in the repo. Upload logo, hero background, and story photos via **Settings** or reference paths in the Page Builder after uploading to `public/uploads/`.
