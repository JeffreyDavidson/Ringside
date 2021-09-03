<?php

namespace Tests\Unit\Models;

use App\Enums\TitleStatus;
use App\Models\Title;
use Tests\TestCase;

/**
 * @group titles
 * @group models
 */
class TitleTest extends TestCase
{
    /**
     * @test
     */
    public function a_title_status_gets_cast_as_a_title_status_enum()
    {
        $title = Title::factory()->make();

        $this->assertInstanceOf(TitleStatus::class, $title->status);
    }

    /**
     * @test
     */
    public function a_title_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Title::class);
    }

    /**
     * @test
     */
    public function a_title_uses_can_be_activated_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Activatable', Title::class);
    }

    /**
     * @test
     */
    public function a_title_uses_can_be_competable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Competable', Title::class);
    }

    /**
     * @test
     */
    public function a_title_uses_can_be_retired_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Retirable', Title::class);
    }
}
