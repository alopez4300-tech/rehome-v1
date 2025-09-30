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

if (! function_exists('feature')) {
    function feature(string $key): bool {
        return (bool) config("feature.$key");
    }
}

if (! function_exists('profile')) {
    function profile(?string $name = null): mixed {
        if ($name === null) {
            return config('feature.profile');
        }
        return config('feature.profile') === $name;
    }
}
