<?php

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\StoreRequest;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group events
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /** @test */
    public function authorize_returns_false_when_unauthenticated()
    {
        $subject = new StoreRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string'],
                'date' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
                'venue_id' => ['nullable', 'integer'],
                'preview' => ['nullable'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['venue_id'], Exists::class);
    }
}
