<?php

namespace App\Filament\Widgets\Admin;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use App\Models\Project;

class RecentProjectsTable extends Widget
{
    protected static ?string $heading = 'Recent Projects';
    protected int|string|array $columnSpan = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(Project::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('workspace.name')->label('Workspace'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label('Updated'),
            ])
            ->paginated(false);
    }
}
