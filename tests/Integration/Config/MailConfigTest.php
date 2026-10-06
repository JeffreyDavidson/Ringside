<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;

test('the resend api key is read from RESEND_API_KEY', function () {
    // Act
    $config = configFileWith('services', ['RESEND_API_KEY' => 're_from_environment']);

    // Assert
    expect($config)->toHaveKey('resend.key', 're_from_environment');
});

test('the resend mailer builds a resend transport from the services api key', function () {
    // Arrange
    config(['services.resend.key' => 're_test_key']);

    // Act
    $transport = Mail::mailer('resend')->getSymfonyTransport();

    // Assert
    expect($transport)->toBeInstanceOf(ResendTransport::class);
});
