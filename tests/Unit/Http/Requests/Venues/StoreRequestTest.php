<?php

namespace Tests\Unit\Http\Requests\Venues;

use App\Http\Requests\Venues\StoreRequest;
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
    public function authorized_users_can_save_a_venue()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
