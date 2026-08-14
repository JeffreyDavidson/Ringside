<?php

declare(strict_types=1);

use App\Builders\Events\VenueBuilder;
use App\Models\Events\Venue;

test('orders venues alphabetically by name', function () {
    Venue::factory()->create(['name' => 'Zenith Arena']);
    Venue::factory()->create(['name' => 'Capitol Center']);
    Venue::factory()->create(['name' => 'Metro Hall']);

    $venues = Venue::query()
        ->alphabetical()
        ->get();

    expect($venues->pluck('name')->all())->toBe([
        'Capitol Center',
        'Metro Hall',
        'Zenith Arena',
    ]);
});

test('remains chainable with other query constraints', function () {
    $builder = Venue::query()
        ->alphabetical()
        ->where('city', 'Chicago');

    expect($builder)->toBeInstanceOf(VenueBuilder::class)
        ->and($builder->toSql())->toContain('where')
        ->and($builder->toSql())->toContain('order by');
});
