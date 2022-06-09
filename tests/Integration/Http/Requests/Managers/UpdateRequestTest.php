<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Requests\Managers;

use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Tests\Factories\ManagerRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group managers
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function manager_first_name_is_required()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'first_name' => null,
                    ])
            )
            ->assertFailsValidation(['first_name' => 'required']);
    }

    /**
     * @test
     */
    public function manager_first_name_must_be_a_string()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'first_name' => 123,
                    ])
            )
            ->assertFailsValidation(['first_name' => 'string']);
    }

    /**
     * @test
     */
    public function manager_first_name_must_be_at_least_3_characters()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'first_name' => 'ab',
                    ])
            )
            ->assertFailsValidation(['first_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function manager_last_name_is_required()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'last_name' => null,
                    ])
            )
            ->assertFailsValidation(['last_name' => 'required']);
    }

    /**
     * @test
     */
    public function manager_last_name_must_be_a_string()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'last_name' => 123,
                    ])
            )
            ->assertFailsValidation(['last_name' => 'string']);
    }

    /**
     * @test
     */
    public function manager_last_name_must_be_at_least_3_characters()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'last_name' => 'ab',
                    ])
            )
            ->assertFailsValidation(['last_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function manager_started_at_is_optional()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'started_at' => null,
                    ])
            )
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function manager_started_at_must_be_a_string_if_provided()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'started_at' => 12345,
                    ])
            )
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function manager_started_at_must_be_in_the_correct_date_format()
    {
        $manager = Manager::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'started_at' => 'not-a-date-format',
                    ])
            )
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function manager_started_at_cannot_be_changed_if_employment_start_date_has_past()
    {
        $manager = Manager::factory()->available()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'started_at' => Carbon::now()->toDateTimeString(),
                    ])
            )
            ->assertFailsValidation(['started_at' => 'employment_date_cannot_be_changed']);
    }

    /**
     * @test
     */
    public function manager_started_at_can_be_changed_if_employment_start_date_is_in_the_future()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('manager', $manager)
            ->validate(
                ManagerRequestDataFactory::new()
                    ->withManager($manager)
                    ->create([
                        'started_at' => Carbon::tomorrow()->toDateString(),
                    ])
            )
            ->assertPassesValidation();
    }
}
