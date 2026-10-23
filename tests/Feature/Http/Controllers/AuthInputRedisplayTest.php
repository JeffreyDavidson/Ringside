<?php

declare(strict_types=1);
use Dom\HTMLDocument;
use PHPUnit\Framework\Assert;

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

it('renders a submit control before JavaScript loads', function (string $routeName): void {
    // Arrange
    $url = route($routeName);

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk();

    $html = $response->getContent();
    Assert::assertIsString($html);
    $document = HTMLDocument::createFromString($html);
    $form = $document->querySelector('form');
    Assert::assertNotNull($form);
    expect($form->querySelector('button[type="submit"]'))->not->toBeNull();
})->with([
    'login' => 'login',
    'register' => 'register',
    'password.request' => 'password.request',
]);

it('masks passwords before JavaScript loads', function (string $routeName, int $count): void {
    // Arrange
    $url = route($routeName);

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk();

    $html = $response->getContent();
    Assert::assertIsString($html);
    $document = HTMLDocument::createFromString($html);
    $form = $document->querySelector('form');
    Assert::assertNotNull($form);
    $inputs = $form->querySelectorAll('input[name="password"], input[name="password_confirmation"]');
    expect($inputs)->toHaveCount($count);

    foreach ($inputs as $input) {
        expect($input->getAttribute('type'))->toBe('password');
    }
})->with([
    'login' => ['login', 1],
    'register' => ['register', 2],
]);
