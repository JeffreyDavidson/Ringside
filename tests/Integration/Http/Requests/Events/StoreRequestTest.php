<?php

namespace Tests\Integration\Http\Requests\Events;

use App\Http\Requests\Events\StoreRequest;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\MatchTypesTableSeeder;
use Tests\Factories\EventRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group events
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    use ValidatesRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MatchTypesTableSeeder::class);
    }

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
    public function event_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function event_name_must_be_a_string()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function event_name_must_be_at_least_3_characters()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function event_name_must_be_unique()
    {
        Event::factory()->create(['name' => 'Example Event Name']);

        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'name' => 'Example Event Name',
            ]))
            ->assertFailsValidation(['name' => 'unique:events,name,NULL,id']);
    }

    /**
     * @test
     */
    public function event_date_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function event_date_must_be_a_string_if_provided()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => 12345,
            ]))
            ->assertFailsValidation(['date' => 'string']);
    }

    /**
     * @test
     */
    public function event_date_must_be_in_the_correct_date_format()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => 'not-a-date',
            ]))
            ->assertFailsValidation(['date' => 'date']);
    }

    /**
     * @test
     */
    public function event_venue_id_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'venue_id' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function event_venue_id_must_be_an_integer_if_provided()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'venue_id' => 'not-an-integer',
            ]))
            ->assertFailsValidation(['venue_id' => 'integer']);
    }

    /**
     * @test
     */
    public function event_venue_id_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'venue_id' => 1,
            ]))
            ->assertFailsValidation(['venue_id' => 'exists:venues,id']);
    }

    /**
     * @test
     */
    public function event_preview_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventRequestDataFactory::new()->create([
                'preview' => null,
            ]))
            ->assertPassesValidation();
    }
}
