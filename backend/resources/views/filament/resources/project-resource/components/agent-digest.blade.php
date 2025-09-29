<div class="space-y-4">
    {{-- Digest Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                @if ($type === 'daily')
                    📅 Daily Project Digest
                @else
                    📊 Weekly Workspace Digest
                @endif
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ now()->format('M j, Y') }}
            </span>
        </div>

        {{-- Digest Content Placeholder --}}
        <div class="prose prose-sm max-w-none dark:prose-invert">
            @if ($type === 'daily')
                <div class="text-gray-600 dark:text-gray-400">
                    <p class="mb-4">📊 <strong>Today's Summary</strong></p>
                    <ul class="list-disc list-inside space-y-1 mb-4">
                        <li>Tasks completed: <span class="text-green-600 dark:text-green-400">Loading...</span></li>
                        <li>New tasks created: <span class="text-blue-600 dark:text-blue-400">Loading...</span></li>
                        <li>Overdue items: <span class="text-red-600 dark:text-red-400">Loading...</span></li>
                        <li>Team activity: <span class="text-purple-600 dark:text-purple-400">Loading...</span></li>
                    </ul>

                    <p class="mb-2">🎯 <strong>Key Achievements</strong></p>
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded mb-4">
                        <em>Generate today's digest to see the latest achievements and progress...</em>
                    </div>

                    <p class="mb-2">⚠️ <strong>Action Items</strong></p>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded">
                        <em>Generate today's digest to identify action items that need attention...</em>
                    </div>
                </div>
            @else
                <div class="text-gray-600 dark:text-gray-400">
                    <p class="mb-4">📈 <strong>Weekly Overview</strong></p>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">--</div>
                            <div class="text-sm">Tasks Completed</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">--</div>
                            <div class="text-sm">On-time Delivery</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">--</div>
                            <div class="text-sm">Team Interactions</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">--</div>
                            <div class="text-sm">Budget Usage</div>
                        </div>
                    </div>

                    <p class="mb-2">🏆 <strong>Week's Highlights</strong></p>
                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded mb-4">
                        <em>Generate weekly digest to see this week's major accomplishments...</em>
                    </div>

                    <p class="mb-2">🎯 <strong>Next Week's Focus</strong></p>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
                        <em>Generate weekly digest to get AI recommendations for next week...</em>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Digests --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            📚 Recent {{ $type === 'daily' ? 'Daily' : 'Weekly' }} Digests
        </h4>
        <div class="space-y-2">
            {{-- Placeholder for recent digests --}}
            <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                No recent digests found. Generate your first {{ $type }} digest above.
            </div>
        </div>
    </div>

    {{-- Digest Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            ⚙️ Digest Settings
        </h4>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm text-gray-700 dark:text-gray-300">
                    Auto-generate {{ $type }} digest
                </label>
                <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600" checked disabled>
            </div>
            <div class="flex items-center justify-between">
                <label class="text-sm text-gray-700 dark:text-gray-300">
                    Email digest to team
                </label>
                <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600" disabled>
            </div>
            <div class="flex items-center justify-between">
                <label class="text-sm text-gray-700 dark:text-gray-300">
                    Include cost analysis
                </label>
                <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600" checked disabled>
            </div>
        </div>
    </div>
</div>
