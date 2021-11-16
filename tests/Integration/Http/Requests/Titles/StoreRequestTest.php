<?php

namespace Tests\Integration\Http\Requests\Titles;

use App\Http\Requests\Titles\StoreRequest;
use App\Models\Title;
use App\Models\User;
use Tests\Factories\TitleRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group titles
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
    public function title_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        Title::factory()->create(['name' => 'Example Title']);

        $this->createRequest(StoreRequest::class)
            ->validate(TitleRequestDataFactory::new()->create([
                'name' => 'Example Title',
            ]))
            ->assertFailsValidation(['name' => 'unique:titles,name,NULL,id']);
    }

    /**
     * @test
     */
    public function title_activated_at_is_optional()
    {
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
            ->validate(TitleRequestDataFactory::new()->create([
                'activated_at' => 'not-a-date',
            ]))
            ->assertFailsValidation(['activated_at' => 'date']);
    }
}
