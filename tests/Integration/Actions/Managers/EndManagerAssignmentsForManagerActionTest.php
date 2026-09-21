<?php

declare(strict_types=1);

use App\Actions\Managers\EndManagerAssignmentsForManagerAction;

test('manager assignment cleanup action can be resolved', function () {
    expect(resolve(EndManagerAssignmentsForManagerAction::class))->toBeInstanceOf(EndManagerAssignmentsForManagerAction::class);
});
