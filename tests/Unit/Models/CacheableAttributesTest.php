<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCachedAttributes;

class CacheableAttributesTest extends TestCase
{
    /**
    * @test
    */
    public function a_cacheable_attribute_acts_as_a_normal_accessor()
    {
        $model = new TestCacheableModel();

        $this->assertEquals('calls 1', $model->test_thing);
    }

    /**
    * @test
    */
    public function a_cacheable_attribute_is_only_accessed_once()
    {
        $model = new TestCacheableModel();
        for($i = 0; $i < 5; $i++) {
            $model->test_thing;
        }

        $this->assertEquals('calls 1', $model->test_thing);
    }
}

class TestCacheableModel extends Model
{
    use HasCachedAttributes;

    public $calls = 0;

    public function getTestThingCachedAttribute()
    {
        $this->calls++;
        return "calls {$this->calls}";
    }
}