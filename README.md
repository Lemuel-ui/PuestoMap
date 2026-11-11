# PuestoMap — Production Preparation Package

Web-Based Vendor Mapping and Business Permit Monitoring System  
Business Permits and Licensing Office (BPLO), Municipality of Manolo Fortich, Bukidnon

This package preserves the existing PHP/MySQL application and interface. It is a **production-preparation build**, not a claim that the system has been certified or accepted for live government records. Complete the pre-launch checklist and test it on the actual hosting account before real users enter data.

## 1. Requirements

- PHP 8.2 or newer
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `json`
- MySQL 8+ or MariaDB 10.4+
- Apache hosting with `.htaccess` support (or equivalent server rules)
- HTTPS/SSL for all real user access
- A hosting account that supports private storage outside the public web root

## 2. Local installation with XAMPP

1. Extract the ZIP.
2. Copy the `PuestoMap` folder to `C:\xampp\htdocs\PuestoMap`.
3. Start Apache and MySQL in XAMPP.
4. Open phpMyAdmin and create a **new empty** database named `puestomap`.
5. Select that database and import `PuestoMap/database/puestomap.sql`.
6. The default local configuration is `localhost`, port `3306`, database `puestomap`, username `root`, and blank password. This default is for local XAMPP testing only.
7. Open `http://localhost/PuestoMap/`.

**Important:** `database/puestomap.sql` drops and recreates PuestoMap tables. Import it only into a newly created empty database. Never import it over a live database or a database containing records you need to keep.

## 3. Create the first administrator

Generate a password hash from a trusted computer that has PHP installed:

```bash
php -r "echo password_hash('USE_A_NEW_LONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Copy the complete hash. In phpMyAdmin, select the new `puestomap` database, open the SQL tab, and run the query below after replacing the email and hash:

```sql
INSERT INTO users (full_name, email, password_hash, role, is_active)
VALUES (
  'System Administrator',
  'admin@yourdomain.com',
  'PASTE_THE_GENERATED_HASH_HERE',
  'admin',
  1
);
```

Use a unique password of at least 12 characters. Do not use the former demo password or publish the password/hash.

## 4. Choosing hosting (no provider selected yet)

Choose a PHP shared-hosting plan or managed VPS that explicitly provides:
- PHP 8.2+ and the `pdo_mysql` and `fileinfo` extensions
- MySQL/MariaDB database
- Free or supported SSL certificate
- `.htaccess` / Apache compatibility, or equivalent access-control rules
- Scheduled backups and restore capability
- Ability to store files outside the public web directory

A cPanel-based PHP hosting plan is usually the simplest starting point for this project. A domain is not required to begin testing: providers often offer a temporary preview URL, but ask the provider before purchasing. You will need a domain or supported subdomain before public production use.

## 5. Production database configuration

1. In the hosting control panel, create a new database.
2. Create a separate database user with a long unique password.
3. Grant that user access only to the PuestoMap database (only the permissions the app requires).
4. Import `database/puestomap.sql` into that new empty production database.
5. Configure the app using **one** of these methods:

### Method A — Environment variables (preferred)

Set these in the hosting control panel:

```text
APP_ENV=production
DB_HOST=database-host-from-provider
DB_PORT=3306
DB_NAME=cpanelprefix_puestomap
DB_USER=cpanelprefix_dbuser
DB_PASS=your_unique_database_password
PUESTOMAP_BACKUP_DIR=/home/CPANEL_USERNAME/puestomap_private/backups
```

Use the exact database host, database name, and username shown by the hosting provider. Do not assume the database host is `localhost`.

### Method B — Protected local configuration file

If the hosting plan does not let you set environment variables:

1. Copy `includes/local_config.example.php` to `includes/local_config.php`.
2. Edit `includes/local_config.php` and replace the example values with the actual hosting values.
3. Set `APP_ENV` to `production`.
4. Set `PUESTOMAP_BACKUP_DIR` to a full path **outside** `public_html` / the website document root.
5. Keep `includes/local_config.php` private. It is excluded by `.gitignore`, and the `includes/.htaccess` file blocks direct web access to the folder.

Never put actual passwords in `.env.example`, README files, screenshots, public GitHub repositories, or messages.

## 6. Upload and configure the website

1. Enable the domain/subdomain and SSL certificate in the hosting control panel.
2. Prefer setting the website document root to the `PuestoMap` folder itself, if the hosting provider supports a custom document root. If not, upload its contents to the selected website root.
3. Upload the application folders and files. Do not upload any local `local_config.php` containing credentials to a public repository.
4. Confirm `includes/`, `database/`, and `backups/` are not directly accessible from a browser. Preserve their `.htaccess` rules or equivalent server rules.
5. Ensure `uploads/inspections/` is writable by PHP. The included `.htaccess` denies script execution in that folder.
6. Create the private backup folder at the path configured in `PUESTOMAP_BACKUP_DIR`. Give PHP write access, but do not place it inside the public website directory.
7. Update the database configuration and test the home/login page over HTTPS.
8. Create the first administrator account in the production database using the SQL above.
9. Log in as administrator and create the actual staff, head officer, inspector, and vendor accounts.

## 7. Important configuration notes

- `includes/config.php` reads environment variables first, then `includes/local_config.php`, then local XAMPP defaults.
- Production mode refuses the default MySQL `root` user or a blank database password.
- The app detects its URL path automatically. If it is installed in a nonstandard subdirectory and the URL is wrong, configure `$PM_BASE_URL_OVERRIDE` near the top of `includes/config.php`.
- The app uses `Asia/Manila` for date calculations.
- Uploaded inspection photos are restricted by extension, detected MIME type, image validation, and a 5 MB limit.
- Backups are written to `PUESTOMAP_BACKUP_DIR`, outside the public website directory. Confirm this path is writable before relying on the backup feature.
- The SQL installer is destructive. For future schema upgrades, use a reviewed migration script; do not re-import the initial installer.

## 8. Pre-launch security and acceptance checklist

Before entering real data, test all of the following using test accounts and fictional records:

- [ ] Administrator can create and deactivate accounts.
- [ ] Deactivated accounts are denied access even if they had an open session.
- [ ] BPLO Staff can register, edit, and deactivate vendor records.
- [ ] Permit issue/renewal and expiry status work correctly.
- [ ] Head Officer can view the intended monitoring and inspection reports.
- [ ] Inspector can update only inspections assigned to that inspector.
- [ ] Inspector photo uploads reject invalid MIME types, extensions, and oversized files.
- [ ] Vendor can access only the business linked to their account.
- [ ] Vendor location requests are reviewed by authorized BPLO Staff.
- [ ] All state-changing forms reject missing or invalid CSRF tokens.
- [ ] Direct access to `includes/`, `database/`, and private backups is blocked.
- [ ] Backups are created outside the public web root and a restore has been tested against a separate test database.
- [ ] All roles are tested on desktop and mobile browsers.
- [ ] HTTPS is enforced and no database credentials are exposed in the browser or repository.
- [ ] The office has approved the data fields, access roles, retention policy, backup schedule, and operational procedures.

## 9. Current security scope and limitations

This package includes CSRF validation on the login, vendor registration/edit/management, permit renewal, location review, admin account, backup/restore, and inspector update forms. Protected pages re-check that the logged-in account still exists and is active.

This is still not a full security certification. Before public launch, have the responsible system administrator review the full authorization model, data privacy requirements, backup restore process, hosting configuration, HTTPS setup, and operational recovery plan. In particular, test each role's access to every record and action using non-production data.

## Main modules

- Admin / BPLO Head Officer: dashboard, user accounts, account approvals, backup/restore, vendor map, permit monitoring, inspection reports
- BPLO Staff: dashboard, vendor registration/management, permits, location requests, map
- Inspector: dashboard, assigned inspections, inspection photos, map
- Vendor: dashboard, profile, permit status


## Self-registration and administrator approval

PuestoMap allows public registration requests only for **BPLO Staff**, **Business Permit Inspector**, and **Vendor** roles. The public registration form is at `/register/index.php`.

A submitted registration is stored in `account_registration_requests` with status `pending`. It is NOT inserted into `users` and cannot log in. The Admin / BPLO Head Officer signs in, opens **Account Approvals** in the sidebar, and chooses Approve or Reject. Approving creates an active user record in `users`; rejecting leaves no login account. The combined Admin / BPLO Head Officer role cannot be requested through the public form and must be assigned manually by an existing administrator.

For an existing installation, back up the database, then run `database/migrations/20261106_account_registration_approval.sql` followed by `database/migrations/20261109_combine_admin_head_officer.sql` in phpMyAdmin against the existing `puestomap` database. The second migration converts existing `head_officer` accounts to `admin` and removes the old role from the `users` table. For a fresh installation, import the updated `database/puestomap.sql` into a new empty database; the combined role is already in its schema.

Vendor self-registration creates the vendor's login account after approval. The business profile itself must still be registered/linked by BPLO Staff using the existing Vendor Registration module. Until that is done, the vendor dashboard will show that no business record is linked.

**Operational note:** Verify the applicant's identity and authority before approving staff or inspector requests. Public registration is not proof that an applicant is an authorized municipal employee.
