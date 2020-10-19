<?php

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\UpdateRequest;
use Tests\TestCase;

/**
 * @group events
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['filled', 'string'],
                'date' => ['sometimes', 'string', 'date_format:Y-m-d H:i:s'],
                'venue_id' => ['nullable', 'integer'],
                'preview' => ['nullable'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['venue_id'], Exists::class);
    }
}
