<?php

namespace App\Filament\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $u = $request->user();

        if (method_exists($u, 'hasRole') && $u->hasRole('admin')) {
            return new RedirectResponse('/admin');
        }

        return new RedirectResponse('/app');
    }
}
