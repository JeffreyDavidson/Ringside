<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Titles\Title;
use Illuminate\Database\QueryException;

test('a title has only one open activity period', function () {
    $title = Title::factory()->create();

    ActivityPeriod::factory()->for($title, 'activeable')->count(2)->create([
        'ended_at' => now(),
    ]);
    ActivityPeriod::factory()->for($title, 'activeable')->create([
        'ended_at' => null,
    ]);

    expect(fn () => ActivityPeriod::factory()->for($title, 'activeable')->create([
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
