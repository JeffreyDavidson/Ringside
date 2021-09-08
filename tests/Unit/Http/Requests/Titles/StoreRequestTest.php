<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\StoreRequest;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group titles
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
                'name' => ['required', 'string', 'min:3', 'ends_with:Title,Titles'],
                'activated_at' => ['nullable', 'string', 'date'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
    }
}
