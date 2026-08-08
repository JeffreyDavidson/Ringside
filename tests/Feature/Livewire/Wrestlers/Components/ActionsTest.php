<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('Actions Basic Functionality', function () {
    it('can be instantiated', function () {
        $component = testLivewire(Actions::class, ['wrestler' => $this->wrestler]);

        expect($component->instance())->toBeInstanceOf(Actions::class);
        expect($component->instance()->wrestler->id)->toBe($this->wrestler->id);
    });

    it('can mount with wrestler', function () {
        $component = testLivewire(Actions::class, ['wrestler' => $this->wrestler]);

        expect($component->instance()->wrestler->id)->toBe($this->wrestler->id);
    });

    it('can render successfully', function () {
        $component = testLivewire(Actions::class, ['wrestler' => $this->wrestler]);

        $component->assertSuccessful();
    });
});
