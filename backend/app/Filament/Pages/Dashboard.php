<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFilters;
use App\Filament\Widgets\Admin\StatsOverview;
use App\Filament\Widgets\Admin\RecentProjectsTable;
use App\Filament\Widgets\Admin\RecentUsersTable;
use App\Filament\Widgets\Admin\RecentTasksTable;

class Dashboard extends BaseDashboard
{
    use HasFilters;

    public static function getNavigationLabel(): string 
    { 
        return 'Dashboard'; 
    }

    public static function getNavigationGroup(): ?string 
    { 
        return null; 
    }

    public static function getNavigationIcon(): ?string 
    { 
        return 'heroicon-o-home'; 
    }

    public function getColumns(): int|string|array
    {
        return 12; // grid
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,                 // row: counts
            RecentProjectsTable::class,           // row: recents
            RecentTasksTable::class,
            RecentUsersTable::class,
        ];
    }
}
