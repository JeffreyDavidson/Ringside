<?php

declare(strict_types=1);
use Dom\HTMLDocument;

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it('renders auth forms when rejected old input contains arrays', function (string $routeName): void {
    // Arrange
    withSession(['_old_input' => [
        'email' => ['invalid'],
        'first_name' => ['invalid'],
        'last_name' => ['invalid'],
    ]]);

    // Act
    $response = get(route($routeName));

    // Assert
    $response
        ->assertOk()
        ->assertDontSee('Array to string conversion');
})->with([
    'login' => 'login',
    'register' => 'register',
    'password.request' => 'password.request',
]);

it('renders a working submit control and masks passwords before JavaScript loads', function (string $routeName): void {
    // Arrange
    $url = route($routeName);

    // Act
    $response = get($url);

    // Assert
    $html = $response->getContent();
    if (! is_string($html)) {
        throw new RuntimeException('Expected the auth form response to contain HTML.');
    }

    $document = HTMLDocument::createFromString($html);
    $form = $document->querySelector('form');
    if ($form === null) {
        throw new RuntimeException('Expected the auth response to contain a form.');
    }

    expect($form->querySelector('button[type="submit"]'))->not->toBeNull();

    foreach ($form->querySelectorAll('input[name="password"], input[name="password_confirmation"]') as $input) {
        expect($input->getAttribute('type'))->toBe('password');
    }
})->with([
    'login' => 'login',
    'register' => 'register',
    'password.request' => 'password.request',
]);
