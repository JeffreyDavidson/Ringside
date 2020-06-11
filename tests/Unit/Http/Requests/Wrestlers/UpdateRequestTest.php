<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Venues\UpdateRequest;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    /** @var UpdateRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new UpdateRequest();
    }

    /** @test */
    public function all_validation_rules_match()
    {
        $this->assertEquals(
            [
                'name' => [
                    'required',
                    'string'
                ],
                'address1' => [
                    'required',
                    'string'
                ],
                'address2' => [
                    'nullable',
                    'string'
                ],
                'city' => [
                    'required',
                    'string'
                ],
                'state' => [
                    'required',
                    'string'
                ],
                'zip' => [
                    'required',
                    'integer',
                    'digits:5'
                ],
            ],
            $this->subject->rules()
        );
    }

    /** @test */
    public function authorized_users_can_save_a_venue()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
