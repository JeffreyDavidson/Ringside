<?php

namespace Tests\Unit\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
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
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $wrestlerMock = $this->createMock(Wrestler::class);
        $wrestlerMock->method('__get')->with('id')->willReturn(1);

        $subject = $this->createFormRequest(UpdateRequest::class);

        $subject->setRouteResolver(function () use ($wrestlerMock) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('wrestler')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('wrestler')->willReturn($wrestlerMock);

            return $stub;
        });

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
        $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
