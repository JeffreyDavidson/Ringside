<?php

declare(strict_types=1);

use App\Mail\Promotions\PromotionInvitationMail;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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

test('the reply-to address is read from MAIL_REPLY_TO_ADDRESS and MAIL_REPLY_TO_NAME', function () {
    // Act
    $config = configFileWith('mail', [
        'MAIL_REPLY_TO_ADDRESS' => 'support@example.test',
        'MAIL_REPLY_TO_NAME' => 'Ringside Support',
    ]);

    // Assert
    expect($config)
        ->toHaveKey('reply_to.address', 'support@example.test')
        ->toHaveKey('reply_to.name', 'Ringside Support');
});

test('no reply-to address is configured unless MAIL_REPLY_TO_ADDRESS is set', function () {
    // Act
    $config = configFileWith('mail', ['MAIL_REPLY_TO_ADDRESS' => null]);

    // Assert
    expect($config)->toHaveKey('reply_to.address', null);
});

test('outgoing mail carries the configured reply-to address', function (?string $address, array $expected) {
    // Arrange
    config(['mail.default' => 'array', 'mail.reply_to' => ['address' => $address, 'name' => 'Ringside Support']]);
    Mail::purge('array');
    $invitation = PromotionInvitation::factory()->create();

    // Act
    $sent = Mail::mailer('array')
        ->to($invitation->email)
        ->send(new PromotionInvitationMail($invitation, User::factory()->create()));

    // Assert
    $message = $sent?->getSymfonySentMessage()->getOriginalMessage();

    $replyTo = $message instanceof Email
        ? array_map(fn (Address $replyTo): string => $replyTo->getAddress(), $message->getReplyTo())
        : null;

    expect($replyTo)->toBe($expected);
})->with([
    'a reply-to address is set' => ['support@example.test', ['support@example.test']],
    'no reply-to address is set' => [null, []],
]);
