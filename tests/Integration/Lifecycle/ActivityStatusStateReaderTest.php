<?php

declare(strict_types=1);

use App\Lifecycle\ActivityStatusStateReader;
use App\Models\Events\Venue;
use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;

test('it reads activity and retirement facts from a stable or title', function (Closure $makeModel, array $expectedState) {
    $model = $makeModel();

    expect(ActivityStatusStateReader::read($model))->toBe($expectedState);
})->with([
    'active stable' => [
        fn (): Stable => Stable::factory()->active()->create(),
        ['isRetired' => false, 'isCurrentlyActive' => true, 'hasFutureActivity' => false, 'hasActivityHistory' => true],
    ],
    'retired stable' => [
        fn (): Stable => Stable::factory()->retired()->create(),
        ['isRetired' => true, 'isCurrentlyActive' => false, 'hasFutureActivity' => false, 'hasActivityHistory' => true],
    ],
    'undebuted title' => [
        fn (): Title => Title::factory()->create(),
        ['isRetired' => false, 'isCurrentlyActive' => false, 'hasFutureActivity' => false, 'hasActivityHistory' => false],
    ],
]);

test('it rejects models that have no activity periods or retirements', function () {
    expect(fn () => ActivityStatusStateReader::read(Venue::factory()->create()))
        ->toThrow(LogicException::class, 'Activity status requires an active, retirable model.');
});
