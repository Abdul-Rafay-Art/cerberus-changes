# CyberPanel deployment checklist

This folder contains the production deployment checklist for the Cerberus Laravel application. It does not contain credentials. Do not commit `.env` files, database passwords, SMTP credentials, SSH keys, or customer data here.

## Upload package

- `cerberus-cyberpanel.zip` is the prepared application-source archive.
- The same source tree is available under `site/` if you prefer to inspect or upload files individually.
- The archive includes the Laravel application, migrations, seeders, public assets (including the local booking hero image), `composer.json`, and `composer.lock`. It deliberately excludes `vendor/` so the server can install production-only dependencies, as well as the real `.env`, local SQLite database, logs, compiled caches, and private report/photo data.
- `site/.env.example` is a production-oriented template with placeholders, not credentials. Copy it to `.env` on the server and fill secrets in CyberPanel or a protected file outside public access.

## Before deployment

- Confirm the production domain points to the CyberPanel server and an HTTPS certificate can be issued.
- Obtain the client-approved package catalogue, prices, checklist wording, rating scheme, logo/report approval, and SMTP sender details. Do not publish the local demo package data.
- Confirm the hosting plan provides PHP 8.2+ and Composer 2, MySQL 8+, and these PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, and `zip`.
- Confirm PHP upload limits support up to 20 photos per upload: `upload_max_filesize=10M`, `post_max_size=210M`, and `max_file_uploads>=20`.
- Arrange a restricted MySQL database/user, approved SMTP credentials, private writable storage, backups, and monitoring.

## CyberPanel / OpenLiteSpeed setup

1. Create the website/virtual host in CyberPanel and enable HTTPS.
2. Keep the Laravel project outside the publicly served directory where possible. Set the virtual-host document root to the project's `public/` directory only; never expose the project root, `.env`, `storage/`, or `vendor/` over HTTP.
3. Select PHP 8.2+ for the site and enable the required extensions listed above.
4. Create a MySQL database and a dedicated least-privilege database user. Do not reuse the CyberPanel administrator account.
5. Ensure the web-server/PHP user can write to `storage/` and `bootstrap/cache/`. Use ownership and narrowly scoped permissions; do not use `chmod 777`.

## Application release

1. Upload and extract `cerberus-cyberpanel.zip` into a private application directory (for example, a directory outside `public_html`). Set the virtual-host document root to that directory's `public/` subdirectory. The local booking hero image is included, so the site does not rely on Unsplash being reachable.
2. Install production dependencies from the project root:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```

3. Create a protected production `.env` (or use CyberPanel's protected environment settings). Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, a unique generated `APP_KEY`, MySQL credentials, private local storage, and approved SMTP settings. Keep local demo admin variables unset. Never paste secrets into this file in the repository or commit it.
4. Take a database and private-storage backup before migration.
5. Apply migrations:

   ```sh
   php artisan migrate --force
   ```

6. Import only the approved package catalogue. Validate it first with `--dry-run`, review the output, then import it:

   ```sh
   php artisan cerberus:import-catalogue storage/app/approved-catalogue.json --dry-run
   php artisan cerberus:import-catalogue storage/app/approved-catalogue.json
   ```

7. Create the first administrator interactively with `php artisan cerberus:create-admin`; transfer the password through a secure channel, not email or this repository.
8. Build Laravel's caches and restart/reload the site's PHP process as needed:

   ```sh
   php artisan optimize
   ```

9. Run the production gate:

   ```sh
   php artisan cerberus:check-production
   ```

   Do not release unless every check passes.

## Release smoke checks

- Review the registered routes with `php artisan route:list`; confirm staff endpoints retain authentication/active-staff middleware and admin endpoints retain the admin middleware. Run the feature test suite after route or middleware changes.
- `/up`, `/`, `/book-inspection`, `/api/packages`, and `/staff/login` respond successfully over HTTPS.
- Public package lists contain only approved production packages; no demo data is active.
- Submit one authorized test booking; confirm staff can authenticate, confirm the appointment, and receive the expected email through approved SMTP.
- Start an inspection, test autosave, upload private evidence, complete the checklist, generate a PDF, and verify a customer token can view/download only its report.
- Verify invalid, revoked, and expired report links are denied; verify private photo/PDF files cannot be fetched directly from a public path.
- Verify Laravel's local-storage serving route rejects unsigned requests; only explicitly authorized/signed file access should work.
- Test YouTube only if the approved Gold catalogue enables it; confirm the video owner permits embedding and browser autoplay behavior is acceptable.
- Confirm HTTPS, session cookies, log handling, backup completion, and restore procedure.
- Remove test personal data after verification.

## Backup and recovery

Back up the MySQL database and `storage/app/private` together, because report records reference private PDFs and uploaded inspection evidence. Keep encrypted backups access-restricted and outside the web root; agree retention and deletion with the data owner. Test restores in a non-production environment before relying on them.

For the fuller project deployment and handover notes, see [`docs/deployment-handover.md`](../docs/deployment-handover.md).
