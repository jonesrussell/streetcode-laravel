# Docker Production Setup

This directory contains the configuration files for deploying the Laravel application using Docker Compose with Nginx and Let's Encrypt SSL certificates.

## Architecture

The production setup consists of four main services:

1. **app** - Laravel application container with Supervisor
   - Runs PHP 8.4 FPM
   - Supervisor manages the `articles:subscribe` Redis subscription command
   - Automatically restarts on failure

2. **db** - MariaDB 10.11 database
   - Persistent data storage
   - Matches ddev development environment

3. **nginx** - Nginx reverse proxy
   - Handles SSL termination
   - Serves static files
   - Proxies PHP requests to app container

4. **certbot** - Let's Encrypt certificate management
   - Automatically renews SSL certificates
   - Runs certificate renewal every 12 hours

## Prerequisites

- Docker and Docker Compose installed
- Domain name pointing to your server
- External Redis service accessible
- Ports 80 and 443 open on your firewall

## Environment Variables

Copy `.env.example` to `.env` and configure the following variables:

### Required Variables

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_ROOT_PASSWORD=root_password

# Redis (External)
REDIS_HOST=your-redis-host.com
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password

# Let's Encrypt
LETSENCRYPT_EMAIL=your-email@example.com
LETSENCRYPT_DOMAIN=yourdomain.com
```

### Optional Variables

```bash
# Article Quality Filtering
ARTICLES_MIN_QUALITY_SCORE=70
```

## Initial Setup

### 1. Configure Environment

```bash
cp .env.example .env
# Edit .env with your production values
```

### 2. Update Certbot Script

Edit `docker/certbot/init-letsencrypt.sh` and update:
- `domains` array with your domain(s)
- `email` variable with your email address
- Set `staging=0` for production (use `staging=1` for testing)

### 3. Update Nginx Configuration

Edit `docker/nginx/default.conf` and replace `${LETSENCRYPT_DOMAIN}` with your actual domain name, or use environment variable substitution.

### 4. Build and Start Services

```bash
# Build the containers
docker compose build

# Start services (without SSL first)
docker compose up -d

# Wait for services to be ready
docker compose ps
```

### 5. Initialize Let's Encrypt Certificates

```bash
# Make script executable (if not already)
chmod +x docker/certbot/init-letsencrypt.sh

# Run the initialization script
./docker/certbot/init-letsencrypt.sh
```

This script will:
1. Create dummy certificates
2. Start nginx
3. Request real Let's Encrypt certificates
4. Reload nginx with real certificates

### 6. Run Database Migrations

```bash
docker compose exec app php artisan migrate --force
```

### 7. Optimize Application

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## Managing Services

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f certbot
```

### Check Supervisor Status

```bash
# Check if articles:subscribe is running
docker compose exec app supervisorctl status

# Restart articles:subscribe
docker compose exec app supervisorctl restart articles-subscribe

# View supervisor logs
docker compose exec app tail -f /var/www/html/storage/logs/supervisor/articles-subscribe.log
```

### Restart Services

```bash
# Restart all services
docker compose restart

# Restart specific service
docker compose restart app
```

### Stop Services

```bash
docker compose down
```

### Stop and Remove Volumes

```bash
# WARNING: This will delete your database data
docker compose down -v
```

## SSL Certificate Renewal

Certificates are automatically renewed by the certbot container every 12 hours. The nginx container automatically reloads when certificates are renewed.

To manually renew certificates:

```bash
docker compose exec certbot certbot renew
docker compose exec nginx nginx -s reload
```

## Troubleshooting

### Supervisor Not Running

Check if supervisor is running:

```bash
docker compose exec app ps aux | grep supervisord
```

If not running, start it manually:

```bash
docker compose exec app supervisord -c /var/www/html/config/supervisor/supervisord.conf -n
```

### Articles Not Being Processed

1. Check Redis connection:
   ```bash
   docker compose exec app php artisan tinker
   # Then: Redis::connection()->ping();
   ```

2. Check supervisor logs:
   ```bash
   docker compose exec app tail -f /var/www/html/storage/logs/supervisor/articles-subscribe.log
   ```

3. Check if the process is running:
   ```bash
   docker compose exec app supervisorctl status articles-subscribe
   ```

### SSL Certificate Issues

1. Check certbot logs:
   ```bash
   docker compose logs certbot
   ```

2. Verify domain DNS is pointing to your server

3. Ensure ports 80 and 443 are open

4. For testing, use staging mode in `init-letsencrypt.sh`:
   ```bash
   staging=1
   ```

### Database Connection Issues

1. Verify database is running:
   ```bash
   docker compose ps db
   ```

2. Check database logs:
   ```bash
   docker compose logs db
   ```

3. Test connection:
   ```bash
   docker compose exec app php artisan tinker
   # Then: DB::connection()->getPdo();
   ```

## Development with ddev

For local development, ddev is configured to automatically start supervisor when you run `ddev start`. The supervisor will manage the `articles:subscribe` command.

### ddev Commands

```bash
# Start ddev (supervisor starts automatically)
ddev start

# Check supervisor status
ddev exec supervisorctl status

# Restart articles:subscribe
ddev exec supervisorctl restart articles-subscribe

# View logs
ddev exec tail -f storage/logs/supervisor/articles-subscribe.log
```

## Production Deployment Checklist

- [ ] Environment variables configured in `.env`
- [ ] Domain DNS pointing to server
- [ ] Firewall ports 80 and 443 open
- [ ] External Redis accessible
- [ ] Certbot script configured with correct domain and email
- [ ] Nginx configuration updated with domain
- [ ] SSL certificates initialized
- [ ] Database migrations run
- [ ] Application optimized (config, route, view cache)
- [ ] Supervisor running and managing articles:subscribe
- [ ] Monitoring and logging configured

## Security Considerations

1. **Environment Variables**: Never commit `.env` file to version control
2. **Database Passwords**: Use strong, unique passwords
3. **Redis**: Use password authentication if possible
4. **SSL**: Always use HTTPS in production
5. **Firewall**: Only expose necessary ports (80, 443)
6. **Updates**: Regularly update Docker images and dependencies

## Backup Recommendations

1. **Database**: Set up regular database backups
   ```bash
   docker compose exec db mysqldump -u root -p database_name > backup.sql
   ```

2. **SSL Certificates**: Certificates are stored in Docker volumes, ensure volumes are backed up

3. **Application Files**: Use version control and regular deployments

## Scaling Considerations

For high-traffic scenarios:

1. **Horizontal Scaling**: Run multiple app containers behind a load balancer
2. **Database**: Consider managed database services for better performance
3. **Redis**: Use Redis Cluster or managed Redis service
4. **Queue Workers**: Add more Horizon workers if needed
5. **Nginx**: Consider using Nginx Plus or CDN for static assets

