<?php

use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

/**
 * Two promotions: "Victim" (owned by $owner) and "Attacker" (member $outsider), each with its own records.
 *
 * @return array{victim: Promotion, attacker: Promotion, owner: User, outsider: User, records: array<string, Model>}
 */
function createIsolatedPromotions(): array
{
    $victim = Promotion::factory()->create(['name' => 'Victim Promotion']);
    $attacker = Promotion::factory()->create(['name' => 'Attacker Promotion']);
    $owner = basicUser();
    $outsider = basicUser();
    $victim->users()->attach($owner, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    $attacker->users()->attach($outsider, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);

    $event = Event::factory()->for($victim, 'promotion')->scheduled()->create(['name' => 'VictimEvent']);

    return [
        'victim' => $victim,
        'attacker' => $attacker,
        'owner' => $owner,
        'outsider' => $outsider,
        'records' => [
            'wrestler' => Wrestler::factory()->for($victim, 'promotion')->bookable()->create(['name' => 'VictimWrestler']),
            'manager' => Manager::factory()->for($victim, 'promotion')->employed()->create(),
            'referee' => Referee::factory()->for($victim, 'promotion')->bookable()->create(),
            'tagTeam' => TagTeam::factory()->for($victim, 'promotion')->create(['name' => 'VictimTeam']),
            'stable' => Stable::factory()->for($victim, 'promotion')->create(['name' => 'VictimStable']),
            'title' => Title::factory()->for($victim, 'promotion')->active()->create(['name' => 'Victim Title']),
            'event' => $event,
            'match' => EventMatch::factory()->forEvent($event)->create(),
        ],
    ];
}

/**
 * Posts the Livewire update a browser sends when a modal is opened through the modal host of a page.
 *
 * @param  array<string, mixed>  $arguments
 * @return TestResponse<Response>
 */
function openModalFromPage(string $routeName, string $component, array $arguments): TestResponse
{
    $page = get(route($routeName))->assertOk();

    return postLivewireUpdate(
        snapshotOf((string) $page->getContent(), 'wire-elements-modal'),
        [['method' => 'openModal', 'params' => [$component, $arguments]]],
    );
}

/**
 * @param  array<int, array{method: string, params?: array<int, mixed>}>  $calls
 * @param  array<string, mixed>  $updates
 * @return TestResponse<Response>
 */
function postLivewireUpdate(string $snapshot, array $calls, array $updates = []): TestResponse
{
    // Start each update like a fresh request: Livewire otherwise skips the persistent middleware
    // (and so the promotion context) for a route it already handled earlier in the test.
    app()->forgetScopedInstances();
    app('livewire')->flushState();

    return withHeaders(['X-Livewire' => 'true'])->postJson(route('default-livewire.update'), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => $updates,
            'calls' => array_map(fn (array $call): array => ['path' => '', 'method' => $call['method'], 'params' => $call['params'] ?? []], $calls),
        ]],
    ]);
}

/** @param  TestResponse<Response>  $response */
function jsonString(TestResponse $response, string $key): string
{
    $value = $response->json($key);

    return is_string($value) ? $value : '';
}

function snapshotOf(string $html, string $component): string
{
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);

    return collect($matches[1])
        ->map(fn (string $encoded): string => html_entity_decode($encoded, ENT_QUOTES))
        ->firstOrFail(fn (string $snapshot): bool => str_contains($snapshot, "\"name\":\"{$component}\""));
}

dataset('promotionOwnedModals', [
    'wrestler' => ['wrestlers.modals.form-modal', 'wrestler', 'modelId'],
    'manager' => ['managers.modals.form-modal', 'manager', 'modelId'],
    'referee' => ['referees.modals.form-modal', 'referee', 'modelId'],
    'tag team' => ['tag-teams.modals.form-modal', 'tagTeam', 'modelId'],
    'stable' => ['stables.modals.form-modal', 'stable', 'modelId'],
    'title' => ['titles.modals.form-modal', 'title', 'modelId'],
    'event' => ['events.modals.form-modal', 'event', 'modelId'],
    'match' => ['matches.modals.form-modal', 'match', 'modelId'],
    'match result' => ['matches.modals.result-modal', 'match', 'matchId'],
]);

dataset('modalHostPages', ['dashboard', 'wrestlers.index']);

describe('promotion-owned modals', function (): void {
    it('does not open another promotion record', function (string $component, string $recordKey, string $argument, string $page): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['outsider']);
        $record = $world['records'][$recordKey];

        // Act
        $response = openModalFromPage($page, $component, [$argument => $record->getKey()]);

        // Assert
        expect($response->getStatusCode())->toBeIn([403, 404]);
    })->with('promotionOwnedModals')->with('modalHostPages');

    it('still opens for the owner of the promotion', function (string $component, string $recordKey, string $argument): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['owner']);
        $record = $world['records'][$recordKey];

        // Act
        $response = openModalFromPage('wrestlers.index', $component, [$argument => $record->getKey()]);

        // Assert
        $response->assertOk();
        expect($response->json('components.0.effects.html'))->toContain($component);
    })->with('promotionOwnedModals');

    it('does not list another promotion in create form options', function (string $component): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['outsider']);

        // Act
        $response = openModalFromPage('dashboard', $component, []);

        // Assert
        $response->assertOk();
        expect($response->json('components.0.effects.html'))->not->toContain('VictimWrestler');
    })->with([
        'tag team' => 'tag-teams.modals.form-modal',
        'stable' => 'stables.modals.form-modal',
    ]);

    it('books the match form only onto :dataset', function (bool $ownEvent, array $statuses, int $addedMatches): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['outsider']);
        $event = $ownEvent
            ? Event::factory()->for($world['attacker'], 'promotion')->scheduled()->create()
            : $world['records']['event'];
        $matchCount = EventMatch::withoutGlobalScopes()->where('event_id', $event->getKey())->count();
        $wrestlers = Wrestler::factory()->for($world['attacker'], 'promotion')->bookable()->count(2)->create();
        $referee = Referee::factory()->for($world['attacker'], 'promotion')->bookable()->create();
        $opened = openModalFromPage('dashboard', 'matches.modals.form-modal', ['eventId' => $event->getKey()]);
        $formModal = snapshotOf(jsonString($opened, 'components.0.effects.html'), 'matches.modals.form-modal');
        $typed = postLivewireUpdate($formModal, [], ['form.matchType' => MatchType::Singles->value]);

        // Act
        $response = postLivewireUpdate(jsonString($typed, 'components.0.snapshot'), [['method' => 'save']], [
            'form.competitors' => [
                ['wrestlers' => [$wrestlers->modelKeys()[0]]],
                ['wrestlers' => [$wrestlers->modelKeys()[1]]],
            ],
            'form.referees' => [$referee->id],
        ]);

        // Assert
        expect($response->getStatusCode())->toBeIn($statuses);
        expect(EventMatch::withoutGlobalScopes()->where('event_id', $event->getKey())->count())->toBe($matchCount + $addedMatches);
    })->with([
        'an event of the active promotion' => [true, [200], 1],
        'another promotion event passed when opening it' => [false, [403, 404], 0],
    ]);

    it('does not let the event of an opened match form be swapped for another promotion event', function (): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['outsider']);
        $ownEvent = Event::factory()->for($world['attacker'], 'promotion')->scheduled()->create();
        $victimEvent = $world['records']['event'];
        $opened = openModalFromPage('dashboard', 'matches.modals.form-modal', ['eventId' => $ownEvent->id]);
        $formModal = snapshotOf(jsonString($opened, 'components.0.effects.html'), 'matches.modals.form-modal');

        // Act
        $response = postLivewireUpdate($formModal, [['method' => 'save']], ['eventId' => $victimEvent->getKey()]);

        // Assert
        // Livewire rejects the locked property: 419 in production, the exception page (500) in debug mode.
        expect($response->getStatusCode())->toBeIn([419, 500]);
        expect(EventMatch::withoutGlobalScopes()->where('event_id', $victimEvent->getKey())->count())->toBe(1);
    });

    it('does not let an administrator who belongs to another promotion open the record', function (): void {
        // Arrange
        $world = createIsolatedPromotions();
        $admin = User::factory()->administrator()->create(['status' => UserStatus::Active]);
        $world['attacker']->users()->attach($admin, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        actingAs($admin);
        $wrestler = $world['records']['wrestler'];

        // Act
        $response = openModalFromPage('dashboard', 'wrestlers.modals.form-modal', ['modelId' => $wrestler->getKey()]);

        // Assert
        expect($response->getStatusCode())->toBeIn([403, 404]);
        expect(Wrestler::withoutGlobalScopes()->whereKey($wrestler->getKey())->value('name'))->toBe('VictimWrestler');
    });
});

describe('administration modals', function (): void {
    it('are closed to non-administrators', function (string $component, string $target): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs($world['owner']);
        $id = $target === 'promotion' ? $world['attacker']->getKey() : $world['outsider']->getKey();

        // Act
        $response = openModalFromPage('dashboard', $component, ['modelId' => $id]);

        // Assert
        expect($response->getStatusCode())->toBeIn([403, 404]);
    })->with([
        'users' => ['users.modals.form-modal', 'user'],
        'promotions of another owner' => ['promotions.modals.form-modal', 'promotion'],
    ]);

    it('open for administrators', function (string $component, string $target): void {
        // Arrange
        $world = createIsolatedPromotions();
        actingAs(administrator());
        $id = $target === 'promotion' ? $world['attacker']->getKey() : $world['outsider']->getKey();

        // Act
        $response = openModalFromPage('dashboard', $component, ['modelId' => $id]);

        // Assert
        $response->assertOk();
    })->with([
        'users' => ['users.modals.form-modal', 'user'],
        'promotions' => ['promotions.modals.form-modal', 'promotion'],
    ]);
});
