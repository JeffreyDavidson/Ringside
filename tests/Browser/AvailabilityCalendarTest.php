<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;

test('an operator reviews dates sees them on the phone calendar and cancels the restriction', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create(['name' => 'Harbor Talent']);
    $day = now()->addWeek()->setTimezone($promotion->timezone)->toDateString();
    actingAs($user);

    // Act / Assert
    $page = visit(route('wrestlers.show', $person));
    $page->resize(390, 844)->keys('[data-test=availability-add] summary', 'Enter')
        ->type('First unavailable day', $day)->type('Last unavailable day', $day)
        ->type('#availability-reason', 'Outside commitment')->click('Review affected bookings')
        ->assertSee('No affected bookings found')->assertNoAccessibilityIssues()
        ->click('Save unavailable dates')->assertSee('Unavailable dates saved')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth');
    $page->script('document.querySelector("[data-test=availability-add]").closest("section").scrollIntoView()');
    $page->screenshot(fullPage: true, filename: 'availability-profile-phone')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'availability-profile-desktop')
        ->assertNoJavascriptErrors()->assertNoAccessibilityIssues();
    expect(Unavailability::query()->count())->toBe(1);

    $calendar = visit(route('availability.index', ['date' => $day]));
    $calendar->resize(390, 844)->assertSee('Harbor Talent')->assertSee('Outside commitment')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->screenshot(fullPage: false, filename: 'availability-calendar-phone')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'availability-calendar-desktop')
        ->type('Search by name', 'No such person')->assertSee('No unavailability recorded for this date and filter')
        ->type('Search by name', 'Harbor')->assertSee('Harbor Talent')
        ->assertNoJavascriptErrors();

    $profile = visit(route('wrestlers.show', $person));
    $profile->click('Cancel entry')->assertSee('Its history will be kept')->click('Confirm cancellation')
        ->assertSee('Unavailable entry cancelled')->assertNoJavascriptErrors();
    expect(Unavailability::query()->firstOrFail()->cancelled_at)->not->toBeNull();
});
