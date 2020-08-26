<?php

namespace Tests\Unit\Http\Requests\Managers;

use App\Http\Requests\Managers\UpdateRequest;
use App\Rules\ConditionalEmploymentStartDateRule;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group requests
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
    public function rules_returns_validation_requirements()
    {
        $this->markTestIncomplete('Needs a route paramter set.');
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'first_name' => ['required', 'string', 'min:3'],
                'last_name' => ['required', 'string', 'min:3'],
            ],
            $rules
        );

        $this->assertValidationRuleContains('started_at', ConditionalEmploymentStartDateRule::class);
    }
}
