<?php

namespace Tests\Unit\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Rules\CanJoinTagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/*
 * @group tagteams
 * @group roster
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @var StoreRequest */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new StoreRequest();
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $rules = $this->subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['nullable', 'array', 'max:2'],
            'wrestlers.*' => ['bail', 'integer'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], CanJoinTagTeam::class);
    }
}
