<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\StoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
                'feet' => ['required', 'integer'],
                'inches' => ['required', 'integer', 'max:11'],
                'weight' => ['required', 'integer'],
                'hometown' => ['required', 'string'],
                'signature_move' => ['nullable', 'string'],
                'started_at' => ['nullable', 'string', 'date'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
    }
}
