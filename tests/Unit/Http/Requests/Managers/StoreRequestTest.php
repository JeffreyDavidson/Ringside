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
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'first_name' => ['required', 'string', 'min:3'],
                'last_name' => ['required', 'string', 'min:3'],
                'started_at' => ['nullable', 'string', 'date'],
            ],
            $rules
        );
    }
}
