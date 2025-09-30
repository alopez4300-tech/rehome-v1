{{--
  Production-Ready Real-time AI Token Streaming
  Features: rAF batching, scroll lock, copy-ready, sequence ordering
  Usage: Include this in a Blade view where you want to show live AI responses
--}}

<div
    x-data="{
        tokens: [],
        tokenBuffer: [],
        isStreaming: false,
        streamComplete: false,
        fullResponse: '',
        scrollLocked: false,
        expectedSequence: 1,
        receivedSequences: new Set(),
        pendingTokens: new Map(),

        // Production: Batched rendering with rAF
        flushBuffer() {
            if (this.tokenBuffer.length === 0) return;

            const wasAtBottom = this.isScrolledToBottom();

            // Process buffer
            this.tokens.push(...this.tokenBuffer);
            this.tokenBuffer = [];

            // Smart auto-scroll with lock detection
            if (!this.scrollLocked && wasAtBottom) {
                this.$nextTick(() => {
                    this.$refs.responseContainer.scrollTop = this.$refs.responseContainer.scrollHeight;
                });
            }
        },

        isScrolledToBottom() {
            const container = this.$refs.responseContainer;
            if (!container) return true;
            return container.scrollTop >= container.scrollHeight - container.clientHeight - 10;
        },

        processToken(event) {
            // Sequence ordering for production reliability
            if (event.sequence) {
                this.receivedSequences.add(event.sequence);

                // Out-of-order token - buffer it
                if (event.sequence !== this.expectedSequence) {
                    this.pendingTokens.set(event.sequence, event.token);
                    return;
                }

                // In-order token - add to buffer
                this.tokenBuffer.push(event.token);
                this.expectedSequence++;

                // Check for pending tokens that are now in sequence
                while (this.pendingTokens.has(this.expectedSequence)) {
                    this.tokenBuffer.push(this.pendingTokens.get(this.expectedSequence));
                    this.pendingTokens.delete(this.expectedSequence);
                    this.expectedSequence++;
                }
            } else {
                // Fallback for tokens without sequence
                this.tokenBuffer.push(event.token);
            }
        }
    }"
    x-init="
        // Production: rAF-based rendering loop
        let rafId;
        const renderLoop = () => {
            this.flushBuffer();
            rafId = requestAnimationFrame(renderLoop);
        };
        rafId = requestAnimationFrame(renderLoop);

        // Cleanup on component destroy
        this.$el.addEventListener('x-destroyed', () => {
            if (rafId) cancelAnimationFrame(rafId);
        });

        // Scroll lock detection
        this.$refs.responseContainer.addEventListener('scroll', () => {
            this.scrollLocked = !this.isScrolledToBottom();
        });

        // WebSocket connection with private channel authentication
        Echo.private('agent.thread.{{ $thread->id }}')
            .listen('.agent.thread.token', (event) => {
                console.log('📡 Token received:', event);

                if (event.done) {
                    // Stream completed - idempotent handling
                    if (!this.streamComplete) {
                        this.isStreaming = false;
                        this.streamComplete = true;
                        if (event.full_response) {
                            this.fullResponse = event.full_response;
                        }
                        // Final buffer flush
                        this.flushBuffer();
                    }
                } else if (event.token) {
                    // New token - start streaming if not already
                    if (!this.isStreaming) {
                        this.isStreaming = true;
                    }
                    this.processToken(event);
                }
            })
            .error((error) => {
                console.error('🚨 WebSocket error:', error);
            });
    "
    class="bg-white rounded-lg shadow-sm border p-4"
>
    <!-- Production UI Header -->
    <div class="flex items-center mb-3">
        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="font-medium text-gray-900">AI Assistant</h3>
            <p class="text-sm text-gray-500" x-show="isStreaming">
                <span x-text="`Streaming... (${tokens.length} tokens)`"></span>
                <span x-show="scrollLocked" class="text-orange-500 ml-1">📍 Scroll locked</span>
            </p>
            <p class="text-sm text-green-600" x-show="streamComplete">
                ✅ Response complete (<span x-text="tokens.length"></span> tokens)
            </p>
        </div>

        <!-- Production streaming indicator -->
        <div x-show="isStreaming" class="flex items-center space-x-2">
            <div class="flex space-x-1">
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <span class="text-xs text-gray-400">LIVE</span>
        </div>
    </div>

    <!-- Production response container with scroll lock -->
    <div
        x-ref="responseContainer"
        class="relative prose prose-sm max-w-none bg-gray-50 rounded-md p-3 max-h-96 overflow-y-auto border"
    >
        <!-- Scroll lock notice -->
        <div x-show="scrollLocked"
             class="absolute top-2 right-2 bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded z-10">
            📍 Auto-scroll paused
        </div>

        <!-- Empty state -->
        <div x-show="tokens.length === 0 && !streamComplete" class="text-gray-400 italic text-center py-8">
            🤖 Waiting for AI response...
        </div>

        <!-- Streaming content -->
        <div x-show="tokens.length > 0 || streamComplete" class="whitespace-pre-wrap font-mono text-sm leading-relaxed">
            <template x-for="(token, index) in tokens" :key="index">
                <span x-text="token" class="break-words"></span>
            </template>
            <span x-show="isStreaming" class="animate-pulse bg-blue-200 px-1 ml-1 rounded">▋</span>
        </div>

        <!-- Final response (if different from tokens) -->
        <div x-show="streamComplete && fullResponse && fullResponse !== tokens.join('')"
             class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-xs text-gray-500 mb-2 font-semibold">📄 Formatted Response:</p>
            <div x-html="fullResponse" class="text-sm prose prose-sm max-w-none"></div>
        </div>
    </div>

    <!-- Production controls & stats -->
    <div class="mt-3 flex items-center justify-between text-xs">
        <div class="flex items-center space-x-4 text-gray-500">
            <span x-text="`${tokens.length} tokens`"></span>
            <span x-show="pendingTokens.size > 0" class="text-orange-600">
                <span x-text="pendingTokens.size"></span> pending
            </span>
            <span x-show="tokenBuffer.length > 0" class="text-blue-600">
                <span x-text="tokenBuffer.length"></span> buffered
            </span>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Copy button -->
            <button x-show="streamComplete"
                    @click="navigator.clipboard.writeText(tokens.join(''))"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    title="Copy response">
                📋
            </button>

            <!-- Stream ID -->
            <span x-show="streamComplete" class="text-gray-400">
                ID: {{ $streamId ?? 'N/A' }}
            </span>
        </div>
    </div>
</div>
