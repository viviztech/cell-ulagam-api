# Deploying Cell Ulagam API to Hostinger

## Prerequisites
- Hostinger Cloud hosting plan
- Domain or subdomain (e.g., api.yourdomain.com)
- SSH access enabled

## Step 1: Create MySQL Database

1. Login to Hostinger hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database:
   - Database name: `cellulagam_db`
   - Username: `cellulagam_user`
   - Password: (generate a strong password)
4. Note down these credentials

## Step 2: Create Subdomain (if needed)

1. In hPanel, go to **Domains** → **Subdomains**
2. Create subdomain: `api.yourdomain.com`
3. Point it to: `/public_html/api` folder

## Step 3: Upload Files via File Manager or SSH

### Option A: Using File Manager
1. Go to **Files** → **File Manager**
2. Navigate to `/public_html/`
3. Create folder `api`
4. Upload all project files to `/public_html/api/`

### Option B: Using SSH (Recommended)
```bash
# Connect to Hostinger via SSH
ssh u123456789@yourdomain.com -p 65002

# Navigate to public_html
cd public_html

# Create api folder
mkdir api
cd api

# Clone your repo (if using Git)
git clone https://github.com/yourusername/cell-ulagam-api.git .

# Or upload via SCP from your local machine:
# scp -P 65002 -r /Users/ganeshthangavel/Sites/cell-ulagam-api/* u123456789@yourdomain.com:~/public_html/api/
```

## Step 4: Configure Environment

1. Rename `.env.production` to `.env`
2. Edit `.env` with your Hostinger database credentials:

```env
APP_NAME="Cell Ulagam API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cellulagam_db
DB_USERNAME=cellulagam_user
DB_PASSWORD=your_database_password

SANCTUM_STATEFUL_DOMAINS=api.yourdomain.com
```

3. Generate app key via SSH:
```bash
cd ~/public_html/api
php artisan key:generate
```

## Step 5: Install Dependencies

Via SSH:
```bash
cd ~/public_html/api

# Install composer dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 6: Run Migrations and Seed

```bash
# Run migrations
php artisan migrate --force

# Seed the database with demo data
php artisan db:seed --force
```

## Step 7: Set Permissions

```bash
# Set proper permissions
chmod -R 755 ~/public_html/api
chmod -R 775 ~/public_html/api/storage
chmod -R 775 ~/public_html/api/bootstrap/cache
```

## Step 8: Configure .htaccess

Make sure the `.htaccess` file in the root redirects to public:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## Step 9: Enable SSL

1. In hPanel, go to **Security** → **SSL**
2. Enable **Free SSL** for your subdomain
3. Update `.env` to use `https://`

## Step 10: Test the API

Test login endpoint:
```bash
curl -X POST https://api.yourdomain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"superadmin@cellulagam.com","password":"password"}'
```

## Troubleshooting

### 500 Internal Server Error
- Check storage permissions: `chmod -R 775 storage bootstrap/cache`
- Check logs: `cat storage/logs/laravel.log`
- Verify `.env` file exists and has correct values

### Database Connection Error
- Verify database credentials in `.env`
- Check if database exists in hPanel
- Try connecting via phpMyAdmin to test credentials

### 404 Not Found
- Check `.htaccess` is properly configured
- Verify `mod_rewrite` is enabled
- Clear route cache: `php artisan route:clear`

## API Endpoints

Base URL: `https://api.yourdomain.com/api/v1`

- Login: `POST /auth/login`
- Dashboard: `GET /dashboard`
- Products: `GET /products`
- Sales: `GET /sales`

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@cellulagam.com | password |
| Shop 1 Admin | admin1@cellulagam.com | password |
| Shop 2 Admin | admin2@cellulagam.com | password |
