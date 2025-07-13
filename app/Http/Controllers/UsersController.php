<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Tests\Feature\Http\Controllers\UsersControllerTest;

/**
 * Controller for managing users.
 *
 * @see UsersControllerTest
 */
class UsersController
{
    /**
     * View a list of users.
     *
     * @see UsersControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', User::class);

        return view('users.index');
    }

    /**
     * Show the profile of a user.
     *
     * @see UsersControllerTest::test_show_returns_a_view()
     */
    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('users.show', [
            'user' => $user,
        ]);
    }
}
