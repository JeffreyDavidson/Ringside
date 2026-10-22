<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

// full_name is a generated column, so the record is read back the way a screen loads it.
test('a deletion refusal names the person and their kind', function (Closure $exception, string $message): void {
    // Act
    $result = $exception();

    // Assert
    expect($result->getMessage())->toBe($message);
})->with([
    'referee booked in a match' => [
        fn (): CannotBeDeletedException => CannotBeDeletedException::bookedInUpcomingMatch(
            Referee::factory()
                ->create(['first_name' => 'Earl', 'last_name' => 'Hebner'])
                ->refresh(),
        ),
        "Referee 'Earl Hebner' cannot be deleted because it is booked in a match that is upcoming or has no result. Remove it from the match or record the result first.",
    ],
    'wrestler booked in a match' => [
        fn (): CannotBeDeletedException => CannotBeDeletedException::bookedInUpcomingMatch(
            Wrestler::factory()->make(['name' => 'Harbor Hawk']),
        ),
        "Wrestler 'Harbor Hawk' cannot be deleted because it is booked in a match that is upcoming or has no result. Remove it from the match or record the result first.",
    ],
    'manager already deleted' => [
        fn (): CannotBeDeletedException => CannotBeDeletedException::alreadyDeleted(
            Manager::factory()
                ->create(['first_name' => 'Paul', 'last_name' => 'Heyman'])
                ->refresh(),
        ),
        "Manager 'Paul Heyman' cannot be deleted because it is already deleted.",
    ],
]);
