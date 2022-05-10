<?php

namespace Tests\Integration\Http\Livewire\TagTeams;

use App\Http\Livewire\TagTeams\TagTeamsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group tagTeams
 * @group integration-tagteams
 */
class TagTeamsListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(TagTeamsList::class)
            ->assertViewIs('livewire.tagteams.tagteams-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(TagTeamsList::class)
            ->assertViewHas('tagTeams');
    }
}
