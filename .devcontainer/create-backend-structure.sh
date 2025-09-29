#!/bin/bash
set -e

echo "🏗️ Creating Laravel backend structure..."

cd /workspaces/rehome-v1/backend

# Create Models
echo "Creating models..."

# Workspace Model
cat > app/Models/Workspace.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    public function admins(): HasMany
    {
        return $this->hasMany(User::class)
                    ->whereHas('roles', fn($q) => $q->where('name', 'admin'));
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
EOF

# Project Model
cat > app/Models/Project.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }
}
EOF

# Update User Model
cat > app/Models/User.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'workspace_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
EOF

# Create migrations
echo "Creating migrations..."

# Workspaces migration
cat > database/migrations/2024_01_01_000001_create_workspaces_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
EOF

# Add workspace_id to users
cat > database/migrations/2024_01_01_000002_add_workspace_id_to_users_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                  ->nullable()
                  ->after('email_verified_at')
                  ->constrained('workspaces')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
EOF

# Projects table
cat > database/migrations/2024_01_01_000003_create_projects_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['in_progress', 'on_hold', 'completed', 'archived'])->default('in_progress');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
EOF

# Project-User pivot table
cat > database/migrations/2024_01_01_000004_create_project_user_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable(); // 'team', 'consultant', 'client'
            $table->timestamps();
            
            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
EOF

# Create Filament directories
mkdir -p app/Filament/Resources
mkdir -p app/Filament/Resources/UserResource/Pages
mkdir -p app/Filament/Resources/ProjectResource/Pages
mkdir -p app/Filament/Resources/ProjectResource/RelationManagers
mkdir -p app/Filament/Pages
mkdir -p app/Policies

# Install Filament
php artisan filament:install --panels --no-interaction

# Create Filament resources
echo "Creating Filament resources..."

# UserResource
cat > app/Filament/Resources/UserResource.php << 'EOF'
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Workspace;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                    
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->live(),
                    
                Forms\Components\Select::make('workspace_id')
                    ->relationship('workspace', 'name')
                    ->visible(fn (Get $get) => collect($get('roles'))->contains(function ($roleId) {
                        return Role::find($roleId)?->name === 'admin';
                    }))
                    ->required(fn (Get $get) => collect($get('roles'))->contains(function ($roleId) {
                        return Role::find($roleId)?->name === 'admin';
                    }))
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Workspace::create($data)->id;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'team' => 'success',
                        'consultant' => 'warning',
                        'client' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('workspace.name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No workspace'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('workspace')
                    ->relationship('workspace', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
EOF

# UserResource Pages
cat > app/Filament/Resources/UserResource/Pages/ListUsers.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
EOF

cat > app/Filament/Resources/UserResource/Pages/CreateUser.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
EOF

cat > app/Filament/Resources/UserResource/Pages/EditUser.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
EOF

# ProjectResource
cat > app/Filament/Resources/ProjectResource.php << 'EOF'
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('workspace_id')
                    ->default(fn () => auth()->user()->workspace_id),
                    
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'on_hold' => 'On Hold',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('in_progress'),
                    
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                    
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('end_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'success',
                        'on_hold' => 'warning',
                        'completed' => 'info',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'on_hold' => 'On Hold',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('workspace_id', auth()->user()->workspace_id)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
EOF

# ProjectResource Pages
cat > app/Filament/Resources/ProjectResource/Pages/ListProjects.php << 'EOF'
<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Projects'),
            
            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),
            
            'on_hold' => Tab::make('On Hold')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'on_hold')),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            
            'archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'archived')),
            
            'trash' => Tab::make('Trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
EOF

cat > app/Filament/Resources/ProjectResource/Pages/CreateProject.php << 'EOF'
<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;
}
EOF

cat > app/Filament/Resources/ProjectResource/Pages/EditProject.php << 'EOF'
<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
EOF

# UsersRelationManager
cat > app/Filament/Resources/ProjectResource/RelationManagers/UsersRelationManager.php << 'EOF'
<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Assigned Users';
    protected static ?string $icon = 'heroicon-o-user-group';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->options([
                        'team' => 'Team Member',
                        'consultant' => 'Consultant',
                        'client' => 'Client',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'team' => 'success',
                        'consultant' => 'warning',
                        'client' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pivot.role')
                    ->label('Role')
                    ->options([
                        'team' => 'Team Member',
                        'consultant' => 'Consultant',
                        'client' => 'Client',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->modifyQueryUsing(fn ($query) => $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))),
                        Forms\Components\Select::make('role')
                            ->options([
                                'team' => 'Team Member',
                                'consultant' => 'Consultant',
                                'client' => 'Client',
                            ])
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
EOF

# Create Seeder
echo "Creating seeder..."
cat > database/seeders/WorkspaceSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $teamRole = Role::firstOrCreate(['name' => 'team']);
        $consultantRole = Role::firstOrCreate(['name' => 'consultant']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);

        // Create workspaces
        $workspace1 = Workspace::create([
            'name' => 'Acme Construction',
            'description' => 'Main construction workspace',
        ]);

        $workspace2 = Workspace::create([
            'name' => 'Beta Corp',
            'description' => 'Secondary workspace',
        ]);

        // Create admin users
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@rehome.build',
            'password' => Hash::make('password'),
            'workspace_id' => $workspace1->id,
        ]);
        $admin1->assignRole($adminRole);

        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@rehome.build',
            'password' => Hash::make('password'),
            'workspace_id' => $workspace2->id,
        ]);
        $admin2->assignRole($adminRole);

        // Create team members
        $team1 = User::create([
            'name' => 'John Team',
            'email' => 'team@rehome.build',
            'password' => Hash::make('password'),
        ]);
        $team1->assignRole($teamRole);

        $consultant1 = User::create([
            'name' => 'Jane Consultant',
            'email' => 'consultant@rehome.build',
            'password' => Hash::make('password'),
        ]);
        $consultant1->assignRole($consultantRole);

        $client1 = User::create([
            'name' => 'Bob Client',
            'email' => 'client@rehome.build',
            'password' => Hash::make('password'),
        ]);
        $client1->assignRole($clientRole);

        // Create sample projects for workspace 1
        $project1 = $workspace1->projects()->create([
            'name' => 'Website Redesign',
            'description' => 'Complete website overhaul',
            'status' => 'in_progress',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ]);

        $project2 = $workspace1->projects()->create([
            'name' => 'Mobile App',
            'description' => 'iOS and Android application',
            'status' => 'on_hold',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addMonths(2),
        ]);

        // Assign users to projects
        $project1->users()->attach($team1->id, ['role' => 'team']);
        $project1->users()->attach($consultant1->id, ['role' => 'consultant']);
        $project1->users()->attach($client1->id, ['role' => 'client']);

        $project2->users()->attach($team1->id, ['role' => 'team']);
    }
}
EOF

# Update DatabaseSeeder
cat > database/seeders/DatabaseSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WorkspaceSeeder::class,
        ]);
    }
}
EOF

# Create composer scripts
echo "Updating composer.json scripts..."
php -r "
\$composer = json_decode(file_get_contents('composer.json'), true);
\$composer['scripts'] = array_merge(\$composer['scripts'] ?? [], [
    'lint' => 'pint',
    'typecheck' => 'phpstan analyse --memory-limit=1G',
    'test' => 'phpunit',
    'audit' => 'composer audit --no-interaction'
]);
file_put_contents('composer.json', json_encode(\$composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
"

# Create phpstan.neon
cat > phpstan.neon << 'EOF'
parameters:
    level: 5
    paths:
        - app
        - database
    excludePaths:
        - app/Console/Kernel.php
        - app/Http/Kernel.php
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
EOF

# Update AdminPanelProvider
cat > app/Providers/Filament/AdminPanelProvider.php << 'EOF'
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authMiddleware(['auth', 'role:admin'])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName('Rehome Admin');
    }
}
EOF

echo "✅ Laravel backend structure created successfully!"