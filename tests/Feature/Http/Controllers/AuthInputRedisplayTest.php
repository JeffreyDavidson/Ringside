<?php

declare(strict_types=1);

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it('renders auth forms when rejected old input contains arrays', function (string $routeName): void {
    withSession(['_old_input' => [
        'email' => ['invalid'],
        'first_name' => ['invalid'],
        'last_name' => ['invalid'],
    ]]);

    $response = get(route($routeName));

    $response
        ->assertOk()
        ->assertDontSee('Array to string conversion');
})->with(['login', 'register', 'password.request']);
