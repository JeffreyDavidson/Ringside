<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\StoreRequest;
use Tests\TestCase;

/**
 * @group wrestlers
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

        $this->assertExactValidationRules(
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
    }
}
