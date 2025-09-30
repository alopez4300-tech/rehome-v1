<?php

use Illuminate\Support\Str;

if (! function_exists('ws')) {
    function ws(): ?\App\Models\Workspace {
        return auth()->user()?->currentWorkspace ?? null;
    }
}

if (! function_exists('ulid')) {
    function ulid(): string {
        return (string) Str::ulid();
    }
}
