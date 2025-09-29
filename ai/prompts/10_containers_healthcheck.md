# 🐳 Container Health Check

Run these commands to verify all containers are healthy:

## 1. Container Status

```bash
make status
# or
docker compose ps
```

**Expected**: All containers should show "Up" or "Healthy"

## 2. Service Logs

```bash
make logs
# or specific services:
make logs-app
make logs-nginx
make logs-horizon
```

**Look for**:

- ✅ `app`: PHP-FPM ready to handle connections
- ✅ `nginx`: No configuration errors
- ✅ `horizon`: "Horizon started successfully"
- ✅ `postgres`: "database system is ready to accept connections"
- ✅ `redis`: "Ready to accept connections tcp"

## 3. Common Issues & Fixes

### 🔧 Permission Errors

```bash
make perms
# or
sudo chown -R vscode:vscode /workspaces/rehome-v1
```

### 📦 Missing Dependencies

```bash
make setup
# or
docker compose exec app composer install
```

### 🌐 Nginx Configuration Error

If nginx shows "invalid value" error:

```bash
# Check nginx logs
make logs-nginx
# Restart nginx
docker compose restart nginx
```

### 💾 Database Connection Issues

```bash
# Check postgres logs
docker compose logs postgres
# Test connection
make db-shell
```

### 🚀 Horizon Not Starting

If horizon shows "Command 'horizon' is not defined":

```bash
# Install Horizon
docker compose exec app composer require laravel/horizon
docker compose exec app php artisan horizon:install
# Restart horizon
docker compose restart horizon
```

## 4. Quick Health Check

```bash
./scripts/dev/containers.sh
```

This script will:

- Show container status
- Display recent logs for all services
- Highlight any errors

## 5. Environment Verification

```bash
./scripts/dev/check-env.sh
```

This checks:

- Required environment variables
- Database connectivity
- Redis connectivity
- File permissions

## ⚡ Quick Recovery Commands

| Issue             | Command                |
| ----------------- | ---------------------- |
| Containers down   | `make up`              |
| Permission denied | `make perms`           |
| Cache issues      | `make cache-clear`     |
| Complete restart  | `make down && make up` |
| Database issues   | `make migrate`         |

## 🎯 Success Indicators

✅ **All containers running**
✅ **No error logs**
✅ **Admin panel accessible**: `http://localhost:8000/admin`
✅ **Database migrations applied**
✅ **Queue system operational**
