<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Users\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Users\User;
use App\Rules\Users\UniqueEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * A taken email gets the same response as a new account so the form cannot be used to discover which
     * emails are registered. A concurrent duplicate, or a lookalike email the database collation treats as equal,
     * hits the unique index and gets the same response. The password is still hashed so the two outcomes take similar time.
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        if (new UniqueEmail()->isTaken($request->string('email')->value())) {
            Hash::make($request->string('password')->value());

            return $this->accountPending();
        }

        try {
            $user = User::query()->create([
                'first_name' => $request->string('first_name')->value(),
                'last_name' => $request->string('last_name')->value(),
                'email' => $request->string('email')->value(),
                'password' => $request->string('password')->value(),
                'role' => Role::Basic,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->accountPending();
        }

        event(new Registered($user));

        return $this->accountPending();
    }

    private function accountPending(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('status', __('auth-forms.account_pending'));
    }
}
