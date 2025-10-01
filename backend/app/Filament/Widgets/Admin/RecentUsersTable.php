<?php

namespace App\Filament\Widgets\Admin;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use App\Models\User;

class RecentUsersTable extends Widget
{
    protected static ?string $heading = 'Recent Users';
    protected int|string|array $columnSpan = 12;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Joined'),
            ])
            ->paginated(false);
    }
}
