# 🔧 Operations Runbook

Production operations guide for Rehome v1.

## 🚀 Deployment

### Initial Deployment (Render)

1. **Set up services in Render**:

   - Web Service (Laravel app)
   - Worker Service (Horizon queues)
   - PostgreSQL database
   - Redis instance

2. **Configure environment variables** (see `docs/ENV.md`)

3. **Deploy**:

   ```bash
   # Render auto-deploys from main branch
   git push origin main
   ```

4. **Post-deployment**:
   ```bash
   # Run via Render shell or deploy hook
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Updates & Maintenance

```bash
# Clear caches after deployment
make cache-clear

# Restart queue workers
make queue-restart

# Check application health
make health
```

## 📊 Monitoring

### Key Metrics to Watch

| Metric               | Normal Range     | Alert Threshold |
| -------------------- | ---------------- | --------------- |
| Response time        | < 200ms          | > 1000ms        |
| Queue wait time      | < 10s            | > 60s           |
| Database connections | < 80%            | > 90%           |
| Memory usage         | < 80%            | > 90%           |
| Disk usage           | < 80%            | > 90%           |
| AI API costs         | Budget dependent | 90% of budget   |

### Health Checks

```bash
# Container health
make ai-status

# Queue system
curl http://localhost:8000/horizon/api/stats

# Database
make db-shell
# \l to list databases
# \dt to list tables

# Application logs
make logs-app
make logs-nginx
make logs-horizon
```

## 🔄 Common Operations

### Restart Services

```bash
# Restart all containers
make restart

# Restart specific services
docker compose restart app
docker compose restart horizon
docker compose restart nginx
```

### Clear Caches

```bash
# All Laravel caches
make cache-clear

# Specific caches
make cache          # Optimize caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Management

```bash
# View queue status
make horizon        # Open dashboard
php artisan queue:work --once  # Process one job

# Failed jobs
make queue-failed   # List failed jobs
make queue-retry    # Retry all failed

# Restart workers
make queue-restart
```

### Database Operations

```bash
# Backup
make db-backup

# Restore
make db-restore FILE=backup_20231201_120000.sql

# Migrations
make migrate
php artisan migrate:status
php artisan migrate:rollback --step=1
```

## 🚨 Incident Response

### High Response Times

1. **Check application logs**:

   ```bash
   make logs-app | grep ERROR
   ```

2. **Check database performance**:

   ```bash
   make db-shell
   SELECT * FROM pg_stat_activity WHERE state = 'active';
   ```

3. **Clear caches**:

   ```bash
   make cache-clear
   ```

4. **Restart services**:
   ```bash
   make restart
   ```

### Queue Backlog

1. **Check Horizon dashboard**: `/horizon`

2. **Scale workers**:

   ```bash
   # Temporarily increase worker processes
   php artisan horizon:scale supervisor-1=5
   ```

3. **Priority jobs**:
   ```bash
   # Process high priority queue first
   php artisan queue:work --queue=high,default,agent
   ```

### Database Connection Issues

1. **Check PostgreSQL logs**:

   ```bash
   make logs-postgres
   ```

2. **Test connectivity**:

   ```bash
   make db-shell
   ```

3. **Check connection limits**:
   ```bash
   # In PostgreSQL
   SHOW max_connections;
   SELECT count(*) FROM pg_stat_activity;
   ```

### High AI Costs

1. **Check cost widget** in admin panel

2. **Review recent runs**:

   ```bash
   php artisan tinker
   AgentRun::where('created_at', '>', now()->subDay())->sum('cost_cents');
   ```

3. **Pause AI features** if needed:
   ```bash
   # Temporarily disable agent processing
   php artisan queue:pause agent
   ```

## 🔐 Security Operations

### Rotate Application Key

```bash
# Generate new key
php artisan key:generate --show

# Update in production environment
# Restart all services
make restart
```

### Update Dependencies

```bash
# Backend
composer update --no-dev
php artisan migrate

# Frontend
npm update --prefix backend
npm run build --prefix backend
```

### SSL Certificate Renewal

Render manages SSL automatically, but monitor expiration dates.

## 📈 Performance Optimization

### Database

```bash
# Analyze slow queries
# Enable slow query log in PostgreSQL
# Review with pg_stat_statements

# Optimize tables
php artisan optimize:clear
```

### Caching

```bash
# Enable all optimizations
make cache

# Monitor cache hit rates
redis-cli info stats | grep keyspace
```

### Queue Optimization

```bash
# Monitor job processing times
# Adjust timeouts in config/horizon.php
# Scale workers based on load
```

## 📋 Maintenance Schedule

### Daily

- [ ] Check error logs
- [ ] Monitor AI costs
- [ ] Verify backup completion

### Weekly

- [ ] Review performance metrics
- [ ] Update dependencies (dev environment first)
- [ ] Clean up old logs and backups

### Monthly

- [ ] Security patches
- [ ] Database maintenance
- [ ] Cost analysis and optimization
- [ ] Capacity planning review

## 🆘 Emergency Contacts

- **On-call Engineer**: [Contact info]
- **Database Admin**: [Contact info]
- **Infrastructure Team**: [Contact info]

## 📞 Escalation Paths

1. **Level 1**: Check logs, restart services
2. **Level 2**: Database issues, scaling needs
3. **Level 3**: Security incidents, data corruption

Keep calm and check the logs first! 🚀
