<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Events\Modals\FormModal as EventFormModal;
use App\Livewire\Managers\Modals\FormModal as ManagerFormModal;
use App\Livewire\Matches\Modals\FormModal as MatchFormModal;
use App\Livewire\Matches\Modals\ResultModal;
use App\Livewire\Promotions\Modals\FormModal as PromotionFormModal;
use App\Livewire\Referees\Modals\FormModal as RefereeFormModal;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\TagTeams\Modals\FormModal;
use App\Livewire\Titles\Modals\FormModal as TitleFormModal;
use App\Livewire\Users\Modals\FormModal as UserFormModal;
use App\Livewire\Venues\Modals\FormModal as VenueFormModal;
use App\Livewire\Wrestlers\Modals\FormModal as WrestlerFormModal;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

describe('clearing modal forms', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    it('clears an unsaved creation form', function (): void {
        // Arrange
        $component = livewire(FormModal::class)
            ->set('form.name', 'Unsaved Team');

        // Act
        $component->call('clear');

        // Assert
        $component
            ->assertSet('form.name', '')
            ->assertSee('Add Tag Team');
    });

    it('restores the persisted model when clearing an edit form', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create(['name' => 'The Originals']);
        $component = livewire(FormModal::class, ['modelId' => $tagTeam->id])
            ->set('form.name', 'Unsaved Rename');

        // Act
        $component->call('clear');

        // Assert
        $component
            ->assertSet('form.name', 'The Originals')
            ->assertSee('Edit The Originals');
    });
});

describe('base modal titles', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    dataset('base modal titles', [
        'wrestler' => [WrestlerFormModal::class, fn (): Wrestler => Wrestler::factory()->create(['name' => 'Rey Mysterio']), 'Rey Mysterio', 'Add Wrestler'],
        'referee' => [RefereeFormModal::class, fn (): Referee => Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']), 'Earl Hebner', 'Add Referee'],
        'manager' => [ManagerFormModal::class, fn (): Manager => Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']), 'Bobby Heenan', 'Add Manager'],
    ]);

    it('renders the translated edit and add titles', function (string $modal, Closure $makeModel, string $name, string $addTitle): void {
        // Arrange
        $model = $makeModel();

        // Act
        $editing = livewire($modal, ['modelId' => $model->id]);
        $creating = livewire($modal);

        // Assert
        $editing->assertSee("Edit {$name}");
        $creating->assertSee($addTitle);
    })->with('base modal titles');
});

describe('localized modal titles', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    dataset('localized modal titles', [
        'stable' => [StableFormModal::class, fn (): Stable => Stable::factory()->create(['name' => 'The Four Horsemen']), 'Add Stable', 'Edit The Four Horsemen'],
        'title' => [TitleFormModal::class, fn (): Title => Title::factory()->create(['name' => 'World Title']), 'Add Title', 'Edit World Title'],
        'venue' => [VenueFormModal::class, fn (): Venue => Venue::factory()->create(['name' => 'Madison Square Garden']), 'Add Venue', 'Edit Madison Square Garden'],
        'event' => [EventFormModal::class, fn (): Event => Event::factory()->create(['name' => 'Summer Showcase']), 'Add Event', 'Edit Summer Showcase'],
        'user' => [UserFormModal::class, fn (): User => User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']), 'Add User', 'Edit Jane Smith'],
        'promotion' => [PromotionFormModal::class, fn (): Promotion => Promotion::factory()->create(['name' => 'Ringside Wrestling']), 'Add Promotion', 'Edit Ringside Wrestling'],
        'tag team' => [FormModal::class, fn (): TagTeam => TagTeam::factory()->create(['name' => 'The Originals']), 'Add Tag Team', 'Edit The Originals'],
    ]);

    it('renders the translated create and edit titles', function (string $modal, Closure $makeModel, string $createTitle, string $editTitle): void {
        // Arrange
        $model = $makeModel();

        // Act
        $creating = livewire($modal);
        $editing = livewire($modal, ['modelId' => $model->id]);

        // Assert
        $creating->assertSeeHtml(">{$createTitle}</h2>");
        $editing->assertSeeHtml(">{$editTitle}</h2>");
    })->with('localized modal titles');

    it('escapes record names in the edit title exactly once', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => "O'Neil & Sons"]);

        // Act
        $editing = livewire(VenueFormModal::class, ['modelId' => $venue->id]);

        // Assert
        $editing->assertSeeHtml('>Edit O&#039;Neil &amp; Sons</h2>');
    });

    it('renders the translated match form titles', function (): void {
        // Arrange
        $match = EventMatch::factory()->create();

        // Act
        $creating = livewire(MatchFormModal::class, ['eventId' => $match->event_id]);
        $editing = livewire(MatchFormModal::class, ['eventId' => $match->event_id, 'modelId' => $match->id]);

        // Assert
        $creating->assertSee('Add Match');
        $editing->assertSee('Edit Match');
    });

    it('renders the translated match result titles', function (?MatchFinish $finish, string $title): void {
        // Arrange
        $match = EventMatch::factory()->create(['match_finish' => $finish]);

        // Act
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Assert
        $modal->assertSee($title);
    })->with([
        'no result yet' => [null, 'Record Match Result'],
        'result recorded' => [MatchFinish::Stipulation, 'Correct Match Result'],
    ]);
});

describe('modal lifecycle', function (): void {
    it('destroys modal component state when a modal closes', function (): void {
        // Act
        $destroyed = [
            FormModal::destroyOnClose(),
            WrestlerFormModal::destroyOnClose(),
            MatchFormModal::destroyOnClose(),
        ];

        // Assert
        expect($destroyed)->each->toBeTrue();
    });
});

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

    it('does not let an administrator who belongs to another promotion open the record from :dataset', function (string $page): void {
        // Arrange
        $world = createIsolatedPromotions();
        $admin = User::factory()->administrator()->create(['status' => UserStatus::Active]);
        $world['attacker']->users()->attach($admin, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        actingAs($admin);
        $wrestler = $world['records']['wrestler'];

        // Act
        $response = openModalFromPage($page, 'wrestlers.modals.form-modal', ['modelId' => $wrestler->getKey()]);

        // Assert
        expect($response->getStatusCode())->toBeIn([403, 404]);
        expect(Wrestler::withoutGlobalScopes()->whereKey($wrestler->getKey())->value('name'))->toBe('VictimWrestler');
    })->with(['dashboard', 'venues.index', 'users.index', 'promotions.index']);
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
