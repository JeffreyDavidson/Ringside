<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Tables\Main;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(User::factory()->administrator()->create(['status' => UserStatus::Active]));
});

it('renders the shared promotions table and toolbar', function (): void {
    Promotion::factory()->create(['name' => 'Ringside Wrestling']);

    livewire(Main::class)
        ->assertSuccessful()
        ->assertSeeHtml('data-test="table-toolbar"')
        ->assertSeeHtml('data-test="table-metadata"')
        ->assertSeeHtml('placeholder="Search promotions"')
        ->assertSee('All Promotions')
        ->assertSee('Ringside Wrestling')
        ->assertSee('Slug')
        ->assertSee('Members')
        ->assertSee('Created');
});

it('searches promotions by name and slug', function (): void {
    Promotion::factory()->create(['name' => 'Ringside Wrestling', 'slug' => 'ringside']);
    Promotion::factory()->create(['name' => 'Other Wrestling', 'slug' => 'ringside-other']);
    $component = livewire(Main::class);

    $component->set('search', 'Other');

    $component
        ->assertSee('Other Wrestling')
        ->assertDontSee('Ringside Wrestling');
});

it('renders the shared empty state when no promotions exist', function (): void {
    livewire(Main::class)
        ->assertSeeHtml('data-test="promotions-empty-state"')
        ->assertSee(__('promotions.empty_title'))
        ->assertSee(__('promotions.empty_description'));
});

it('escapes promotion names while rendering trusted table links', function (): void {
    $promotion = Promotion::factory()->create([
        'name' => '<img src=x onerror=alert(1)>',
    ]);

    livewire(Main::class)
        ->assertSeeHtml('&lt;img src=x onerror=alert(1)&gt;')
        ->assertDontSeeHtml('<img src=x onerror=alert(1)>')
        ->assertSeeHtml('<a')
        ->assertSeeHtml('href="'.route('promotions.show', $promotion).'"');
});
