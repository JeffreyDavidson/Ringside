<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use JMac\Testing\Double;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('Actions Basic Functionality', function () {
    it('can be instantiated', function () {
        $component = livewire(Actions::class, ['wrestler' => $this->wrestler]);

        expect($component->instance())->toBeInstanceOf(Actions::class);
        $component->assertSet('wrestler.id', $this->wrestler->id);
    });

    it('can mount with wrestler', function () {
        $component = livewire(Actions::class, ['wrestler' => $this->wrestler]);

        $component->assertSet('wrestler.id', $this->wrestler->id);
    });

    it('can render successfully', function () {
        $component = livewire(Actions::class, ['wrestler' => $this->wrestler]);

        $component->assertSuccessful();
    });

    it('does not translate unexpected action failures into business errors', function () {
        $employAction = Double::for(EmployAction::class);
        $employAction->expects('handle')
            ->throws(new LogicException('Unexpected employment failure.'));
        app()->instance(EmployAction::class, $employAction);
        $component = livewire(Actions::class, ['wrestler' => $this->wrestler]);

        expect(fn () => $component->call('employ'))
            ->toThrow(LogicException::class, 'Unexpected employment failure.');

        $employAction->verify();
    });
});
