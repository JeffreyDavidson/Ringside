<?php

declare(strict_types=1);

use App\Enums\Events\LiveItemOperation;

it('identifies whether an operation needs a reason', function (LiveItemOperation $operation, bool $requiresReason) {
    expect($operation->requiresReason())->toBe($requiresReason);
})->with([
    'start' => [LiveItemOperation::Start, false],
    'finish' => [LiveItemOperation::Finish, false],
    'skip' => [LiveItemOperation::Skip, true],
    'correct' => [LiveItemOperation::Correct, true],
    'reopen' => [LiveItemOperation::Reopen, true],
]);
