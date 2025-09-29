<div class="space-y-4">
    {{-- Risk Overview --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                ⚠️ Project Risk Analysis
            </h3>
            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                Last analyzed: Never
            </span>
        </div>

        {{-- Risk Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-red-800 dark:text-red-200">High Risk Items</dt>
                        <dd class="text-lg font-semibold text-red-900 dark:text-red-100">--</dd>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Blocked Tasks</dt>
                        <dd class="text-lg font-semibold text-yellow-900 dark:text-yellow-100">--</dd>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <dt class="text-sm font-medium text-orange-800 dark:text-orange-200">Overdue Items</dt>
                        <dd class="text-lg font-semibold text-orange-900 dark:text-orange-100">--</dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Risk Analysis Placeholder --}}
        <div class="prose prose-sm max-w-none dark:prose-invert">
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <p class="text-gray-600 dark:text-gray-400 italic mb-0">
                    Click "Analyze Current Risks" to get AI-powered insights into project risks, blockers, and recommended mitigation strategies.
                </p>
            </div>
        </div>
    </div>

    {{-- Current Blockers --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            🚧 Current Blockers
        </h4>
        <div class="space-y-3">
            {{-- Placeholder for current blockers --}}
            <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                Run risk analysis to identify current blockers and their impact on the project timeline.
            </div>
        </div>
    </div>

    {{-- Risk Mitigation Suggestions --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            💡 Mitigation Suggestions
        </h4>
        <div class="space-y-3">
            {{-- Placeholder for mitigation suggestions --}}
            <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                AI-powered mitigation strategies will appear here after running the risk analysis.
            </div>
        </div>
    </div>

    {{-- Risk Trends --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            📈 Risk Trends
        </h4>
        <div class="h-32 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-sm">Risk trend chart will appear here</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
            🎯 Quick Actions
        </h4>
        <div class="flex flex-wrap gap-2">
            <button class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-full hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors">
                🚨 View All Overdue
            </button>
            <button class="px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-900/40 transition-colors">
                🚧 Resolve Blockers
            </button>
            <button class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors">
                📊 Export Risk Report
            </button>
            <button class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-full hover:bg-green-200 dark:hover:bg-green-900/40 transition-colors">
                ✅ Schedule Review
            </button>
        </div>
    </div>
</div>
