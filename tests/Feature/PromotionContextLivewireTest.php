<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

/**
 * Sends the Livewire update request a browser would send after the index page has loaded.
 *
 * The scoped promotion context is reset first so the update runs with the same empty
 * state as a separate PHP request instead of inheriting the context from the page load.
 *
 * @return TestResponse<Response>
 */
function sendWrestlersTableUpdate(string $search): TestResponse
{
    $page = get(route('wrestlers.index'))->assertOk();

    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    $snapshot = collect($matches[1])
        ->map(fn (string $encoded): string => html_entity_decode($encoded, ENT_QUOTES))
        ->first(fn (string $snapshot): bool => str_contains($snapshot, '"name":"wrestlers.tables.main"'));

    expect($snapshot)->not->toBeNull();

    app()->forgetScopedInstances();

    return withHeaders(['X-Livewire' => 'true'])
        ->postJson(route('default-livewire.update'), [
            'components' => [
                [
                    'snapshot' => $snapshot,
                    'updates' => ['search' => $search],
                    'calls' => [],
                ],
            ],
        ]);
}

test('livewire updates keep the active promotion context for promotion users', function (bool $isAdministrator, MembershipRole $role) {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Ownedwrestler Alpha']);
    Wrestler::factory()->for($otherPromotion, 'promotion')->create(['name' => 'Foreignwrestler Alpha']);
    $user = $isAdministrator
        ? User::factory()->administrator()->create(['status' => UserStatus::Active])
        : basicUser();
    $promotion->users()->attach($user, [
        'role' => $role,
        'status' => MembershipStatus::Active,
    ]);
    actingAs($user);

    $response = sendWrestlersTableUpdate('Alpha');

    $response->assertOk();
    expect($response->json('components.0.effects.html'))
        ->toBeString()
        ->toContain('Ownedwrestler Alpha')
        ->not->toContain('Foreignwrestler Alpha');
})->with([
    'administrator in the promotion' => [true, MembershipRole::Owner],
    'member of the promotion' => [false, MembershipRole::Member],
]);
