<?php

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group events
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $event = Event::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($event) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('event')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('event')->willReturn($event);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
                'date' => ['nullable', 'string', 'date'],
                'venue_id' => ['nullable', 'integer'],
                'preview' => ['nullable', 'string'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['venue_id'], Exists::class);
    }
}
