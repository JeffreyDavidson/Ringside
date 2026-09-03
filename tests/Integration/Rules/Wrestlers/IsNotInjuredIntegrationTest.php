<?php

declare(strict_types=1);

use App\Rules\Wrestlers\IsNotInjured;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown wrestler as a validation error', function () {
    $validator = Validator::make(
        ['wrestler' => 999],
        ['wrestler' => [new IsNotInjured()]],
    );

    expect($validator->errors()->has('wrestler'))->toBeTrue();
});
