<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use Illuminate\Database\QueryException;

test('a title has only one open activity period', function () {
    $title = Title::factory()->create();

    TitleActivityPeriod::factory()->count(2)->create([
        'title_id' => $title->id,
        'ended_at' => now(),
    ]);
    TitleActivityPeriod::factory()->create([
        'title_id' => $title->id,
        'ended_at' => null,
    ]);

    expect(fn () => TitleActivityPeriod::factory()->create([
        'title_id' => $title->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
