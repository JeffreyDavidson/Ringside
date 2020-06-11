<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\UpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class UpdateTitleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Name Title',
            'activated_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->create();

        $response = $this->editRequest($title);

        $response->assertViewIs('titles.edit');
        $this->assertTrue($response->data('title')->is($title));
    }

    /** @test */
    public function an_administrator_can_update_a_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->create();

        $response = $this->updateRequest($title, $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Example Name Title', $title->name);
        });
    }

    /** @test */
    public function a_title_activated_at_date_can_be_nullable()
    {
        $this->markTestIncomplete();
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->create();
        TitleFactory::new()->create();

        $response = $this->updateRequest($title, $this->validParams(['activated_at' => '']));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->create();

        $response = $this->editRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->create();

        $response = $this->updateRequest($title, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_title()
    {
        $title = TitleFactory::new()->create();

        $response = $this->editRequest($title);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_title()
    {
        $title = TitleFactory::new()->create();

        $response = $this->updateRequest($title, $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            TitlesController::class,
            'update',
            UpdateRequest::class
        );
    }
}
