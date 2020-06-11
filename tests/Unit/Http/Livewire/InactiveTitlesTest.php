<?php

namespace Tests\Unit\Http\Livewire;

use Mockery;
use Tests\TestCase;
use App\Models\Title;
use App\Http\Livewire\Titles\InactiveTitles;

class InactiveTitlesTest extends TestCase
{
    /** @test */
    public function testing_mockery()
    {
        $component = Mockery::mock(InactiveTitles::class);

        $component->shouldReceive('inactive')
                ->shouldReceive('paginate');
    }
}
