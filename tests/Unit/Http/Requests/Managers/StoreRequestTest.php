<?php

namespace Tests\Unit\Http\Requests\Managers;

use App\Http\Requests\Managers\StoreRequest;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /** @test */
    public function authorize_returns_false_when_unauthenticated()
    {
        $subject = new StoreRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'first_name' => ['required', 'string', 'min:3'],
                'last_name' => ['required', 'string', 'min:3'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );
    }
}
