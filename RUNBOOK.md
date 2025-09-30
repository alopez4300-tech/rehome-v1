# ReHome v1 – Streaming Runbook (Ops)

## 🔧 Processes

### Start Reverb (WebSockets)
- **Direct**: `php artisan reverb:start --host=0.0.0.0 --port=8080`
- **Supervisor**: `sudo supervisorctl start rehome-reverb`
- **Systemd**: `sudo systemctl start rehome-reverb`

### Start Workers (Queues)
- **Direct**: `php artisan queue:work --queue=high,default`
- **Horizon**: `php artisan horizon` or `sudo supervisorctl start rehome-horizon`
- **Supervisor**: `sudo supervisorctl start rehome-workers`

## ✅ Health Checks (Daily)

### System Health
- **Redis**: `redis-cli ping` → `PONG`
- **Reverb**: `ps aux | grep "artisan reverb"` or `curl http://localhost:8080/health`
- **App Log**: `tail -n 200 storage/logs/laravel.log | grep -E "StreamingService|ThreadToken" -n`

### Quick Status
```bash
# All-in-one health check
redis-cli ping && echo "✅ Redis OK" || echo "❌ Redis DOWN"
pgrep -f "artisan reverb" && echo "✅ Reverb OK" || echo "❌ Reverb DOWN"  
curl -s http://localhost:8080/health | grep -q "ok" && echo "✅ WebSocket OK" || echo "❌ WebSocket DOWN"
```

## 🧪 Smoke Test (End-to-end)

### Step 1: Identify Thread/Run
```bash
# Use existing or create test data
php artisan tinker --execute="
\$thread = \App\Models\AgentThread::first() ?? \App\Models\AgentThread::factory()->create();
\$run = \App\Models\AgentRun::factory()->create(['thread_id' => \$thread->id]);
echo 'THREAD=' . \$thread->id . PHP_EOL;
echo 'RUN=' . \$run->id . PHP_EOL;
"
```

### Step 2: Emit Tokens
```bash
# Replace {thread_id} and {run_id} with values from Step 1
php artisan stream:smoke {thread_id} {run_id} --stream_id=ops-smoke

# Expected: Progress bar + "🎉 Smoke stream completed successfully!"
```

### Step 3: Browser Verification
```javascript
// Open browser dev tools on any page with Echo loaded, execute:
Echo.private('agent.thread.{thread_id}')
    .listen('.agent.thread.token', e => console.log('📡 Token:', e));

// Re-run Step 2 and confirm tokens appear in real-time
```

## 🔧 Quick Fixes

### No Tokens in Browser
1. **Check Private Channel Auth**: Network tab → `/broadcasting/auth` should return 200
2. **Verify Environment**: `php artisan config:show broadcasting | grep reverb`
3. **Rebuild Assets**: `npm run build` or restart Vite dev server after env changes

### Reverb Won't Start / Port in Use
1. **Check Port**: `lsof -i :8080` then kill stale processes
2. **Firewall**: Ensure port 8080 is open internally (not public)
3. **Restart**: `sudo supervisorctl restart rehome-reverb`

### Redis Errors
1. **Memory Check**: `redis-cli info memory` (look for memory pressure)
2. **Connection Test**: `redis-cli monitor` (watch live commands)
3. **Restart Redis**: `sudo systemctl restart redis` then verify `CACHE_STORE=redis`

## 📊 Metrics to Watch

### Redis Performance
```bash
# Hit ratio (should be >95%)
redis-cli info stats | grep -E "keyspace_hits:|keyspace_misses:"

# Memory usage
redis-cli info memory | grep "used_memory_human"

# Operations per second
redis-cli info stats | grep "instantaneous_ops_per_sec"
```

### Streaming Health
```bash
# Active sequences (should trend to zero)
redis-cli --scan --pattern "ai:seq:*" | wc -l

# Completed streams (cleaned up over time)
redis-cli --scan --pattern "ai:done:*" | wc -l

# Check for stuck sequences
redis-cli --scan --pattern "ai:seq:*" | head -5
```

### Application Metrics
- **Token Latency**: p95 <50ms from `streamToken()` to client
- **Error Rate**: <10 errors/5min containing `StreamingService` or `ThreadToken`
- **Memory Usage**: <50MB per worker process

## 🚨 Alerts (Suggested Monitoring)

### Critical Alerts
```bash
# Reverb process down
! pgrep -f "artisan reverb" && echo "CRITICAL: Reverb server offline"

# Redis unavailable  
! redis-cli ping &>/dev/null && echo "CRITICAL: Redis server down"

# High error rate
tail -100 storage/logs/laravel.log | grep -c ERROR | awk '$1 > 10 {print "ALERT: High error rate"}'
```

### Warning Alerts  
```bash
# Memory usage high (>1GB Redis)
redis-cli info memory | grep used_memory | awk -F: '$2 > 1000000000 {print "WARNING: Redis memory >1GB"}'

# Many pending sequences (possible leak)
redis-cli --scan --pattern "ai:seq:*" | wc -l | awk '$1 > 100 {print "WARNING: Many pending sequences"}'
```

## 🔐 Security

### Channel Security
- All streaming uses **private channels**: `PrivateChannel('agent.thread.{id}')`
- Rate limiting: 100 events/minute per thread (adjust as needed)  
- WSS via load balancer/proxy; Reverb stays HTTP internally

### Secrets Management
- Use **unique** `REVERB_APP_KEY/SECRET` (not equal to `APP_KEY`)
- Rotate quarterly; deploy rolls clients automatically (Echo picks up from Vite env)
- Store secrets in secure vault (AWS Secrets Manager, etc.)

## 🛠️ Useful Commands

### Configuration
```bash
# Clear all cached config
php artisan optimize:clear

# Inspect broadcast configuration  
php artisan config:show broadcasting

# View current environment
php artisan env

# Test cache connectivity
php artisan tinker --execute="cache()->put('test', 'ok', 60); echo cache()->get('test');"
```

### Debugging
```bash
# Tail application logs
tail -f storage/logs/laravel.log

# Monitor Redis commands live
redis-cli monitor

# Check WebSocket connectivity
curl -i -H "Connection: Upgrade" -H "Upgrade: websocket" http://localhost:8080/app/your-app-key

# Test broadcasting manually
php artisan tinker --execute="broadcast(new \App\Events\Agent\ThreadTokenStreamed(123, ['token' => 'test']));"
```

### Maintenance
```bash
# Restart all streaming services
sudo supervisorctl restart rehome-reverb rehome-workers

# Clean up old stream data (if needed)
redis-cli --scan --pattern "ai:done:*" | head -100 | xargs redis-cli del

# Check disk space for logs
du -sh storage/logs/
```

## 📞 Emergency Contacts

- **Tech Lead**: [Your contact info]
- **DevOps**: [Your contact info]  
- **On-Call**: [Your contact info]
- **Escalation**: [Manager contact info]

---

## 🎯 **Quick Reference Card**

| **Command** | **Purpose** |
|-------------|-------------|
| `redis-cli ping` | Check Redis connectivity |
| `php artisan stream:smoke {thread} {run}` | End-to-end smoke test |
| `ps aux \| grep reverb` | Check Reverb process |
| `curl localhost:8080/health` | WebSocket health check |
| `tail -f storage/logs/laravel.log` | Monitor application logs |
| `supervisorctl status` | Check all service status |

**🚨 Emergency**: If all else fails, restart everything:
```bash
sudo supervisorctl restart all
redis-cli flushall  # ⚠️  ONLY in dev/staging!
php artisan optimize:clear
```