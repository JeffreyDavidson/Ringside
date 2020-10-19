<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Rules\EmploymentStartDateCanBeChanged;
use Tests\TestCase;

/**
 * @group wrestlers
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
                'name' => ['required', 'string', 'min:3'],
                'feet' => ['required', 'integer', 'min:5', 'max:7'],
                'inches' => ['required', 'integer', 'max:11'],
                'weight' => ['required', 'integer'],
                'hometown' => ['required', 'string'],
                'signature_move' => ['nullable', 'string'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );

        // $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
