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
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
                'date' => ['nullable', 'string', 'date'],
                'venue_id' => ['nullable', 'integer'],
                'preview' => ['nullable'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['venue_id'], Exists::class);
    }
}
