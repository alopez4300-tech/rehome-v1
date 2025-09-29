# 🌐 Production Environment Setup

Configure production-ready services: Render + S3 + Resend + WebSockets

## 🔧 Required Environment Variables

### Core Laravel

```bash
APP_NAME=Rehome
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com
APP_KEY=base64:your-32-character-key

# Generate with: php artisan key:generate --show
```

### Database (Render Managed PostgreSQL)

```bash
DB_CONNECTION=pgsql
DB_HOST=your-postgres.render.com
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
```

### Redis (Render Managed Redis)

```bash
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_CONNECTION=pusher

REDIS_HOST=your-redis.render.com
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_URL=redis://default:password@host:port
```

### File Storage (AWS S3)

```bash
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=rehome-production-private
AWS_URL=https://rehome-production-private.s3.us-east-1.amazonaws.com

# S3 Direct Access (No CloudFront)
# Use signed URLs for private assets
```

### Email (Resend)

```bash
MAIL_MAILER=resend
RESEND_API_KEY=re_123456789_your_api_key_here

MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="Rehome"
```

### Broadcasting (Pusher or Ably)

#### Option A: Pusher

```bash
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=1234567
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=api-us-east-1.pusherapp.com
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=us-east-1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### Option B: Ably

```bash
BROADCAST_CONNECTION=ably

ABLY_KEY=your_ably_api_key

VITE_ABLY_KEY="${ABLY_KEY}"
```

#### Option C: Self-hosted WebSockets (Render service)

```bash
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=your-websockets.onrender.com
PUSHER_PORT=443
PUSHER_SCHEME=https

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

### AI Configuration

```bash
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
AI_MAX_TOKENS=4096
AI_TEMPERATURE=0.7
AI_TIMEOUT=60
AI_TOKEN_SAFETY=0.10

# OpenAI
OPENAI_API_KEY=sk-your_openai_api_key_here

# OR Anthropic
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-your_anthropic_key_here
```

### Security & CORS

```bash
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,app.yourdomain.com
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com

# Session security
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

### Optional: Monitoring & Logging

```bash
# Sentry
SENTRY_LARAVEL_DSN=https://your_sentry_dsn_here

# Log level
LOG_LEVEL=warning
LOG_CHANNEL=stack
```

## 🏗️ Render Service Configuration

### Web Service (Laravel)

```yaml
# render.yaml
services:
  - type: web
    name: rehome-web
    runtime: php
    plan: starter # or pro
    buildCommand: |
      composer install --no-dev --optimize-autoloader
      npm install --prefix backend
      npm run build --prefix backend
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
      php artisan event:cache
    startCommand: |
      php artisan migrate --force
      php-fpm
    envVars:
      - key: APP_ENV
        value: production
      # ... other env vars
```

### Background Worker (Horizon)

```yaml
- type: worker
  name: rehome-worker
  runtime: php
  plan: starter
  buildCommand: composer install --no-dev --optimize-autoloader
  startCommand: php artisan horizon
  envVars:
    # Same as web service
```

### Optional: WebSockets Service

```yaml
- type: web
  name: rehome-websockets
  runtime: php
  plan: starter
  buildCommand: composer install --no-dev --optimize-autoloader
  startCommand: php artisan websockets:serve --host=0.0.0.0 --port=8080
  envVars:
    # Minimal env vars for websockets
```

## 📦 S3 Bucket Setup

### Bucket Configuration

1. **Private Bucket**: `rehome-production-private`

   - Block all public access
   - Versioning enabled
   - Server-side encryption

2. **Public Bucket** (if needed): `rehome-production-public`
   - Static website hosting
   - Public read access for SPA assets

### IAM Policy

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
      "Resource": "arn:aws:s3:::rehome-production-private/*"
    },
    {
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": "arn:aws:s3:::rehome-production-private"
    }
  ]
}
```

## ✅ Deployment Checklist

### Pre-deployment

- [ ] Generate secure `APP_KEY`
- [ ] Set up Render PostgreSQL database
- [ ] Set up Render Redis instance
- [ ] Create S3 buckets with proper policies
- [ ] Get Resend API key and verify domain
- [ ] Choose broadcasting provider (Pusher/Ably/self-hosted)
- [ ] Get AI provider API keys

### Post-deployment

- [ ] Run migrations: `php artisan migrate --force`
- [ ] Test file uploads to S3
- [ ] Verify email sending via Resend
- [ ] Test WebSocket connections
- [ ] Monitor Horizon dashboard
- [ ] Set up backup schedules
- [ ] Configure domain and SSL

### Monitoring

- [ ] Set up Sentry error tracking
- [ ] Monitor Render service logs
- [ ] Track AI usage and costs
- [ ] Monitor S3 usage and costs
- [ ] Set up uptime monitoring

## 🚨 Security Notes

1. **Environment Variables**: Never commit real keys to version control
2. **Database**: Use strong passwords and connection encryption
3. **S3**: Use IAM roles with minimal permissions
4. **HTTPS**: Enforce HTTPS in production
5. **CORS**: Restrict to your domains only
6. **Sessions**: Use secure cookies and proper same-site settings

Ready for production deployment! 🚀
