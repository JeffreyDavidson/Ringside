<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Users\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Users\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'first_name' => $request->string('first_name')->value(),
            'last_name' => $request->string('last_name')->value(),
            'email' => $request->string('email')->value(),
            'password' => $request->string('password')->value(),
            'role' => Role::Basic,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
