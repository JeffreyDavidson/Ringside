<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal as EventFormModal;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Livewire\Titles\Modals\FormModal as TitleFormModal;
use App\Livewire\Wrestlers\Modals\FormModal as WrestlerFormModal;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

afterEach(function () {
    app(PromotionContextService::class)->clear();
});

function actInPromotion(Promotion $promotion): void
{
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
}

dataset('unique promotion fields', [
    'wrestler name' => [WrestlerFormModal::class, Wrestler::class, 'name', 'Bret Hart'],
    'wrestler signature move' => [WrestlerFormModal::class, Wrestler::class, 'signature_move', 'Sharpshooter'],
    'tag team name' => [TagTeamFormModal::class, TagTeam::class, 'name', 'The Hart Foundation'],
    'tag team signature move' => [TagTeamFormModal::class, TagTeam::class, 'signature_move', 'Hart Attack'],
    'stable name' => [StableFormModal::class, Stable::class, 'name', 'The Four Horsemen'],
    'title name' => [TitleFormModal::class, Title::class, 'name', 'World Heavyweight Title'],
    'event name' => [EventFormModal::class, Event::class, 'name', 'Summer Slam'],
]);

it('rejects a value already used in the same promotion', function (string $modal, string $model, string $field, string $value) {
    [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
    $model::factory()->for($promotionA, 'promotion')->create([$field => $value]);
    actInPromotion($promotionA);

    $component = livewire($modal)
        ->set("form.{$field}", $value)
        ->call('save');

    $component->assertHasErrors(["form.{$field}"]);
})->with('unique promotion fields');

it('accepts a value used only in another promotion without revealing it', function (string $modal, string $model, string $field, string $value) {
    [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
    $model::factory()->for($promotionA, 'promotion')->create([$field => $value]);
    actInPromotion($promotionB);

    $component = livewire($modal)
        ->set("form.{$field}", $value)
        ->call('save');

    $component->assertHasNoErrors(["form.{$field}"]);
})->with('unique promotion fields');

it('keeps the record own value valid when editing', function (string $modal, string $model, string $field, string $value) {
    $promotionA = Promotion::factory()->create();
    $record = $model::factory()->for($promotionA, 'promotion')->create([$field => $value]);
    actInPromotion($promotionA);

    $component = livewire($modal, ['modelId' => $record->getKey()])
        ->call('save');

    $component->assertHasNoErrors(["form.{$field}"]);
})->with('unique promotion fields');

describe('tag team managers', function () {
    it('rejects a manager of another promotion', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $foreignManager = Manager::factory()->for($promotionA, 'promotion')->create();
        actInPromotion($promotionB);

        $component = livewire(TagTeamFormModal::class)
            ->set('form.managers', [$foreignManager->id])
            ->call('save');

        $component->assertHasErrors(['form.managers.0']);
    });

    it('rejects an unknown manager id', function () {
        $promotionA = Promotion::factory()->create();
        actInPromotion($promotionA);

        $component = livewire(TagTeamFormModal::class)
            ->set('form.managers', [999999])
            ->call('save');

        $component->assertHasErrors(['form.managers.0']);
    });

    it('accepts a manager of the active promotion', function () {
        $promotionA = Promotion::factory()->create();
        $manager = Manager::factory()->for($promotionA, 'promotion')->create();
        actInPromotion($promotionA);

        $component = livewire(TagTeamFormModal::class)
            ->set('form.managers', [$manager->id])
            ->call('save');

        $component->assertHasNoErrors(['form.managers.0']);
    });
});
