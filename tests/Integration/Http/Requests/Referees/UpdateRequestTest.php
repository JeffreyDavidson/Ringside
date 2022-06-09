<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Requests\Referees;

use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Tests\Factories\RefereeRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group referees
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function referee_first_name_is_required()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'first_name' => null,
                    ])
            )
            ->assertFailsValidation(['first_name' => 'required']);
    }

    /**
     * @test
     */
    public function referee_first_name_must_be_a_string()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'first_name' => 123,
                    ])
            )
            ->assertFailsValidation(['first_name' => 'string']);
    }

    /**
     * @test
     */
    public function referee_first_name_must_be_at_least_3_characters()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'first_name' => 'ab',
                    ])
            )
            ->assertFailsValidation(['first_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function referee_last_name_is_required()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'last_name' => null,
                    ])
            )
            ->assertFailsValidation(['last_name' => 'required']);
    }

    /**
     * @test
     */
    public function referee_last_name_must_be_a_string()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'last_name' => 123,
                    ])
            )
            ->assertFailsValidation(['last_name' => 'string']);
    }

    /**
     * @test
     */
    public function referee_last_name_must_be_at_least_3_characters()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'last_name' => 'ab',
                    ])
            )
            ->assertFailsValidation(['last_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function referee_started_at_is_optional()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => null,
                    ])
            )
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function referee_started_at_must_be_a_string_if_provided()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => 12345,
                    ])
            )
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function referee_started_at_must_be_in_the_correct_date_format()
    {
        $referee = Referee::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => 'not-a-date-format',
                    ])
            )
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function referee_started_at_cannot_be_changed_if_employment_start_date_has_past()
    {
        $referee = Referee::factory()->bookable()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => Carbon::now()->toDateTimeString(),
                    ])
            )
            ->assertFailsValidation(['started_at' => 'employment_date_cannot_be_changed']);
    }

    /**
     * @test
     */
    public function referee_started_at_can_be_changed_if_employment_start_date_is_in_the_future()
    {
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('referee', $referee)
            ->validate(
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => Carbon::tomorrow()->toDateString(),
                    ])
            )
            ->assertPassesValidation();
    }
}
