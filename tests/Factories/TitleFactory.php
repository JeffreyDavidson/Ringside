<?php

namespace Tests\Factories;

use App\Enums\TitleStatus;
use App\Models\Title;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class TitleFactory extends BaseFactory
{
    /** @var ActivationFactory|null */
    public $activationFactory;

    /** @var RetirementFactory|null */
    public $retirementFactory;

    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Title::class;

    public function create(array $extra = []): Title
    {
        $title = parent::build($extra);

        if ($this->activationFactory) {
            $this->activationFactory->forTitle($title)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forTitle($title)->create();
        }

        $title->save();

        if ($this->softDeleted) {
            $title->delete();
        }

        return $title;
    }

    public function make(array $extra = []): Title
    {
        dd(parent::build($extra, 'make'));
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => Str::title($faker->unique()->words(2, true)). ' Title',
            'status' => TitleStatus::__default,
        ];
    }

    public function active(ActivationFactory $activationFactory = null): TitleFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::ACTIVE,
        ]);

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started(now());

        $clone->retirementFactory = null;

        return $clone;
    }

    public function inactive(ActivationFactory $activationFactory = null): TitleFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::INACTIVE,
        ]);

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started(now()->subDays(4))->ended(now()->subDays(1));

        $clone->retirementFactory = null;

        return $clone;
    }

    public function futureActivation(ActivationFactory $activationFactory = null): TitleFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::FUTURE_ACTIVATION,
        ]);

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started(now()->addDays(4));

        return $clone;
    }

    public function retired(ActivationFactory $activationFactory = null, RetirementFactory $retirementFactory = null): TitleFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TitleStatus::RETIRED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started($start)->ended($end);

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started($end);

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
