<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class TitlesControllerTest extends TestCase
{
    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([TitlesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeLivewire('titles.titles-list');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_titles_index_page()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([TitlesController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_titles_index_page()
    {
        $this
            ->get(action([TitlesController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function a_title_can_be_viewed()
    {
        $this->withoutExceptionHandling();
        $title = Title::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([TitlesController::class, 'show'], $title))
            ->assertViewIs('titles.show')
            ->assertViewHas('title', $title)
            ->assertSeeLivewire('titles.title-championships-list');
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([TitlesController::class, 'show'], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->get(action([TitlesController::class, 'show'], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function delete_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->delete(action([TitlesController::class, 'destroy'], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        $this->assertSoftDeleted($title);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::basic())
            ->delete(action([TitlesController::class, 'destroy'], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->delete(action([TitlesController::class, 'destroy'], $title))
            ->assertRedirect(route('login'));
    }
}
