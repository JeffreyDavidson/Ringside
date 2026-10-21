<?php

declare(strict_types=1);

use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Queries\Crew\CrewContactSearchQuery;

use function Pest\Laravel\actingAs;

test('finds contacts by name in name order and treats wildcards literally', function (): void {
    // Arrange
    actingAs(administrator());
    CrewContact::factory()
        ->create(['name' => 'Zed Sound']);
    CrewContact::factory()
        ->create(['name' => 'Alex Sound']);
    CrewContact::factory()
        ->create(['name' => 'Alex%Sound']);
    CrewContact::factory()
        ->create(['name' => 'Lights Jo']);

    // Act
    $sound = resolve(CrewContactSearchQuery::class)->get('sound');
    $wildcard = resolve(CrewContactSearchQuery::class)->get('Alex%');

    // Assert
    expect($sound->pluck('name')->all())->toBe(['Alex Sound', 'Alex%Sound', 'Zed Sound'])
        ->and($wildcard->pluck('name')->all())->toBe(['Alex%Sound']);
});

test('hides archived contacts unless asked and limits the results to twenty', function (): void {
    // Arrange
    actingAs(administrator());
    CrewContact::factory()
        ->count(21)
        ->create();
    $archived = CrewContact::factory()
        ->create(['name' => 'Archived Person', 'archived_at' => now()]);

    // Act
    $active = resolve(CrewContactSearchQuery::class)->get('');
    $archivedOnly = resolve(CrewContactSearchQuery::class)->get('Archived', includeArchived: true);
    $hidden = resolve(CrewContactSearchQuery::class)->get('Archived');

    // Assert
    expect($active)->toHaveCount(20)
        ->and($archivedOnly->modelKeys())->toBe([$archived->id])
        ->and($hidden)->toBeEmpty();
});

test('limits the search to one promotion when given its id', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()
        ->create();
    $own = CrewContact::factory()
        ->create(['promotion_id' => $promotion->id, 'name' => 'Own Crew']);
    CrewContact::factory()
        ->create(['name' => 'Other Crew']);

    // Act
    $found = resolve(CrewContactSearchQuery::class)->get('crew', $promotion->id);

    // Assert
    expect($found->modelKeys())->toBe([$own->id]);
});
