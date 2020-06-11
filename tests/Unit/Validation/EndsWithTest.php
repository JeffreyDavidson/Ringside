<?php

namespace Tests\Unit\Validation;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EndsWithTest extends TestCase
{
    /**
     * @test
     * @dataProvider endsWithMessages
     */
    public function it_formats_ends_with_message_correctly($arguments, $message): void
    {
        $arguments = implode(',', $arguments);

        $validator = Validator::make(['name' => 'Hello world'], [
            'name' => "ends_with:{$arguments}"
        ]);

        $this->assertEquals($message, $validator->errors()->first('name'));
    }

    public function endsWithMessages(): array
    {
        return [
            'one argument' => [['foo'], 'The name must end with foo.'],
            'two arguments' => [['foo', 'bar'], 'The name must end with foo or bar.'],
            'more than 2 arguments' => [['foo', 'bar', 'baz'], 'The name must end with foo, bar or baz.'],
        ];
    }
}
