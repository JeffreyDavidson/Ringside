<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Enums\Role;
use App\Http\Requests\Wrestlers\StoreRequest;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    /** @var StoreRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StoreRequest();
    }

    /** @test */
    public function all_validation_rules_match()
    {
        $this->assertEquals(
            [
                'name' => [
                    'required',
                    'string',
                    'min:3'
                ],
                'feet' => [
                    'required',
                    'integer',
                    'min:5',
                    'max:7'
                ],
                'inches' => [
                    'required',
                    'integer',
                    'max:11'
                ],
                'weight' => [
                    'required',
                    'integer'
                ],
                'hometown' => [
                    'required',
                    'string'
                ],
                'signature_move' => [
                    'nullable',
                    'string'
                ],
                'started_at' => [
                    'nullable',
                    'string',
                    'date_format:Y-m-d H:i:s'
                ],
            ],
            $this->subject->rules()
        );
    }

    /** @test */
    public function  administrators_can_create_a_wrestler()
    {
        $user = UserFactory::new()->administrator()->make();

        $this->actingAs($user);

        $this->assertTrue($this->subject->authorize());
    }
}
