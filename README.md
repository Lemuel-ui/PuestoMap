# PuestoMap
### Web-Based Vendor Mapping and Business Permit Monitoring System
Business Permits and Licensing Office (BPLO) — Municipality of Manolo Fortich, Bukidnon

PuestoMap is a PHP + MySQL/MariaDB application for vendor mapping and business permit monitoring. The existing interface and modules are retained.

## Technology
- PHP 8.2+ recommended (PDO MySQL extension enabled)
- MySQL 8+ or MariaDB 10.4+
- Apache with PHP support (XAMPP for local development; PHP-compatible hosting for online use)
- Existing front-end assets and map resources

## Local setup (XAMPP)
1. Copy the `PuestoMap` folder into `C:\xampp\htdocs\PuestoMap`.
2. Start Apache and MySQL.
3. Create a NEW empty database named `puestomap` in phpMyAdmin.
4. Import `database/puestomap.sql`.
5. Local defaults use `localhost`, database `puestomap`, user `root`, and an empty password. Change these if your local MySQL differs.
6. Open `http://localhost/PuestoMap/`.
7. Create the first administrator account using the SQL instructions below.

## First administrator account
Generate a password hash using PHP CLI on a trusted machine/server:

```bash
php -r "echo password_hash('USE_A_NEW_LONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Then execute in the new database, replacing the email and hash:

```sql
INSERT INTO users (full_name, email, password_hash, role, is_active)
VALUES ('System Administrator', 'admin@yourdomain.com', 'PASTE_PASSWORD_HASH_HERE', 'admin', 1);
```

No demo accounts or sample records are included. Use a unique password; never reuse the former demo credentials.

## Online hosting configuration
1. Choose PHP-compatible hosting with PHP 8.2+, PDO MySQL, MySQL/MariaDB, HTTPS, and scheduled backups.
2. Create a NEW production database and a dedicated database user with access only to that database.
3. Configure the environment variables shown in `.env.example` in the hosting control panel. Do not put production credentials in a public repository.
4. Set `APP_ENV=production`. The app refuses the default root/blank-password database configuration in production mode.
5. Upload the project and configure the domain's document root.
6. Enable HTTPS before allowing real users to sign in.
7. Ensure `uploads/inspections/` is writable by PHP but cannot execute scripts. Keep `backups/` and `database/` inaccessible from the public web.
8. Import the schema only into a new/empty database. The SQL installer drops and recreates the application's tables; never run it against a live database.
9. Create the first administrator account, then create actual staff, inspector, head officer, and vendor accounts through the application.
10. Test every role with non-production test accounts before entering real records.

## Important security and deployment notes
- Login and inspector inspection-update forms have CSRF token validation in this preparation build. Apply the same `csrf_field()` and `verify_csrf()` pattern to every remaining state-changing POST form before public launch.
- Inspector inspection updates are scoped to the logged-in inspector and pending assigned inspections.
- Uploaded inspection images use random filenames. Keep server-side MIME/type/size validation.
- Store backups outside the public web root and test restores using a separate test database.
- Review all role permissions, data-ownership checks, upload paths, admin restore workflow, and error handling before launch.
- This is a production-preparation build, not a security certification. Complete acceptance testing before real records are stored.

## Main modules
- MISU Administrator: dashboard, user accounts, backup/restore, vendor map
- BPLO Staff: dashboard, vendor registration/management, permits, location requests, map
- BPLO Head Officer: dashboard, permit monitoring, inspection reports, map
- Inspector: dashboard, assigned inspections, inspection photos, map
- Vendor: dashboard, profile, permit status
