# 🌐 Environment Variables Reference

Complete guide to configuring Rehome v1 for different environments.

## 📋 Quick Reference

| Category         | Development       | Production        |
| ---------------- | ----------------- | ----------------- |
| **Database**     | SQLite/PostgreSQL | Render PostgreSQL |
| **Cache/Queue**  | Redis             | Render Redis      |
| **Files**        | Local storage     | AWS S3            |
| **Email**        | Mailpit           | Resend            |
| **Broadcasting** | Laravel Reverb    | Pusher/Ably       |
| **AI**           | OpenAI/local keys | Production keys   |

## 🔧 Environment Files

### Development (.env)

```bash
APP_NAME=Rehome
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:your-local-key

# Database - Local PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=rehome
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis - Local
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Local storage
FILESYSTEM_DISK=local

# Email - Mailpit for testing
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Broadcasting - Laravel Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_HOST=localhost
REVERB_PORT=8080

# AI - Development keys
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=sk-your-dev-key
```

### Production (.env.production)

```bash
APP_NAME=Rehome
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com
APP_KEY=base64:your-production-key

# Database - Render PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host.render.com
DB_PORT=5432
DB_DATABASE=your_db_name
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Redis - Render Redis
REDIS_HOST=your-redis-host.render.com
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# S3 Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=AKIAEXAMPLE
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=rehome-prod-private

# Email - Resend
MAIL_MAILER=resend
RESEND_API_KEY=re_your_api_key
MAIL_FROM_ADDRESS=no-reply@yourdomain.com

# Broadcasting - Pusher
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=api-us-east-1.pusherapp.com

# AI - Production keys
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=sk-your-production-key
```

## 📖 Variable Descriptions

### Core Application

| Variable    | Description                    | Default          | Required |
| ----------- | ------------------------------ | ---------------- | -------- |
| `APP_NAME`  | Application name               | Laravel          | ✅       |
| `APP_ENV`   | Environment (local/production) | local            | ✅       |
| `APP_DEBUG` | Enable debug mode              | true             | ✅       |
| `APP_URL`   | Base application URL           | http://localhost | ✅       |
| `APP_KEY`   | Encryption key                 | -                | ✅       |

### Database

| Variable        | Description       | Default   | Required |
| --------------- | ----------------- | --------- | -------- |
| `DB_CONNECTION` | Database driver   | sqlite    | ✅       |
| `DB_HOST`       | Database host     | 127.0.0.1 | ✅       |
| `DB_PORT`       | Database port     | 3306      | ✅       |
| `DB_DATABASE`   | Database name     | -         | ✅       |
| `DB_USERNAME`   | Database user     | -         | ✅       |
| `DB_PASSWORD`   | Database password | -         | ✅       |

### Cache & Sessions

| Variable           | Description    | Default   | Required |
| ------------------ | -------------- | --------- | -------- |
| `CACHE_DRIVER`     | Cache driver   | file      | ✅       |
| `SESSION_DRIVER`   | Session driver | file      | ✅       |
| `QUEUE_CONNECTION` | Queue driver   | sync      | ✅       |
| `REDIS_HOST`       | Redis host     | 127.0.0.1 | ❌       |
| `REDIS_PORT`       | Redis port     | 6379      | ❌       |
| `REDIS_PASSWORD`   | Redis password | null      | ❌       |

### File Storage

| Variable                | Description    | Default   | Required |
| ----------------------- | -------------- | --------- | -------- |
| `FILESYSTEM_DISK`       | Storage driver | local     | ✅       |
| `AWS_ACCESS_KEY_ID`     | S3 access key  | -         | 🔐       |
| `AWS_SECRET_ACCESS_KEY` | S3 secret key  | -         | 🔐       |
| `AWS_DEFAULT_REGION`    | S3 region      | us-east-1 | 🔐       |
| `AWS_BUCKET`            | S3 bucket name | -         | 🔐       |

### Email

| Variable            | Description    | Default     | Required |
| ------------------- | -------------- | ----------- | -------- |
| `MAIL_MAILER`       | Mail driver    | smtp        | ✅       |
| `MAIL_HOST`         | SMTP host      | 127.0.0.1   | ✅       |
| `MAIL_PORT`         | SMTP port      | 2525        | ✅       |
| `RESEND_API_KEY`    | Resend API key | -           | 🔐       |
| `MAIL_FROM_ADDRESS` | From email     | -           | ✅       |
| `MAIL_FROM_NAME`    | From name      | ${APP_NAME} | ✅       |

### Broadcasting

| Variable               | Description       | Default           | Required |
| ---------------------- | ----------------- | ----------------- | -------- |
| `BROADCAST_CONNECTION` | Broadcast driver  | log               | ✅       |
| `PUSHER_APP_ID`        | Pusher app ID     | -                 | 🔐       |
| `PUSHER_APP_KEY`       | Pusher app key    | -                 | 🔐       |
| `PUSHER_APP_SECRET`    | Pusher app secret | -                 | 🔐       |
| `PUSHER_HOST`          | Pusher host       | api.pusherapp.com | 🔐       |
| `ABLY_KEY`             | Ably API key      | -                 | 🔐       |

### AI Configuration

| Variable            | Description                    | Default     | Required |
| ------------------- | ------------------------------ | ----------- | -------- |
| `AI_PROVIDER`       | AI provider (openai/anthropic) | openai      | ✅       |
| `AI_MODEL`          | AI model name                  | gpt-4o-mini | ✅       |
| `AI_MAX_TOKENS`     | Max tokens per request         | 4096        | ✅       |
| `AI_TEMPERATURE`    | Response randomness            | 0.7         | ✅       |
| `AI_TIMEOUT`        | Request timeout (seconds)      | 60          | ✅       |
| `OPENAI_API_KEY`    | OpenAI API key                 | -           | 🔐       |
| `ANTHROPIC_API_KEY` | Anthropic API key              | -           | 🔐       |

### Security

| Variable                   | Description      | Default   | Required |
| -------------------------- | ---------------- | --------- | -------- |
| `SANCTUM_STATEFUL_DOMAINS` | Sanctum domains  | localhost | ✅       |
| `CORS_ALLOWED_ORIGINS`     | CORS origins     | \*        | ✅       |
| `SESSION_SECURE_COOKIE`    | Secure cookies   | false     | ✅       |
| `SESSION_SAME_SITE`        | Same-site policy | lax       | ✅       |

## 🔐 Secrets Management

### Development

- Store in `.env` file (git-ignored)
- Use weak keys for local development
- Share non-sensitive defaults in `.env.example`

### Production

- Use Render environment variables
- Generate strong, unique keys
- Rotate keys regularly
- Never commit real keys to version control

## 🚀 Environment Setup Commands

### Generate Application Key

```bash
# Development
php artisan key:generate

# Production (show only, don't write to file)
php artisan key:generate --show
```

### Test Configuration

```bash
# Check all config values
php artisan config:show

# Test specific connections
php artisan tinker
# DB::connection()->getPdo()
# Cache::put('test', 'value')
# Queue::push(new TestJob)
```

### Validate Environment

```bash
# Custom validation script
./scripts/dev/check-env.sh

# Laravel config caching (production)
php artisan config:cache
```

## 📋 Environment Checklist

### Pre-deployment

- [ ] All required variables set
- [ ] Database connection tested
- [ ] Redis connection tested
- [ ] S3 bucket accessible
- [ ] Email sending works
- [ ] Broadcasting configured
- [ ] AI provider keys valid

### Post-deployment

- [ ] Application loads without errors
- [ ] Database migrations successful
- [ ] Queue jobs processing
- [ ] File uploads work
- [ ] Email notifications sent
- [ ] Real-time features working
- [ ] AI agent responses working

## 🐳 Docker Environment

Variables are automatically loaded from `.env` in Docker Compose:

```yaml
# docker-compose.yml
services:
  app:
    environment:
      - APP_ENV=${APP_ENV}
      - DB_HOST=postgres
      - REDIS_HOST=redis
```

Override for different environments:

```bash
# Use different env file
docker compose --env-file .env.staging up

# Override specific variables
APP_ENV=production docker compose up
```

Ready to configure for any environment! 🌟
