# Deployment Guide

Three supported deployment paths: **VPS (recommended)**, **Docker in production**, and **shared/managed hosting**. All three require PHP 8.1+, MySQL 8.0+, and Apache or Nginx with rewrite support.

---

## Option A — VPS / Bare Metal (Ubuntu 22.04 LTS)

### 1. Install dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip libapache2-mod-php8.1
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. Create the database

```bash
sudo mysql -uroot
```

```sql
CREATE DATABASE empandahub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'empanda'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON empandahub.* TO 'empanda'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Clone and configure

```bash
cd /var/www
sudo git clone https://github.com/jkmills/EmpandaHub.git empandahub
sudo chown -R www-data:www-data empandahub
sudo chmod -R 755 empandahub
sudo chmod -R 775 empandahub/public/uploads
```

### 4. Apache virtual host

Create `/etc/apache2/sites-available/empandahub.conf`:

```apache
<VirtualHost *:80>
    ServerName app.yourorg.org
    DocumentRoot /var/www/empandahub/public

    <Directory /var/www/empandahub/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Installer — only needed during initial setup
    Alias /install /var/www/empandahub/install
    <Directory /var/www/empandahub/install>
        AllowOverride None
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/empandahub_error.log
    CustomLog ${APACHE_LOG_DIR}/empandahub_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite empandahub
sudo a2dissite 000-default
sudo systemctl reload apache2
```

### 5. Run the installer

Navigate to `http://app.yourorg.org/install/install.php` and complete the form. The installer:
- Creates `config/config.php` with your database credentials and `APP_URL`
- Runs `install/schema.sql` to build the database
- Creates your first super admin account

Once complete, the installer checks for the presence of `config/config.php` and refuses to run again.

### 6. SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d app.yourorg.org
```

Certbot updates the virtual host automatically. Renewals run via the certbot systemd timer — verify with:

```bash
sudo certbot renew --dry-run
```

After SSL is live, update `APP_URL` in `config/config.php` to use `https://`.

### 7. File permissions (production)

```bash
# Web server needs write access only to uploads
sudo chown -R root:www-data /var/www/empandahub
sudo chmod -R 750 /var/www/empandahub
sudo chown -R www-data:www-data /var/www/empandahub/public/uploads
sudo chmod -R 775 /var/www/empandahub/public/uploads

# config.php should not be world-readable
sudo chmod 640 /var/www/empandahub/config/config.php
```

### 8. PHP production settings

Create `/etc/php/8.1/apache2/conf.d/99-empandahub.ini`:

```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/empandahub_errors.log
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
upload_max_filesize = 25M
post_max_size = 26M
```

```bash
sudo mkdir -p /var/log/php && sudo touch /var/log/php/empandahub_errors.log
sudo chown www-data:www-data /var/log/php/empandahub_errors.log
sudo systemctl restart apache2
```

### 9. Cron jobs

```bash
sudo crontab -u www-data -e
```

Add (adjust paths and frequency as needed):

```cron
# Engagement score recalculation — nightly at 2am
0 2 * * * php /var/www/empandahub/cron/engagement_score.php >> /var/log/empandahub_cron.log 2>&1
```

> The engagement score cron script will be added when Issue #3 (Donor Engagement Score) is implemented.

---

## Option B — Docker in Production

Use this when you prefer container-based deployments (e.g., DigitalOcean Droplet, Hetzner VPS, AWS EC2).

### 1. Server setup

```bash
# Install Docker and Compose
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

### 2. Production compose file

Create `docker-compose.prod.yml` (do **not** expose MySQL or phpMyAdmin publicly):

```yaml
services:
  web:
    image: php:8.1-apache
    container_name: empandahub_web
    restart: unless-stopped
    volumes:
      - .:/var/www/html
      - ./docker/apache.conf:/etc/apache2/sites-enabled/000-default.conf
      - uploads:/var/www/html/public/uploads
    depends_on:
      - db
    environment:
      APP_ENV: production
    command: >
      bash -c "
        docker-php-ext-install pdo pdo_mysql &&
        a2enmod rewrite &&
        apache2-foreground
      "

  db:
    image: mysql:8.0
    container_name: empandahub_db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: empandahub
      MYSQL_USER: empanda
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
      - ./install/schema.sql:/docker-entrypoint-initdb.d/schema.sql

  nginx:
    image: nginx:alpine
    container_name: empandahub_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./certbot/conf:/etc/letsencrypt
      - ./certbot/www:/var/www/certbot
    depends_on:
      - web

  certbot:
    image: certbot/certbot
    volumes:
      - ./certbot/conf:/etc/letsencrypt
      - ./certbot/www:/var/www/certbot

volumes:
  db_data:
  uploads:
```

Create a `.env` file (never commit this):

```bash
DB_ROOT_PASSWORD=your_very_strong_root_password
DB_PASSWORD=your_app_db_password
```

### 3. Nginx config

Create `docker/nginx.conf`:

```nginx
server {
    listen 80;
    server_name app.yourorg.org;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    server_name app.yourorg.org;

    ssl_certificate     /etc/letsencrypt/live/app.yourorg.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.yourorg.org/privkey.pem;

    location / {
        proxy_pass         http://empandahub_web:80;
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

### 4. Obtain SSL certificate

```bash
# Start containers without SSL first
docker compose -f docker-compose.prod.yml up -d nginx certbot

# Obtain certificate
docker compose -f docker-compose.prod.yml run --rm certbot certonly \
    --webroot -w /var/www/certbot \
    -d app.yourorg.org \
    --email you@yourorg.org \
    --agree-tos --no-eff-email

# Start everything
docker compose -f docker-compose.prod.yml up -d
```

### 5. Run installer via Docker

```bash
# Navigate to http://app.yourorg.org/install/install.php in your browser
# OR run the schema directly:
docker exec -i empandahub_db mysql -uempanda -p${DB_PASSWORD} empandahub < install/schema.sql
```

### 6. SSL renewal (cron on the host)

```bash
crontab -e
```

```cron
0 3 * * * docker compose -f /opt/empandahub/docker-compose.prod.yml run --rm certbot renew --quiet && docker compose -f /opt/empandahub/docker-compose.prod.yml exec nginx nginx -s reload
```

---

## Option C — Shared / Managed Hosting (cPanel)

Use when you don't have SSH root access or prefer managed infrastructure.

### Requirements

- PHP 8.1+ with PDO, pdo_mysql, mbstring extensions enabled
- MySQL 5.7+ or 8.0 database
- `.htaccess` support (mod_rewrite enabled)

### Steps

1. **Create a database** in cPanel → MySQL Databases. Note the host, name, user, and password.

2. **Upload files** via cPanel File Manager or FTP to your domain's `public_html/` (or a subdirectory). Upload the full repository contents.

3. **Point the document root** to the `public/` subdirectory:
   - If you have access to Apache vhost config, set `DocumentRoot` to `public/`.
   - If not, add a `.htaccess` in the root that redirects to `public/`:
     ```apache
     RewriteEngine On
     RewriteRule ^(.*)$ public/$1 [L]
     ```
   This `.htaccess` is already included in the repository.

4. **Run the installer** at `https://yourdomain.com/install/install.php`. Use the database credentials from step 1. Set `APP_URL` to your full domain with `https://`.

5. **Set upload permissions** — ensure `public/uploads/` is writable by the web server (typically chmod 775 or 755 depending on the host).

6. **SSL** — use cPanel's built-in Let's Encrypt integration (AutoSSL) or upload an existing certificate.

---

## Updating

```bash
cd /var/www/empandahub
git pull origin main
```

There is currently no automated migration system. Schema changes are documented in commit messages and tagged with the relevant GitHub issue. Apply them manually:

```bash
# Example: apply a schema change for a new feature
mysql -uempanda -p empandahub < install/migrations/0001_add_engagement_score.sql
```

> Migration files will live in `install/migrations/` as new modules are built.

---

## Backups

Use the built-in **Data → Backup** feature (JSON export by module) for application-level data backups.

For full database backups (recommended nightly on production):

```bash
# VPS
mysqldump -uempanda -p empandahub | gzip > /backups/empandahub_$(date +%Y%m%d).sql.gz

# Docker
docker exec empandahub_db mysqldump -uroot -psecret empandahub | gzip > /backups/empandahub_$(date +%Y%m%d).sql.gz
```

Back up `public/uploads/` separately — it contains logo files and any uploaded documents.

---

## Security Checklist

- [ ] `config/config.php` is not world-readable (`chmod 640`)
- [ ] `APP_ENV` is set to `production` in config
- [ ] `display_errors` is `Off` in `php.ini`
- [ ] `public/uploads/` does not allow PHP execution (add `php_flag engine off` in a `.htaccess` inside uploads/)
- [ ] Database user has only `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX` on the app database — not `SUPER` or `FILE`
- [ ] MySQL is not publicly accessible (bind to `127.0.0.1` or Docker internal network only)
- [ ] SSL certificate is valid and HTTP redirects to HTTPS
- [ ] Session cookies set to `Secure` and `HttpOnly` (enforced in `Auth::start()` and your php.ini)
- [ ] `/install/` directory is removed or blocked after initial setup
