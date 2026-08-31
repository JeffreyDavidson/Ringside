<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsInjurable;
use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @see IsInjurable
 */
interface Injurable
{
    /** @return MorphMany<Injury, TModel> */
    public function injuries(): MorphMany;

    /** @return MorphOne<Injury, TModel> */
    public function currentInjury(): MorphOne;
}
