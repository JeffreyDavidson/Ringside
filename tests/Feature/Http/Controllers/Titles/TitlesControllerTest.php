<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class TitlesControllerTest extends TestCase
{
    use RefreshDatabase, AdditionalAssertions;

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
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->indexRequest('titles');

        $response->assertOk();
        $response->assertViewIs('titles.index');
        $response->assertSeeLivewire('titles.active-titles');
        $response->assertSeeLivewire('titles.future-activation-and-unactivated-titles');
        $response->assertSeeLivewire('titles.inactive-titles');
        $response->assertSeeLivewire('titles.retired-titles');
    }

    /** @test */
    public function a_basic_user_cannot_view_titles_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->indexRequest('titles')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_titles_index_page()
    {
        $this->indexRequest('title')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('title');

        $response->assertViewIs('titles.create');
        $response->assertViewHas('title', new Title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_title_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('title', $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap(Title::first(), function ($title) {
            $this->assertEquals('Example Name Title', $title->name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_activation_is_not_created_for_the_title_if_activated_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->storeRequest('title', $this->validParams(['activated_at' => null]));

        tap(Title::first(), function ($title) {
            $this->assertCount(0, $title->activations);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_activation_is_created_for_the_title_if_activated_at_is_filled_in_request($administrators)
    {
        $activatedAt = now()->toDateTimeString();

        $this->actAs($administrators);

        $this->storeRequest('titles', $this->validParams(['activated_at' => $activatedAt]));

        tap(Title::first(), function ($title) use ($activatedAt) {
            $this->assertCount(1, $title->activations);
            $this->assertEquals($activatedAt, $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_title()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('titles')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_title()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('titles', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function guests_cannot_view_the_form_for_creating_a_title()
    {
        $this->createRequest('titles')->assertRedirect(route('login'));
    }

    /** @test */
    public function guests_cannot_create_a_title()
    {
        $this->storeRequest('titles', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            TitlesController::class,
            'store',
            StoreRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->create();

        $response = $this->editRequest($title);

        $response->assertViewIs('titles.edit');
        $this->assertTrue($response->data('title')->is($title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_a_title($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->create();

        $response = $this->updateRequest($title, $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Example Name Title', $title->name);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->editRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->updateRequest($title, $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_title()
    {
        $title = Title::factory()->create();

        $this->editRequest($title)->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_title()
    {
        $title = Title::factory()->create();

        $this->updateRequest($title, $this->validParams())->assertRedirect(route('login'));
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

    /**
     * @test
     * @dataProvider administrators
     */
    public function a_title_can_be_viewed($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->create();

        $response = $this->showRequest($title);

        $response->assertViewIs('titles.show');
        $this->assertTrue($response->data('title')->is($title));
    }

    /** @test */
    public function a_basic_user_can_view_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->showRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_title()
    {
        $title = Title::factory()->create();

        $this->showRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function delete_a_title($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted($title);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->deleteRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_title()
    {
        $title = Title::factory()->create();

        $this->deleteRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function restore_a_title_a_redirects($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->softDeleted()->create();

        $response = $this->restoreRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertNull($title->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->softDeleted()->create();

        $this->restoreRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_title()
    {
        $title = Title::factory()->softDeleted()->create();

        $this->restoreRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function restore_returns_a_404_when_title_is_not_deleted($administrators)
    {
        $this->actAs($administrators);
        $title = Title::factory()->create();

        $this->restoreRequest($title)->assertNotFound();
    }
}
