<?php

namespace Tests\Integration\Http\Requests\EventMatches;

use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use Carbon\Carbon;
use Tests\Factories\EventRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group events
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function event_name_is_required()
    {
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create(['name' => 'Example Event']);

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $eventA)
            ->validate(EventRequestDataFactory::new()->create([
                'name' => 'Example Event',
            ]))
            ->assertFailsValidation(['name' => 'unique:events,NULL,1,id']);
    }

    /**
     * @test
     */
    public function event_date_is_optional()
    {
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => 'not-a-date-format',
            ]))
            ->assertFailsValidation(['date' => 'date']);
    }

    /**
     * @test
     */
    public function event_date_cannot_be_changed_if_event_date_has_past()
    {
        $event = Event::factory()->scheduledOn('2021-01-01')->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => '2021-02-01',
            ]))
            ->assertFailsValidation(['date' => 'app\rules\eventdatecanbechanged']);
    }

    /**
     * @test
     */
    public function event_date_can_be_changed_if_activation_start_date_is_in_the_future()
    {
        $event = Event::factory()->scheduledOn(Carbon::parse('+2 weeks'))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'date' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function event_venue_id_is_optional()
    {
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
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
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'venue_id' => 1,
            ]))
            ->assertFailsValidation(['venue_id' => 'exists']);
    }

    /**
     * @test
     */
    public function event_preview_is_optional()
    {
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'preview' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function event_preview_must_be_a_string_if_provided()
    {
        $event = Event::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('event', $event)
            ->validate(EventRequestDataFactory::new()->create([
                'preview' => 1234,
            ]))
            ->assertFailsValidation(['preview' => 'string']);
    }
}
