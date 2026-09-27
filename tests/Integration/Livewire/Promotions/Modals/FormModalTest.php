<?php

declare(strict_types=1);

use App\Livewire\Promotions\Modals\FormModal;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('promotion form interactions', function () {
    beforeEach(function () {
        actingAs(User::factory()->administrator()->create());
    });

    it('renders and opens the promotion form', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.promotions.modals.form-modal');
        $modal->assertSet('isModalOpen', true);
        $modal->assertSeeHtml('wire:model="form.name"');
        $modal->assertSeeHtml('wire:model="form.slug"');
        $modal->assertSee('Create Promotion');
    });

    it('opens through the shared modal host', function () {
        livewire('wire-elements-modal')
            ->dispatch('openModal', component: 'promotions.modals.form-modal')
            ->assertSee('Promotion name')
            ->assertSee('Slug');
    });

    it('creates a promotion and refreshes the directory', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Ringside Championship Wrestling',
            'form.slug' => 'ringside-championship-wrestling',
        ]);
        $modal->call('save');

        expect(Promotion::query()->where('slug', 'ringside-championship-wrestling')->firstOrFail()->name)
            ->toBe('Ringside Championship Wrestling');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('promotion-saved')
            ->assertSet('isModalOpen', false)
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '');
    });

    it('updates a promotion while allowing its existing slug', function () {
        $promotion = Promotion::factory()->create([
            'name' => 'Old Promotion Name',
            'slug' => 'old-promotion-name',
        ]);
        $modal = livewire(FormModal::class);
        $modal->call('openModal', $promotion->id);
        $modal->set('form.name', 'Updated Promotion Name');
        $modal->call('save');

        expect($promotion->refresh()->name)->toBe('Updated Promotion Name')
            ->and($promotion->slug)->toBe('old-promotion-name');
        $modal->assertHasNoErrors()->assertDispatched('promotion-saved');
    });

    it('rejects duplicate and invalid slugs', function (string $slug, string $rule) {
        Promotion::factory()->create(['slug' => 'already-used']);
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Valid Promotion',
            'form.slug' => $slug,
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.slug' => $rule]);
    })->with([
        'duplicate' => ['already-used', 'unique'],
        'spaces' => ['not a slug', 'alpha_dash'],
    ]);

    it('does not allow a regular user to open the promotion form', function () {
        actingAs(User::factory()->basicUser()->create());

        livewire(FormModal::class)
            ->call('openModal')
            ->assertForbidden();
    });
});
