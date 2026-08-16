<?php

declare(strict_types=1);

use App\Console\Commands\RingsideMakeTest;
use App\Models\Events\Event;
use App\Models\Roster\Wrestlers\Wrestler;

final class TestableRingsideMakeTest extends RingsideMakeTest
{
    public function pathFor(string $modelClass): string
    {
        return $this->modelTestPath($modelClass, class_basename($modelClass).'Test');
    }
}

test('it mirrors model namespaces in generated unit test paths', function (string $modelClass, string $expectedPath) {
    $command = new TestableRingsideMakeTest();

    expect($command->pathFor($modelClass))->toBe($expectedPath);
})->with([
    'roster model' => [
        Wrestler::class,
        'tests/Unit/Models/Roster/Wrestlers/WrestlerTest.php',
    ],
    'event model' => [
        Event::class,
        'tests/Unit/Models/Events/EventTest.php',
    ],
    'root model' => [
        'App\\Models\\User',
        'tests/Unit/Models/UserTest.php',
    ],
]);
