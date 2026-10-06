<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Mail\Promotions\PromotionInvitationMail;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use Illuminate\Support\Carbon;

function invitationMail(string $timezone = 'UTC', ?Carbon $expiresAt = null): PromotionInvitationMail
{
    $promotion = Promotion::factory()->create(['name' => 'Ironclad Wrestling', 'timezone' => $timezone]);
    $invitation = PromotionInvitation::factory()
        ->for($promotion)
        ->forEmail('guest@example.test')
        ->withRole(MembershipRole::Manager)
        ->create(['expires_at' => $expiresAt ?? now()->addDays(30)]);
    $inviter = User::factory()->create(['first_name' => 'Dana', 'last_name' => 'Rivers']);
    $inviter = User::query()->findOrFail($inviter->id);

    return new PromotionInvitationMail($invitation, $inviter);
}

test('it has a subject naming the promotion', function () {
    // Arrange
    $mail = invitationMail();

    // Act
    $subject = $mail->envelope()->subject;

    // Assert
    expect($subject)->toBe("You're invited to join Ironclad Wrestling on Ringside");
});

test('it names the inviter, promotion and role and links to sign in and register without a token', function () {
    // Arrange
    $mail = invitationMail();

    // Act
    $html = $mail->render();

    // Assert
    expect($html)
        ->toContain('Dana Rivers')
        ->toContain('Ironclad Wrestling')
        ->toContain('Manager')
        ->toContain(route('login'))
        ->toContain(route('register'))
        ->toContain('this email address')
        ->not->toMatch('/[?&]\w*(token|signature)=/');
});

test('it shows the expiry date in the promotion time zone', function () {
    // Arrange
    $mail = invitationMail('Pacific/Auckland', Carbon::parse('2030-01-10 20:00:00', 'UTC'));

    // Act
    $html = $mail->render();

    // Assert
    expect($html)
        ->toContain('Jan 11, 2030')
        ->not->toContain('Jan 10, 2030');
});

test('it has a plain text alternative with the same links', function () {
    // Arrange
    $mail = invitationMail();

    // Act
    $mail->assertSeeInText('Ironclad Wrestling');

    // Assert
    $mail->assertSeeInText(route('login'));
    $mail->assertSeeInText(route('register'));
});

test('it reads the same whether or not an account exists for the address', function () {
    // Arrange
    $without = invitationMail()->render();
    User::factory()->create(['email' => 'guest@example.test']);

    // Act
    $with = invitationMail()->render();

    // Assert
    expect(preg_replace('/\s+/', ' ', strip_tags($with)))->toBe(preg_replace('/\s+/', ' ', strip_tags($without)));
});
