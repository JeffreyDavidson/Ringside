<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\UpdateRequest;
use App\Rules\ConditionalActivationStartDateRule;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group titles
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unathenticated()
    {
        $subject = new UpdateRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $this->markTestIncomplete('Needs a route paramter set.');
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'min:3', 'ends_with:Title,Titles'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['activated_at'], ConditionalActivationStartDateRule::class);
    }
}
