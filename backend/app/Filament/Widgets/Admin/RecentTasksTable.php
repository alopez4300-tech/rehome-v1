<?php

namespace App\Filament\Widgets\Admin;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use App\Models\Task;

class RecentTasksTable extends Widget
{
    protected static ?string $heading = 'Recent Tasks';
    protected int|string|array $columnSpan = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(Task::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label('Updated'),
            ])
            ->paginated(false);
    }
}
