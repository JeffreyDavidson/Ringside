<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;

test('it formats ends with message correctly', function ($arguments, $message) {
    $arguments = implode(',', $arguments);

    $validator = Validator::make(['name' => 'Hello world'], [
        'name' => "ends_with:{$arguments}",
    ]);

    expect($validator->errors()->first('name'))->toEqual($message);
})->with([
    'one argument' => [['foo'], 'The name field must end with one of the following: foo.'],
    'two arguments' => [['foo', 'bar'], 'The name field must end with one of the following: foo or bar.'],
    'more than 2 arguments' => [['foo', 'bar', 'baz'], 'The name field must end with one of the following: foo, bar or baz.'],
]);
