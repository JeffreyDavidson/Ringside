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
            ->call('openModal')
            ->set('form.name', 'Unsaved Team');

        // Act
        $component->call('clear');

        // Assert
        $component
            ->assertSet('form.name', '')
            ->assertSee('Create Tag Team');
    });

    it('restores the persisted model when clearing an edit form', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create(['name' => 'The Originals']);
        $component = livewire(FormModal::class)
            ->call('openModal', $tagTeam->id)
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
        $editing = livewire($modal)->call('openModal', $model->id);
        $creating = livewire($modal)->call('openModal');

        // Assert
        $editing->assertSee("Edit {$name}");
        $creating->assertSee($addTitle);
    })->with('base modal titles');

    it('shows the add title when the modal is reopened for a new record', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Rey Mysterio']);
        $component = livewire(WrestlerFormModal::class)->call('openModal', $wrestler->id);

        // Act
        $component->call('openModal');

        // Assert
        $component
            ->assertDontSee('Edit Rey Mysterio')
            ->assertSee('Add Wrestler');
    });
});

describe('localized modal titles', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    dataset('localized modal titles', [
        'stable' => [StableFormModal::class, fn (): Stable => Stable::factory()->create(), 'Create Stable', 'Edit Stable'],
        'title' => [TitleFormModal::class, fn (): Title => Title::factory()->create(), 'Create Title', 'Edit Title'],
        'venue' => [VenueFormModal::class, fn (): Venue => Venue::factory()->create(), 'Create Venue', 'Edit Venue'],
        'event' => [EventFormModal::class, fn (): Event => Event::factory()->create(), 'Create Event', 'Edit Event'],
        'user' => [UserFormModal::class, fn (): User => User::factory()->create(), 'Create User', 'Edit User'],
        'promotion' => [PromotionFormModal::class, fn (): Promotion => Promotion::factory()->create(), 'Create Promotion', 'Edit Promotion'],
        'tag team' => [FormModal::class, fn (): TagTeam => TagTeam::factory()->create(['name' => 'The Originals']), 'Create Tag Team', 'Edit The Originals'],
    ]);

    it('renders the translated create and edit titles', function (string $modal, Closure $makeModel, string $createTitle, string $editTitle): void {
        // Arrange
        $model = $makeModel();

        // Act
        $creating = livewire($modal)->call('openModal');
        $editing = livewire($modal)->call('openModal', $model->id);

        // Assert
        $creating->assertSee($createTitle);
        $editing->assertSee($editTitle);
    })->with('localized modal titles');

    it('renders the translated match form titles', function (): void {
        // Arrange
        $match = EventMatch::factory()->create();

        // Act
        $creating = livewire(MatchFormModal::class, ['eventId' => $match->event_id])->call('openModal');
        $editing = livewire(MatchFormModal::class, ['eventId' => $match->event_id])->call('openModal', $match->id);

        // Assert
        $creating->assertSee('Create Match');
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
