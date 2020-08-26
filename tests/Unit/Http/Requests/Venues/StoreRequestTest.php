<?php

namespace Tests\Unit\Http\Requests\Venues;

use App\Http\Requests\Venues\StoreRequest;
use Tests\TestCase;

/**
 * @group venues
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /** @test */
    public function authorized_users_can_save_a_venue()
    {
        $subject = new StoreRequest;

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertExactValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
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
