<?php

namespace Tests\Unit\Http\Requests\Venues;

use App\Http\Requests\Venues\UpdateRequest;
use Tests\TestCase;

/**
 * @group venues
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string'],
                'address1' => ['required', 'string'],
                'address2' => ['nullable', 'string'],
                'city' => ['required', 'string'],
                'state' => ['required', 'string'],
                'zip' => ['required', 'integer', 'digits:5'],
            ],
            $rules
        );
    }
}
