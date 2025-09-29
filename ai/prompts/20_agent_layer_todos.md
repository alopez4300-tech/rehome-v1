# 🤖 Agent Layer Implementation TODOs

The foundation is complete. Here are the key areas ready for AI provider integration:

## ✅ Already Implemented

### Models & Database

- ✅ `AgentThread` model with workspace/project scoping
- ✅ `AgentMessage` model with role-based content
- ✅ `AgentRun` model with cost tracking
- ✅ All migrations applied with proper indexes

### Filament Admin Interface

- ✅ Project → AI Agent page with 4 tabs:
  - 📱 **Chat**: Real-time conversation interface
  - 📅 **Daily Digest**: Project summaries
  - 📊 **Weekly Digest**: Workspace rollups
  - ⚠️ **Risks & Blockers**: Risk analysis dashboard
- ✅ Cost tracking widget (last 30 days spend)
- ✅ Quick prompt buttons for common queries

### API Layer

- ✅ `/api/projects/{project}/agent/threads` - Create/list threads
- ✅ `/api/agent/threads/{thread}/messages` - Send/receive messages
- ✅ `/api/agent/threads/{thread}/stream` - Server-sent events endpoint
- ✅ Authorization logic (workspace/project scoping)

### Queue System

- ✅ Laravel Horizon installed and configured
- ✅ Agent-specific queue supervisor with higher timeout
- ✅ Ready for job tagging: `['agent', 'workspace:{id}', 'project:{id}']`

### Broadcasting

- ✅ Laravel Reverb (WebSockets) installed
- ✅ Echo configuration ready for real-time streaming

## 🔨 Ready to Implement

### 1. AI Provider Integration

**Location**: `backend/app/Services/AI/`

Create service classes:

```php
// AIProviderInterface
interface AIProviderInterface {
    public function chat(array $messages, array $options = []): AIResponse;
    public function stream(array $messages, callable $callback): void;
}

// OpenAIProvider, AnthropicProvider implementations
```

**Environment variables needed**:

```bash
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=your_key_here
```

### 2. Agent Job Classes

**Location**: `backend/app/Jobs/Agent/`

```php
ProcessAgentMessage::class    # Handle user messages
GenerateDigest::class         # Daily/weekly summaries
AnalyzeRisks::class          # Risk assessment
StreamAgentResponse::class    # Real-time streaming
```

Each job should:

- Tag with workspace/project IDs
- Track tokens and cost in `AgentRun`
- Broadcast progress via WebSockets
- Handle cancellation gracefully

### 3. Context Building Service

**Location**: `backend/app/Services/Agent/ContextBuilder.php`

Implement the policy from README:

- **50%** budget for messages
- **30%** budget for tasks/activities
- **20%** budget for files/metadata
- Drop whole items (oldest first) when over budget
- 10% safety buffer for system prompts

### 4. Real-time Streaming

**Location**: `backend/app/Events/Agent/`

```php
AgentMessageCreated::class    # New message event
AgentStreamToken::class       # Token-by-token streaming
AgentStreamComplete::class    # Stream finished
```

Wire these to the SSE endpoint and Livewire components.

### 5. Digest Generation Logic

**Location**: `backend/app/Services/Agent/DigestGenerator.php`

Create templates for:

- Daily project summaries (participant-visible)
- Weekly workspace rollups (admin-only)
- Risk and blocker identification
- Cost analysis integration

## 📋 Implementation Priority

### Phase 1: Basic Chat (1-2 hours)

1. Create `OpenAIProvider` service
2. Build `ProcessAgentMessage` job
3. Wire up basic request/response in Filament
4. Test with simple prompts

### Phase 2: Streaming (2-3 hours)

1. Implement WebSocket events
2. Add streaming to `ProcessAgentMessage`
3. Update Filament frontend for real-time display
4. Add cancellation support

### Phase 3: Context & Intelligence (3-4 hours)

1. Build `ContextBuilder` with budget management
2. Add project data extraction (tasks, messages, files)
3. Implement cost tracking and budgets
4. Create digest generation workflows

### Phase 4: Automation (2-3 hours)

1. Schedule daily/weekly digest generation
2. Add risk analysis automation
3. Implement email notifications
4. Polish admin dashboard

## 🎯 Quick Wins to Start

1. **Test API endpoints**: Use Postman/curl to verify auth and basic functionality
2. **Create simple AI job**: Start with a basic OpenAI integration job
3. **Wire up one tab**: Get the Chat tab working end-to-end first
4. **Add cost tracking**: Implement token counting and cost calculation

## 📁 File Structure Ready

```
backend/
├── app/
│   ├── Services/AI/           # ← Create AI provider services
│   ├── Jobs/Agent/            # ← Create agent job classes
│   ├── Events/Agent/          # ← Create broadcast events
│   └── Http/Controllers/Api/  # ✅ Controllers exist
├── resources/views/filament/  # ✅ Agent UI exists
└── routes/api.php             # ✅ Routes defined
```

The foundation is solid. Ready to add intelligence! 🚀
