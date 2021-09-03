<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $this->markTestIncomplete();
        $wrestler = Wrestler::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($wrestler) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('wrestler')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('wrestler')->willReturn($wrestler);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules(
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

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
