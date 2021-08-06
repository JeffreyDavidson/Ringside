<?php

namespace Tests\Unit\Http\Requests\Referees;

use App\Http\Requests\Referees\StoreRequest;
use Tests\TestCase;

/**
 * @group referees
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

        $this->assertExactValidationRules(
            [
                'first_name' => ['required', 'string'],
                'last_name' => ['required', 'string'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );
    }
}
