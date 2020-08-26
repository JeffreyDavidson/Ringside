<?php

namespace Tests\Factories;

use App\Enums\TitleStatus;
use App\Models\Title;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class TitleFactory extends BaseFactory
{
    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Title::class;

    public function create(array $extra = []): Title
    {
        $title = parent::build($extra);

        $title->save();

        if ($this->softDeleted) {
            $title->delete();
        }

        return $title;
    }

    public function make(array $extra = []): Title
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => Str::title($faker->unique()->words(2, true)). ' Title',
            'status' => TitleStatus::__default,
        ];
    }

    public function active(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::ACTIVE,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::yesterday()), 'activations', 1);

        return $clone;
    }

    public function inactive(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::INACTIVE,
        ]);

        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::yesterday())->ended($end), 'activations', 1);

        return $clone;
    }

    public function withFutureActivation(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::FUTURE_ACTIVATION,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::tomorrow()), 'activations', 1);

        return $clone;
    }

    public function retired(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(ActivationFactory::new()->started($start)->ended($end), 'activations', 1);
        $clone = $clone->withFactory(RetirementFactory::new()->started($end), 'retirements', 1);

        return $clone;
    }

    public function unactivated(): TitleFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::UNACTIVATED,
        ]);
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
