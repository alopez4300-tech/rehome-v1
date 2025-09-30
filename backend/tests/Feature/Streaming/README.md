# 🧪 Streaming System Tests

## Current Test Architecture

### **Production-Ready Tests** ✅

#### `BroadcastContractTest.php`
**The authoritative test suite for the ReHome v1 streaming system.**

- ✅ **4 comprehensive tests** covering all critical contracts
- ✅ **Modern architecture**: Tests `ThreadTokenStreamed` with private channels
- ✅ **Production patterns**: Validates sequence integrity, idempotent operations
- ✅ **Real schema**: Uses correct workspace → project → thread relationships
- ✅ **PHPUnit 12 ready**: Uses `#[Test]` attributes instead of docblock annotations

**Test Coverage:**
1. `broadcast_contract_private_channel_and_seq()` - Event structure & sequencing
2. `private_channel_uses_correct_naming_convention()` - Channel security
3. `event_structure_contains_required_fields()` - Payload contracts  
4. `streaming_service_maintains_sequence_integrity()` - Production reliability

### **Retired Legacy Tests** ❌

The following test files have been **retired** as they tested outdated patterns:

- ~~`AgentBroadcastingTest.php`~~ - Tested legacy `AgentMessageCreated` pattern with wrong schema
- ~~`StreamingBroadcastTest.php`~~ - Had wrong method signatures and import paths

**Why retired vs updated:**
- Legacy tests used `workspaces.user_id` (doesn't exist in current schema)
- Tested old message-based approach vs. current token streaming
- Had fundamental architectural mismatches that would require complete rewrites
- New tests provide superior coverage of actual production patterns

### **Running Tests**

```bash
# Run the authoritative streaming tests
php artisan test tests/Feature/Streaming/BroadcastContractTest.php

# Expected output: ✓ 4 passed (17 assertions)
```

### **Adding New Tests**

When adding streaming-related tests:
- ✅ Add to `BroadcastContractTest.php` for core contracts
- ✅ Use `ThreadTokenStreamed` event with correct namespace
- ✅ Test private channels with `private-agent.thread.{id}` naming
- ✅ Use `Event::fake()` instead of `Broadcast::fake()` for event testing
- ✅ Test against real model relationships (workspace → project → thread)

### **Test Patterns**

```php
// ✅ Correct test pattern
Event::fake([ThreadTokenStreamed::class]);
$event = new ThreadTokenStreamed($thread->id, [...]);
Event::assertDispatched(ThreadTokenStreamed::class, function ($event) use ($thread) {
    return $event->broadcastAs() === 'agent.thread.token';
});

// ❌ Avoid legacy patterns  
Broadcast::fake(); // Doesn't work with current driver setup
$event = new AgentMessageCreated(...); // Legacy event
```

---

## 🎯 **Bottom Line**

The `BroadcastContractTest.php` is the **single source of truth** for streaming system validation. It comprehensively tests the production system with modern Laravel patterns and provides reliable guardrails for the ReHome v1 AI streaming functionality.