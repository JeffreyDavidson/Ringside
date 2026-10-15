<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
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
