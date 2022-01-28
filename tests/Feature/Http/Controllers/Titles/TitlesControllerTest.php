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
            ->assertSeeLivewire('titles.active-titles')
            ->assertSeeLivewire('titles.future-activation-and-unactivated-titles')
            ->assertSeeLivewire('titles.inactive-titles')
            ->assertSeeLivewire('titles.retired-titles');
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
            ->assertViewHas('title', $title);
    }

    /**
     * @test
     */
    public function a_history_list_for_a_title_can_be_viewed_for_title_show_page()
    {
        $title = Title::factory()
            ->has(
                TitleChampionship::factory()
                    ->wonOn('2021-03-01')
                    ->lostOn('2021-06-01')
                    ->for(Wrestler::factory()->state(['name' => 'Example Wrestler 3']), 'champion'),
                'championships'
            )
            ->has(
                TitleChampionship::factory()
                    ->wonOn('2021-06-01')
                    ->lostOn(null)
                    ->for(Wrestler::factory()->state(['name' => 'Example Wrestler 1']), 'champion'),
                'championships'
            )
            ->has(
                TitleChampionship::factory()
                    ->wonOn('2021-01-01')
                    ->lostOn('2021-03-01')
                    ->for(Wrestler::factory()->state(['name' => 'Example Wrestler 2']), 'champion'),
                'championships'
            )
            ->create();

        $response = $this
            ->actAs(Role::administrator())
            ->get(action([TitlesController::class, 'show'], $title));

        $response->assertSeeInOrder([
            'Example Wrestler 2', 'Example Wrestler 3', 'Example Wrestler 1',
        ]);
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
