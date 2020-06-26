<?php

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\UpdateRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\Factories\EventFactory;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * @group events
 */
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new UpdateRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function authorized_returns_true_when_users_can_update_a_event()
    {
        $user = UserFactory::new()->make();

        $this->actingAs($user);

        $subject = new UpdateRequest();

        Auth::shouldReceive('user')->once()->andReturn(true);

        // $this->assertTrue($subject->authorize());
    }

    /** @test */
    public function all_validation_rules_match()
    {
        $event = EventFactory::new()->make();

        $subject = $this->createFormRequest(UpdateRequest::class, ['event' => $event]);
        $rules = $subject->rules();

        $this->assertEquals(
            [
                'name' => [
                    'filled',
                    'string',
                    Rule::unique('events')->ignore($this->route('event')->id)
                ],
                'date' => [
                    'sometimes',
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
            $rules()
        );
    }
}
