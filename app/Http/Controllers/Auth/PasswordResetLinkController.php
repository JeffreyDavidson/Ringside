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

    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->validated());

        if (in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
            $throttle = Config::integer('auth.passwords.'.Config::string('auth.defaults.passwords').'.throttle');

            $request->session()->flash('recovery_resend_at', now()->addSeconds($throttle)->timestamp);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()
                ->with('status', __($status))
                ->with('recovery_email', $request->string('email')->value());
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->with('recovery_email', $request->string('email')->value())
                ->withErrors(['email' => __($status)]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
