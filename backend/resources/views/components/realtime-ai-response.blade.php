{{-- 
  Example: Real-time AI Token Streaming
  Usage: Include this in a Blade view where you want to show live AI responses
--}}

<div 
    x-data="{
        tokens: [],
        isStreaming: false,
        streamComplete: false,
        fullResponse: ''
    }"
    x-init="
        Echo.channel('agent.thread.{{ $thread->id }}')
            .listen('.Agent.Token', (event) => {
                console.log('Received token:', event);
                
                if (event.done) {
                    // Stream completed
                    isStreaming = false;
                    streamComplete = true;
                    if (event.full_response) {
                        fullResponse = event.full_response;
                    }
                } else {
                    // New token received
                    isStreaming = true;
                    tokens.push(event.token);
                    
                    // Auto-scroll to bottom
                    $nextTick(() => {
                        $refs.responseContainer.scrollTop = $refs.responseContainer.scrollHeight;
                    });
                }
            });
    "
    class="bg-white rounded-lg shadow-sm border p-4"
>
    <div class="flex items-center mb-3">
        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="font-medium text-gray-900">AI Assistant</h3>
            <p class="text-sm text-gray-500" x-show="isStreaming">Generating response...</p>
            <p class="text-sm text-green-600" x-show="streamComplete">Response complete</p>
        </div>
        <div x-show="isStreaming" class="flex space-x-1">
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
        </div>
    </div>

    <div 
        x-ref="responseContainer"
        class="prose prose-sm max-w-none bg-gray-50 rounded-md p-3 max-h-96 overflow-y-auto"
    >
        <div x-show="tokens.length === 0 && !streamComplete" class="text-gray-400 italic">
            Waiting for response...
        </div>
        
        <div x-show="tokens.length > 0 || streamComplete" class="whitespace-pre-wrap">
            <template x-for="(token, index) in tokens" :key="index">
                <span x-text="token"></span>
            </template>
            <span x-show="isStreaming" class="animate-pulse bg-blue-200 px-1">|</span>
        </div>
        
        <div x-show="streamComplete && fullResponse" class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-xs text-gray-500 mb-2">Final response:</p>
            <div x-html="fullResponse" class="text-sm"></div>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
        <span x-text="`${tokens.length} tokens received`"></span>
        <span x-show="streamComplete">
            Stream ID: {{ $streamId ?? 'N/A' }}
        </span>
    </div>
</div>