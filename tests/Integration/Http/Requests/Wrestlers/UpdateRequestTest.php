<?php

namespace Tests\Integration\Http\Requests\Wrestlers;

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Employment;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group wrestlers
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase,
        ValidatesRequests;

    /**
     * @test
     */
    public function wrestler_name_is_required()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function wrestler_name_must_be_a_string()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function wrestler_name_must_be_at_least_3_characters()
    {
        $wrestler = Wrestler::factory()->make();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function wrestler_name_must_be_unique()
    {
        $wrestlerA = Wrestler::factory()->create(['name' => 'Example Wrestler Name A']);
        $wrestlerB = Wrestler::factory()->create(['name' => 'Example Wrestler Name B']);

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestlerA)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'name' => 'Example Wrestler Name B',
            ]))
            ->assertFailsValidation(['name' => 'unique:wrestlers,NULL,1,id']);
    }

    /**
     * @test
     */
    public function wrestler_height_in_feet_is_required()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'feet' => null,
            ]))
            ->assertFailsValidation(['feet' => 'required']);
    }

    /**
     * @test
     */
    public function wrestler_height_for_feet_must_be_an_integer()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'feet' => 'not-an-integer',
            ]))
            ->assertFailsValidation(['feet' => 'integer']);
    }

    /**
     * @test
     */
    public function wrestler_height_for_inches_is_required()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'inches' => null,
            ]))
            ->assertFailsValidation(['inches' => 'required']);
    }

    /**
     * @test
     */
    public function wrestler_height_for_inches_must_be_an_integer()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'inches' => 'not-an-integer',
            ]))
            ->assertFailsValidation(['inches' => 'integer']);
    }

    /**
     * @test
     */
    public function wrestler_height_for_inches_has_a_max_of_eleven()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'inches' => 12,
            ]))
            ->assertFailsValidation(['inches' => 'max:11']);
    }

    /**
     * @test
     */
    public function wrestler_weight_is_required()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'weight' => null,
            ]))
            ->assertFailsValidation(['weight' => 'required']);
    }

    /**
     * @test
     */
    public function wrestler_weight_must_be_an_integer()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'weight' => 'not-an-integer',
            ]))
            ->assertFailsValidation(['weight' => 'integer']);
    }

    /**
     * @test
     */
    public function wrestler_hometown_is_required()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'hometown' => null,
            ]))
            ->assertFailsValidation(['hometown' => 'required']);
    }

    /**
     * @test
     */
    public function wrestler_hometown_must_be_a_string()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'hometown' => 12345,
            ]))
            ->assertFailsValidation(['hometown' => 'string']);
    }

    /**
     * @test
     */
    public function wrestler_signature_move_is_optional()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'signature_move' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function wrestler_signature_move_must_be_a_string_if_provided()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'signature_move' => 12345,
            ]))
            ->assertFailsValidation(['signature_move' => 'string']);
    }

    /**
     * @test
     */
    public function wrestler_started_at_is_optional()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'started_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function wrestler_started_at_must_be_a_string_if_provided()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'started_at' => 12345,
            ]))
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function wrestler_started_at_must_be_in_the_correct_date_format()
    {
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'started_at' => 'not-a-date-format',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function wrestler_started_at_cannot_be_changed_if_employment_start_date_has_past()
    {
        $wrestler = Wrestler::factory()->has(Employment::factory()->started('2021-01-01'))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'started_at' => '2021-01-01',
            ]))
            ->assertFailsValidation(['started_at' => 'app\rules\employmentstartdatecanbechanged']);
    }

    /**
     * @test
     */
    public function wrestler_started_at_can_be_changed_if_employment_start_date_is_in_the_future()
    {
        $wrestler = Wrestler::factory()->has(Employment::factory()->started(Carbon::parse('+2 weeks')))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('wrestler', $wrestler)
            ->validate(WrestlerRequestDataFactory::new()->create([
                'started_at' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertPassesValidation();
    }
}
