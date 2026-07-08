# ContentLite (EN)

ContentLite is a lightweight PHP CMS with a news module, image gallery, admin panel, and browser-based installer. This English edition uses English route names, schema identifiers, setup text, and admin UI copy.

## Highlights

- News publishing with TinyMCE editing
- Gallery albums with image upload, editing, sorting, and lightbox viewing
- First-run installer with optional table prefix support
- Shared-template front end with responsive navigation
- Plain PHP + MySQLi stack with minimal moving parts

## Requirements

- PHP 8.0+
- MySQL or MariaDB
- PHP extensions: `mysqli`, `mbstring`, `dom`, `fileinfo`
- Write access if you want the installer to update `config.php`

## Quick start

1. Upload the project to your server.
2. Open `install.php` in the browser.
3. Enter your database credentials.
4. Optionally set a table prefix for shared databases.
5. Complete installation and remove or protect `install.php`.

## Default admin access

- Username: `admin`
- Password seed in code: `change_this_password`

Change the password hash in `config.php` before production use.

## Project structure

- `index.php`: homepage
- `news.php`: public news listing and single article pages
- `gallery.php`: public gallery and album pages
- `admin.php`: admin panel for content and gallery management
- `install.php`: browser installer
- `schema.sql`: canonical database schema
- `assets/_header.php`: shared site header
- `assets/_footer.php`: shared site footer

## Database notes

- Base tables: `cms_content`, `gallery_albums`, `gallery_images`
- Optional table prefixes are supported through the installer and `TABLE_PREFIX`

## Deployment notes

- Set `SITE_BASE_URL` correctly in `config.php`
- Confirm your upload directory is writable
- `install.php` gets locked after setup
- Replace placeholder database credentials before going live
- And otherwise alter/tweak the necessary CSS-selectors params for your layout-requirements and -desires
