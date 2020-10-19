<?php

namespace Tests\Unit\Http\Requests\Referees;

use App\Http\Requests\Referees\UpdateRequest;
use App\Rules\EmploymentStartDateCanBeChanged;
use Tests\TestCase;

/**
 * @group referees
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
                'first_name' => ['required', 'string'],
                'last_name' => ['required', 'string'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );

        // $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
