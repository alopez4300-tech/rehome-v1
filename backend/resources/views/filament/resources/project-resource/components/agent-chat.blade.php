<div class="space-y-4">
    {{-- Thread Selector --}}
    @if (!empty($threads))
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Chat History
            </label>
            <div class="flex flex-wrap gap-2">
                @foreach ($threads as $thread)
                    <button
                        wire:click="selectThread({{ $thread['id'] }})"
                        class="px-3 py-2 text-sm rounded-lg border transition-colors
                               {{ $selectedThreadId === $thread['id']
                                  ? 'bg-primary-500 text-white border-primary-500'
                                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                    >
                        {{ $thread['title'] ?? 'Thread ' . $thread['id'] }}
                        <span class="text-xs opacity-75 ml-1">
                            ({{ count($thread['messages'] ?? []) }} msgs)
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Chat Messages --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 min-h-[400px] max-h-[600px] overflow-y-auto">
        @if (empty($messages))
            <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 21l1.98-5.874A8.955 8.955 0 013 12a8 8 0 018-8c4.418 0 8 3.582 8 8z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No messages yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start a conversation with the AI agent</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($messages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] {{ $message['role'] === 'user' ? 'order-2' : '' }}">
                            <div class="flex items-center gap-2 mb-1">
                                @if ($message['role'] === 'user')
                                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">You</span>
                                @else
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400">AI Agent</span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('M j, H:i') }}
                                </span>
                            </div>
                            <div class="p-3 rounded-lg {{ $message['role'] === 'user'
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                                <div class="prose prose-sm max-w-none {{ $message['role'] === 'user' ? 'prose-invert' : 'dark:prose-invert' }}">
                                    {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Streaming indicator --}}
        @if ($isStreaming)
            <div class="flex justify-start mt-4">
                <div class="max-w-[80%]">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">AI Agent</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">typing...</span>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-100 dark:bg-gray-700">
                        <div class="flex items-center space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-2 mt-4">
        <button
            wire:click="$set('chatMessage', 'What tasks are overdue and who are they assigned to?')"
            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
            🚨 Overdue by assignee
        </button>
        <button
            wire:click="$set('chatMessage', 'Show me all blocked tasks and their blockers')"
            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
            🚧 Blocked tasks
        </button>
        <button
            wire:click="$set('chatMessage', 'Summarize the highlights from last week')"
            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
            ⭐ Last week highlights
        </button>
        <button
            wire:click="$set('chatMessage', 'What are the current project risks?')"
            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
            ⚠️ Project risks
        </button>
    </div>
</div>
