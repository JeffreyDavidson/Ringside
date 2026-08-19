<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Stables\CanJoinStable;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown stable member as a validation error', function () {
    $validator = Validator::make(
        ['member' => 999],
        ['member' => [new CanJoinStable(Wrestler::class)]],
    );

    expect($validator->errors()->has('member'))->toBeTrue();
});
