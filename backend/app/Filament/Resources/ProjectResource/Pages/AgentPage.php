<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\AgentThread;
use App\Models\AgentMessage;
use App\Models\AgentRun;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AgentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.agent-page';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $title = 'AI Agent';

    public ?array $data = [];

    public Project $record;

    public string $activeTab = 'chat';

    public ?string $chatMessage = '';

    public array $threads = [];

    public ?int $selectedThreadId = null;

    public array $messages = [];

    public bool $isStreaming = false;

    public function mount(): void
    {
        $this->form->fill();
        $this->loadThreads();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Agent Tabs')
                    ->tabs([
                        Tab::make('Chat')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                View::make('filament.resources.project-resource.components.agent-chat')
                                    ->viewData([
                                        'threads' => $this->threads,
                                        'selectedThreadId' => $this->selectedThreadId,
                                        'messages' => $this->messages,
                                        'isStreaming' => $this->isStreaming,
                                    ]),
                                Textarea::make('chatMessage')
                                    ->label('Message')
                                    ->placeholder('Ask the AI agent about your project...')
                                    ->rows(3)
                                    ->suffixAction(
                                        Action::make('send')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->action('sendMessage')
                                            ->disabled(fn () => $this->isStreaming)
                                    ),
                            ]),
                        Tab::make('Daily Digest')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                View::make('filament.resources.project-resource.components.agent-digest')
                                    ->viewData([
                                        'type' => 'daily',
                                        'project' => $this->record,
                                    ]),
                                Action::make('generateDailyDigest')
                                    ->label('Generate Today\'s Digest')
                                    ->icon('heroicon-o-sparkles')
                                    ->action('generateDigest')
                                    ->extraAttributes(['data-digest-type' => 'daily']),
                            ]),
                        Tab::make('Weekly Digest')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                View::make('filament.resources.project-resource.components.agent-digest')
                                    ->viewData([
                                        'type' => 'weekly',
                                        'project' => $this->record,
                                    ]),
                                Action::make('generateWeeklyDigest')
                                    ->label('Generate This Week\'s Digest')
                                    ->icon('heroicon-o-sparkles')
                                    ->action('generateDigest')
                                    ->extraAttributes(['data-digest-type' => 'weekly']),
                            ]),
                        Tab::make('Risks & Blockers')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->schema([
                                View::make('filament.resources.project-resource.components.agent-risks')
                                    ->viewData([
                                        'project' => $this->record,
                                    ]),
                                Action::make('analyzeRisks')
                                    ->label('Analyze Current Risks')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->action('analyzeRisks'),
                            ]),
                    ])
                    ->activeTab($this->activeTab)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function loadThreads(): void
    {
        $this->threads = AgentThread::forAdmin(Auth::user())
            ->where('project_id', $this->record->id)
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest()
            ->get()
            ->toArray();

        if (!empty($this->threads) && !$this->selectedThreadId) {
            $this->selectedThreadId = $this->threads[0]['id'];
            $this->loadMessages();
        }
    }

    public function loadMessages(): void
    {
        if (!$this->selectedThreadId) {
            $this->messages = [];
            return;
        }

        $this->messages = AgentMessage::where('thread_id', $this->selectedThreadId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function selectThread(int $threadId): void
    {
        $this->selectedThreadId = $threadId;
        $this->loadMessages();
    }

    public function sendMessage(): void
    {
        if (empty($this->chatMessage)) {
            return;
        }

        $this->isStreaming = true;

        try {
            // Create or get thread
            if (!$this->selectedThreadId) {
                $thread = AgentThread::create([
                    'project_id' => $this->record->id,
                    'user_id' => Auth::id(),
                    'audience' => 'admin',
                    'title' => 'Admin Chat - ' . now()->format('M j, Y H:i'),
                ]);
                $this->selectedThreadId = $thread->id;
                $this->loadThreads();
            }

            // Create user message
            $userMessage = AgentMessage::create([
                'thread_id' => $this->selectedThreadId,
                'role' => 'user',
                'content' => $this->chatMessage,
            ]);

            // Clear the input
            $this->chatMessage = '';

            // Refresh messages
            $this->loadMessages();

            // TODO: Dispatch job to process the message and generate response
            // This should integrate with your AI service

            Notification::make()
                ->title('Message sent')
                ->body('Your message has been sent to the AI agent.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to send message: ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isStreaming = false;
        }
    }

    public function generateDigest(array $arguments = []): void
    {
        $type = $arguments['data-digest-type'] ?? 'daily';

        try {
            // TODO: Implement digest generation logic
            // This should create a new thread and generate the appropriate digest

            Notification::make()
                ->title('Digest Generation Started')
                ->body("Generating {$type} digest for {$this->record->name}...")
                ->info()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to generate digest: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function analyzeRisks(): void
    {
        try {
            // TODO: Implement risk analysis logic
            // This should analyze the project and identify potential risks and blockers

            Notification::make()
                ->title('Risk Analysis Started')
                ->body("Analyzing risks and blockers for {$this->record->name}...")
                ->info()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to analyze risks: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    #[On('agent-message-received')]
    public function onMessageReceived(array $data): void
    {
        if ($data['thread_id'] === $this->selectedThreadId) {
            $this->loadMessages();
        }
    }

    #[On('agent-streaming-complete')]
    public function onStreamingComplete(): void
    {
        $this->isStreaming = false;
        $this->loadMessages();
    }

    public static function getNavigationLabel(): string
    {
        return 'AI Agent';
    }
}
