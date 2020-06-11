<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\StoreRequest;
use Illuminate\Validation\Rule;
use Tests\TestCase;

/**
 * @group titles
 */
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
                    'min:3',
                    'ends_with:Title,Titles',
                    Rule::unique('titles', 'name')
                ],
                'activated_at' => [
                    'nullable',
                    'string',
                    'date_format:Y-m-d H:i:s'
                ],
            ],
            $this->subject->rules()
        );
    }

    /** @test */
    public function authorized_users_can_store_a_title()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
