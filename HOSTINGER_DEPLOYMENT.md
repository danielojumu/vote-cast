# BUI Beta Set Alumni Election Voting System (VCAST)
## Hostinger Business Web Hosting Deployment Guide

This document provides step-by-step instructions to deploy **VCAST** on Hostinger Business Web Hosting for `https://vote.buibetaset.org`.

---

### STEP 1: MySQL Database Setup on Hostinger (phpMyAdmin)

1. Log in to your **Hostinger hPanel** at `https://hpanel.hostinger.com`.
2. Navigate to **Databases** -> **Management**.
3. Create a new MySQL Database:
   - **Database Name**: `u123456789_vcast` (Note: Hostinger prefixes with your username)
   - **Database Username**: `u123456789_vcast_user`
   - **Database Password**: Set a strong password (e.g. `BuiBeta2026!Secure`)
4. Click **Create**.
5. Once created, click **Enter phpMyAdmin** next to the newly created database.
6. In phpMyAdmin, click the **Import** tab at the top.
7. Click **Choose File** and select `database/schema.sql` from this codebase.
8. Click **Import** at the bottom.
9. Verify that the 7 tables (`admins`, `registrations`, `voters`, `positions`, `candidates`, `votes`, `audit_logs`) were imported and seeded.

---

### STEP 2: File Upload via Hostinger File Manager

1. In hPanel, go to **Files** -> **File Manager**.
2. Navigate to the domain/subdomain root directory for `vote.buibetaset.org` (or `public_html/vcast`).
3. Upload all project files and directories (`app/`, `database/`, `public/`, `routes/`, `storage/`, `vendor/`, `.env`, `composer.json`).
4. **Setting Document Root**:
   - In Hostinger hPanel, go to **Domains** -> **Subdomains** -> Edit Document Root for `vote.buibetaset.org` to point to `/public` directory (e.g., `/public_html/vcast/public`).
   - *Alternative (.htaccess)*: If document root cannot be changed, place an `.htaccess` in the root folder redirecting traffic to `public/`:
     ```apache
     <IfModule mod_rewrite.c>
         RewriteEngine On
         RewriteRule ^$ public/ [L]
         RewriteRule (.*) public/$1 [L]
     </IfModule>
     ```

---

### STEP 3: Configure `.env` Environment File

1. In Hostinger File Manager, create or edit the `.env` file in the project root:
   ```env
   APP_NAME="BUI Beta Set Alumni Voting"
   APP_ENV="production"
   APP_URL="https://vote.buibetaset.org"

   # Database Configuration (Hostinger MySQL)
   DB_HOST="localhost"
   DB_PORT="3306"
   DB_NAME="u123456789_vcast"
   DB_USER="u123456789_vcast_user"
   DB_PASS="BuiBeta2026!Secure"
   DB_CHARSET="utf8mb4"

   # Hostinger SMTP Settings
   SMTP_HOST="smtp.hostinger.com"
   SMTP_PORT=465
   SMTP_SECURE="ssl"
   SMTP_USERNAME="noreply@vote.buibetaset.org"
   SMTP_PASSWORD="YourHostingerEmailPassword123!"
   SMTP_FROM="noreply@vote.buibetaset.org"
   SMTP_NAME="BUI Beta Set Alumni Electoral Committee"

   SESSION_LIFETIME=3600
   SESSION_SECURE=true
   ```

---

### STEP 4: Email & Hostinger Webmail Setup

1. In Hostinger hPanel, go to **Emails** -> **Email Accounts**.
2. Create email address `noreply@vote.buibetaset.org`.
3. Note the password and update `SMTP_PASSWORD` in `.env`.
4. Test email dispatch by completing a test alumni registration.

---

### STEP 5: Directory Permissions & Storage

1. Ensure the following directories have write permissions (755 or 775):
   - `storage/logs/`
   - `public/uploads/candidates/`
2. If uploading candidates' photos, ensure `public/uploads/candidates` is publicly readable via web browser.

---

### STEP 6: Composer Dependencies

1. If SSH access is enabled in Hostinger hPanel (**Advanced** -> **SSH Access**):
   ```bash
   cd public_html/vcast
   composer install --no-dev --optimize-autoloader
   ```
2. If SSH is not enabled, upload the local `vendor/` directory generated via `composer install`.

---

### STEP 7: Security Verification & Going Live

1. Ensure SSL (HTTPS) is activated in Hostinger hPanel (**Security** -> **SSL**).
2. **Creating & Managing Admin Accounts**:
   - Demo credential hints have been completely removed from the frontend `admin.php` interface for maximum security.
   - **Method A (Web Utility)**: Visit `https://vote.buibetaset.org/create_admin.php` on your site. Enter the official commissioner's name, email, and password. Click **Insert Directly into DB** or **Generate SQL Query**.
   - **Method B (phpMyAdmin SQL)**: Run a query with a bcrypt hash in phpMyAdmin:
     ```sql
     INSERT INTO `admins` (`fullname`, `email`, `password`, `role`)
     VALUES ('Electoral Chairman', 'admin@vote.buibetaset.org', '$2y$10$fV3GsnT1zT2QvjN9YxHq/eXmY7wY09kZkC8Q.8C3eL/xG3C2q3b/S', 'Super Admin')
     ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);
     ```
   - **CRITICAL SECURITY STEP**: Delete or rename `create_admin.php` from your server immediately after creating your initial admin user so unauthorized persons cannot generate admin accounts.

---

### Troubleshooting & Blank Screen Fixes

#### 1. Why Am I Seeing a Blank Screen?
A blank screen on Hostinger typically occurs for one of three reasons:
1. **Document Root Mismatch (PHP Deployment)**: Hostinger serves files from `public_html/` by default. Since the PHP entry point is located at `public/index.php`, visiting `vote.buibetaset.org` looks for `index.php` in the root folder. If no root `.htaccess` or `index.php` exists, Hostinger serves a blank page.
2. **Uploading Raw Source Files Instead of Static Build (`dist/`)**: If deploying the React SPA, uploading raw `.tsx` files directly without building causes browsers to fail parsing JSX.
3. **Missing `.htaccess` for SPA Routing**: Refreshing or visiting non-root paths returns a 404 or blank screen because Apache isn't configured to route traffic to `index.html`.

#### 2. Quick Fix Checklist for Hostinger:
- **For PHP Backend Deployment**:
  - Upload the newly created `/.htaccess` file to your domain root (`public_html` or subdomain directory). It automatically forwards incoming requests to `/public/index.php`.
  - Alternatively, in Hostinger hPanel, go to **Domains** -> **Subdomains** -> Edit Document Root for `vote.buibetaset.org` and set it to `/public_html/public`.
  - Ensure PHP version is set to **PHP 8.2 or 8.3** in hPanel (**Advanced** -> **PHP Configuration**).

- **For React Static Build Deployment (`dist/`)**:
  - Run `npm run build` locally or export the `dist/` directory.
  - Upload **only the contents inside `dist/`** (i.e. `index.html` and the `assets/` folder) directly into your Hostinger `public_html/` directory.
  - Ensure `/.htaccess` is present in `public_html/` with the following rule:
    ```apache
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.html [L]
    </IfModule>
    ```

- **Database Connection Issues**:
  - Confirm `DB_NAME` and `DB_USER` in `.env` include the Hostinger prefix (e.g. `u123456789_vcast`).
