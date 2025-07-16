<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;

/**
 * Integration tests for AppServiceProvider registrations.
 *
 * Tests service provider boot process and custom registrations
 * including validation rule replacers and other provider-level integrations.
 *
 * @see \App\Providers\AppServiceProvider
 */
describe('AppServiceProvider', function () {
    describe('custom validation rule replacers', function () {
        describe('ends_with replacer', function () {
            test('formats validation messages with proper grammar', function ($arguments, $expectedMessage) {
                $validator = Validator::make(['name' => 'Hello world'], [
                    'name' => "ends_with:{$arguments}",
                ]);

                expect($validator->errors()->first('name'))->toBe($expectedMessage);
            })->with([
                // Single argument
                ['foo', 'The name field must end with one of the following: foo.'],
                
                // Two arguments  
                ['foo,bar', 'The name field must end with one of the following: foo or bar.'],
                
                // Multiple arguments with Oxford comma
                ['foo,bar,baz', 'The name field must end with one of the following: foo, bar or baz.'],
                
                // Four arguments
                ['foo,bar,baz,qux', 'The name field must end with one of the following: foo, bar, baz or qux.'],
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