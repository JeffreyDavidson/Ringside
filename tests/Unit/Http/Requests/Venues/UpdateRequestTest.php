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
        $subject = $this->createFormRequest(UpdateRequest::class, ['venue' => 1]);
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
