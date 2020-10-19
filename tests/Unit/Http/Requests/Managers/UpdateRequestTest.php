<?php

namespace Tests\Unit\Http\Requests\Managers;

use App\Http\Requests\Managers\UpdateRequest;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
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
                'first_name' => ['required', 'string', 'min:3'],
                'last_name' => ['required', 'string', 'min:3'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );

        // $this->assertValidationRuleContains('started_at', EmploymentStartDateCanBeChanged::class);
    }
}
