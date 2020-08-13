<?php

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\StoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * @group events
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @var StoreRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StoreRequest();
    }

    /** @test */
    public function it_should_not_authorize_the_request_if_the_user_is_not_logged_in()
    {
        $user = UserFactory::new()->basicUser()->make();
        $subject = new StoreRequest();
        $subject->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function authorized_users_can_store_a_event()
    {
        $this->assertTrue($this->subject->authorize());
    }

    /** @test */
    public function all_validation_rules_match()
    {
        $this->assertEquals(
            [
                'name' => [
                    'required',
                    'string',
                    Rule::unique('events', 'name')
                ],
                'date' => [
                    'nullable',
                    'string',
                    'date_format:Y-m-d H:i:s'
                ],
                'venue_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('venues', 'id')
                ],
                'preview' => [
                    'nullable'
                ],
            ],
            $this->subject->rules()
        );
    }
}
