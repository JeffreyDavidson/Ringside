<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Components\WrestlerActionsComponent;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('WrestlerActionsComponent Basic Functionality', function () {
    it('can be instantiated', function () {
        $component = Livewire::test(WrestlerActionsComponent::class, ['wrestler' => $this->wrestler]);

        expect($component->instance())->toBeInstanceOf(WrestlerActionsComponent::class);
        expect($component->instance()->wrestler->id)->toBe($this->wrestler->id);
    });

    it('can mount with wrestler', function () {
        $component = Livewire::test(WrestlerActionsComponent::class, ['wrestler' => $this->wrestler]);

        expect($component->instance()->wrestler->id)->toBe($this->wrestler->id);
    });

    it('has required action methods', function () {
        $component = new WrestlerActionsComponent();

        expect(method_exists($component, 'employ'))->toBeTrue();
        expect(method_exists($component, 'release'))->toBeTrue();
        expect(method_exists($component, 'retire'))->toBeTrue();
        expect(method_exists($component, 'unretire'))->toBeTrue();
        expect(method_exists($component, 'suspend'))->toBeTrue();
        expect(method_exists($component, 'reinstate'))->toBeTrue();
        expect(method_exists($component, 'injure'))->toBeTrue();
        expect(method_exists($component, 'healFromInjury'))->toBeTrue();
    });

    it('can render successfully', function () {
        $component = Livewire::test(WrestlerActionsComponent::class, ['wrestler' => $this->wrestler]);

        $component->assertSuccessful();
    });
});
