<?php

declare(strict_types=1);

use App\Http\Middleware\AddWrestlingContext;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

test('adds the authenticated user full name to the request context', function () {
    $user = User::factory()->make([
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'full_name' => 'Jeffrey Davidson',
    ]);
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn (): User => $user);
    $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

    Context::flush();

    try {
        $response = resolve(AddWrestlingContext::class)->handle(
            $request,
            fn (): Response => new Response(),
        );

        expect($response->isSuccessful())->toBeTrue()
            ->and(Context::get('user_name'))->toBe('Jeffrey Davidson');
    } finally {
        Context::flush();
    }
});
