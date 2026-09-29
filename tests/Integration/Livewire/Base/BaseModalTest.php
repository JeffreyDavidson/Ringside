<?php

declare(strict_types=1);

use App\Livewire\Managers\Modals\FormModal as ManagerFormModal;
use App\Livewire\Referees\Modals\FormModal as RefereeFormModal;
use App\Livewire\TagTeams\Modals\FormModal;
use App\Livewire\Wrestlers\Modals\FormModal as WrestlerFormModal;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

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
