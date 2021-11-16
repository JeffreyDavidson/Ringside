<?php

namespace Tests\Integration\Http\Requests\Titles;

use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Activation;
use App\Models\Title;
use Carbon\Carbon;
use Tests\Factories\TitleRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group titles
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function title_name_is_required()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function title_name_must_be_a_string()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function title_name_must_be_at_least_3_characters()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function title_name_must_end_with_title_or_titles()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => 'Example',
            ]))
            ->assertFailsValidation(['name' => 'endswith:Title,Titles']);
    }

    /**
     * @test
     */
    public function title_name_must_be_unique()
    {
        $titleA = Title::factory()->create();
        $titleB = Title::factory()->create(['name' => 'Example Title']);

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $titleA)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => 'Example Title',
            ]))
            ->assertFailsValidation(['name' => 'unique:titles,NULL,1,id']);
    }

    /**
     * @test
     */
    public function title_activated_at_is_optional()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function title_activated_at_must_be_a_string_if_provided()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => 12345,
            ]))
            ->assertFailsValidation(['activated_at' => 'string']);
    }

    /**
     * @test
     */
    public function title_activated_at_must_be_in_the_correct_date_format()
    {
        $title = Title::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => 'not-a-date-format',
            ]))
            ->assertFailsValidation(['activated_at' => 'date']);
    }

    /**
     * @test
     */
    public function title_activated_at_cannot_be_changed_if_employment_start_date_has_past()
    {
        $title = Title::factory()->has(Activation::factory()->started('2021-01-01'))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => '2021-01-01',
            ]))
            ->assertFailsValidation(['activated_at' => 'app\rules\activationstartdatecanbechanged']);
    }

    /**
     * @test
     */
    public function title_activated_at_can_be_changed_if_employment_start_date_is_in_the_future()
    {
        $title = Title::factory()->has(Activation::factory()->started(Carbon::parse('+2 weeks')))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('title', $title)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertPassesValidation();
    }
}
