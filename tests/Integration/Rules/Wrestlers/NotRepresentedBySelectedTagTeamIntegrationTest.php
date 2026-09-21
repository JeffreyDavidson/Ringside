<?php

declare(strict_types=1);

use App\Rules\Wrestlers\NotRepresentedBySelectedTagTeam;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown wrestler as a validation error', function () {
    $validator = Validator::make(
        ['wrestler' => 999],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect([1]))]],
    );

    expect($validator->errors()->has('wrestler'))->toBeTrue();
});
