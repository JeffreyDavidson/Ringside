<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Every outcome (sent, throttled, unknown email) gets the same response so the form
     * cannot be used to discover which emails are registered. The broker still enforces its
     * cooldown and only notifies existing users.
     */
    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->validated());

        $throttle = Config::integer('auth.passwords.'.Config::string('auth.defaults.passwords').'.throttle');

        return back()
            ->with('status', __('passwords.sent'))
            ->with('recovery_email', $request->string('email')->value())
            ->with('recovery_resend_at', now()->addSeconds($throttle)->timestamp);
    }
}
