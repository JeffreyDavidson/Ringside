<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
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

use function Pest\Laravel\actingAs;
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
