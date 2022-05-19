<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Requests\Referees;

use App\Http\Requests\Referees\StoreRequest;
use App\Models\User;
use Tests\Factories\RefereeRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group referees
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function an_administrator_is_authorized_to_make_this_request()
    {
        $administrator = User::factory()->administrator()->create();

        $this->createRequest(StoreRequest::class)
            ->by($administrator)
            ->assertAuthorized();
    }

    /**
     * @test
     */
    public function a_non_administrator_is_not_authorized_to_make_this_request()
    {
        $user = User::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->by($user)
            ->assertNotAuthorized();
    }

    /**
     * @test
     */
    public function referee_first_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'first_name' => null,
            ]))
            ->assertFailsValidation(['first_name' => 'required']);
    }

    /**
     * @test
     */
    public function referee_first_name_must_be_a_string()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'first_name' => 123,
            ]))
            ->assertFailsValidation(['first_name' => 'string']);
    }

    /**
     * @test
     */
    public function referee_first_name_must_be_at_least_3_characters()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'first_name' => 'ab',
            ]))
            ->assertFailsValidation(['first_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function referee_last_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'last_name' => null,
            ]))
            ->assertFailsValidation(['last_name' => 'required']);
    }

    /**
     * @test
     */
    public function referee_last_name_must_be_a_string()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'last_name' => 123,
            ]))
            ->assertFailsValidation(['last_name' => 'string']);
    }

    /**
     * @test
     */
    public function referee_last_name_must_be_at_least_3_characters()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'last_name' => 'ab',
            ]))
            ->assertFailsValidation(['last_name' => 'min:3']);
    }

    /**
     * @test
     */
    public function referee_started_at_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'started_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function referee_started_at_must_be_a_string_if_provided()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'started_at' => 12345,
            ]))
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function referee_started_at_must_be_in_the_correct_date_format()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(RefereeRequestDataFactory::new()->create([
                'started_at' => 'not-a-date',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }
}
