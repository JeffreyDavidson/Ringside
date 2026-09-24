<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            return $next($request);
        }

        $user = User::query()->whereKey($authenticatedUser->getKey())->first();

        if ($user instanceof User && $user->status === UserStatus::Active) {
            Auth::setUser($user);

            return $next($request);
        }

        $statusMessage = $user?->status === UserStatus::Unverified
            ? __('auth-forms.account_pending')
            : __('auth-forms.account_inactive');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $statusMessage);
    }
}
