<?php

namespace Tests\Unit\Models;

use App\Enums\TitleStatus;
use App\Models\Title;
use Tests\TestCase;

class TitleTest extends TestCase
{
    /** @test */
    public function a_title_has_a_name()
    {
        $wrestler = new Title(['name' => 'Example Title Name']);

        $this->assertEquals('Example Title Name', $wrestler->name);
    }

    /** @test */
    public function a_title_has_a_status()
    {
        $title = new Title();
        $title->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $title->getRawOriginal('status'));
    }

    /** @test */
    public function a_title_status_gets_cast_as_a_title_status_enum()
    {
        $title = new Title();

        $this->assertInstanceOf(TitleStatus::class, $title->status);
    }

    /** @test */
    public function a_title_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Title::class);
    }

    /** @test */
    public function a_title_uses_can_be_activated_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeActivated', Title::class);
    }

    /** @test */
    public function a_title_uses_can_be_competed_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeCompeted', Title::class);
    }

    /** @test */
    public function a_title_uses_can_be_retired_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeRetired', Title::class);
    }

    /** @test */
    public function a_title_uses_has_cached_attributes_trait()
    {
        $this->assertUsesTrait('App\Traits\HasCachedAttributes', Title::class);
    }
}
