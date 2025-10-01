<?php

namespace App\Filament\Widgets\Admin;

use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;

class StatsOverview extends Widget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', (string) User::count()),
            Stat::make('Projects', (string) Project::count()),
            Stat::make('Tasks', (string) Task::count()),
        ];
    }

    protected function getColumns(): int
    {
        return 3; // three cards
    }
}
