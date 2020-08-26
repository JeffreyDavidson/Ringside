<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Venues\UpdateRequest;
use Tests\TestCase;

/**
 * @group wrestlers
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
        $subject = $this->createFormRequest(UpdateRequest::class, ['tag_team' => 1]);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
                'feet' => ['required', 'integer', 'min:5', 'max:7'],
                'inches' => ['required', 'integer', 'max:11'],
                'weight' => ['required', 'integer'],
                'hometown' => ['required', 'string'],
                'signature_move' => ['nullable', 'string'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['started_at'], ConditionalEmploymentStartDateRule::class);
    }
}
