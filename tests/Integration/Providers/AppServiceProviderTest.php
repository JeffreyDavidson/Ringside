<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Validator;

/**
 * Integration tests for AppServiceProvider registrations.
 *
 * Tests service provider boot process and framework integrations.
 *
 * @see AppServiceProvider
 */
describe('AppServiceProvider', function () {
    describe('Laravel validation rules', function () {
        describe('ends_with rule', function () {
            test('uses Laravel validation messages', function ($arguments, $expectedMessage) {
                $validator = Validator::make(['name' => 'Hello world'], [
                    'name' => "ends_with:{$arguments}",
                ]);

                expect($validator->errors()->first('name'))->toBe($expectedMessage);
            })->with([
                // Single argument
                ['foo', 'The name field must end with one of the following: foo.'],

                // Two arguments
                ['foo,bar', 'The name field must end with one of the following: foo, bar.'],

                // Multiple arguments with Oxford comma
                ['foo,bar,baz', 'The name field must end with one of the following: foo, bar, baz.'],

                // Four arguments
                ['foo,bar,baz,qux', 'The name field must end with one of the following: foo, bar, baz, qux.'],
            ]);

            test('validates rule functionality correctly', function ($value, $rule, $shouldPass) {
                $validator = Validator::make(['name' => $value], ['name' => $rule]);

                expect($validator->passes())->toBe($shouldPass);
            })->with([
                // Passes when value ends with specified string
                ['Hello world', 'ends_with:world', true],

                // Fails when value does not end
                ['Hello world', 'ends_with:foo', false],

                // Passes with multiple options
                ['Hello world', 'ends_with:foo,world,bar', true],

                // Fails with multiple options that don't match
                ['Hello world', 'ends_with:foo,baz,bar', false],
            ]);
        });
    });
});
