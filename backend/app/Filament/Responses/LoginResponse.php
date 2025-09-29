<?php

namespace App\Filament\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $u = $request->user();

        if (method_exists($u, 'isSystemAdmin') && $u->isSystemAdmin()) {
            return redirect()->intended('/admin');
        }

        if (method_exists($u, 'isWorkspaceAdmin') && $u->isWorkspaceAdmin()) {
            return redirect()->intended('/ops');
        }

        return redirect()->intended('/app');
    }
}
