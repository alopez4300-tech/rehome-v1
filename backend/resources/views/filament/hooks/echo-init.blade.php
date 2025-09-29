{{-- Echo JavaScript Integration for Filament --}}
<script>
    // Import and initialize Echo if not already available
    if (typeof window.Echo === 'undefined') {
        import('/js/echo.js').then(() => {
            console.log('✅ Laravel Echo initialized in Filament');
        }).catch((error) => {
            console.error('❌ Failed to load Echo:', error);
        });
    }

    // Initialize agent thread listening when Echo is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Echo to be available
        const checkEcho = setInterval(() => {
            if (typeof window.Echo !== 'undefined') {
                clearInterval(checkEcho);
                initializeAgentListeners();
            }
        }, 100);

        function initializeAgentListeners() {
            // Listen for agent messages on current thread if we're on an agent thread page
            const threadMatch = window.location.pathname.match(/\/agent-threads\/(\d+)/);
            if (threadMatch) {
                const threadId = threadMatch[1];
                console.log(`🔗 Subscribing to agent.thread.${threadId}`);

                window.Echo.channel(`agent.thread.${threadId}`)
                    .listen('AgentMessageCreated', (e) => {
                        console.log('📨 New agent message received:', e.message);
                        handleNewAgentMessage(e.message);
                    })
                    .error((error) => {
                        console.error('❌ WebSocket channel error:', error);
                    });
            }

            // Global agent activity listener for workspace updates
            const workspaceMatch = window.location.pathname.match(/\/workspaces\/(\d+)/);
            if (workspaceMatch) {
                const workspaceId = workspaceMatch[1];
                console.log(`🏢 Subscribing to workspace.${workspaceId}.agents`);

                window.Echo.channel(`workspace.${workspaceId}.agents`)
                    .listen('AgentRunStarted', (e) => {
                        console.log('🚀 Agent run started:', e.run);
                        showNotification('Agent processing started', 'info');
                    })
                    .listen('AgentRunCompleted', (e) => {
                        console.log('✅ Agent run completed:', e.run);
                        showNotification('Agent processing completed', 'success');
                    });
            }
        }

        function handleNewAgentMessage(message) {
            // If we're on the agent thread page, trigger a Livewire refresh
            if (window.Livewire) {
                // Emit to any components listening for new messages
                window.Livewire.emit('agent-message-received', message);

                // Or refresh specific components
                const threadComponents = document.querySelectorAll('[wire\\:id*="agent-thread"]');
                threadComponents.forEach(component => {
                    const componentId = component.getAttribute('wire:id');
                    if (componentId) {
                        window.Livewire.find(componentId)?.call('refresh');
                    }
                });
            }

            // Update UI elements directly if needed
            const messagesContainer = document.getElementById('agent-messages');
            if (messagesContainer) {
                // Add the new message to the UI
                appendMessageToUI(messagesContainer, message);
            }
        }

        function appendMessageToUI(container, message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'agent-message bg-gray-50 p-4 rounded-lg mb-4';
            messageElement.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium">🤖</span>
                        </div>
                    </div>
                    <div class="flex-grow">
                        <div class="text-sm text-gray-900 whitespace-pre-wrap">${message.content}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            ${message.metadata?.provider || 'AI'} • Just now
                        </div>
                    </div>
                </div>
            `;

            // Smooth scroll and highlight
            container.appendChild(messageElement);
            messageElement.scrollIntoView({ behavior: 'smooth' });
            messageElement.style.animation = 'fadeIn 0.5s ease-in-out';
        }

        function showNotification(message, type = 'info') {
            // Use Filament's notification system if available
            if (window.FilamentNotification) {
                const notification = new window.FilamentNotification()
                    .title(message)
                    .color(type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info')
                    .duration(3000);

                notification.send();
            } else {
                // Fallback to console
                console.log(`📢 ${message}`);
            }
        }
    });
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .agent-message {
        animation: fadeIn 0.5s ease-in-out;
    }
</style>
