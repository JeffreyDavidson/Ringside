<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Injury;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Injurable extends Identifiable
{
    /**
     * @return HasMany<Injury, $this>
     */
    public function injuries(): HasMany;

    /**
     * @return HashOne<Injury, $this>
     */
    public function currentInjury(): HasOne;

    /**
     * @return HasMany<Injury, $this>
     */
    public function previousInjuries(): HasMany;

    /**
     * @return HasOne<Injury>
     */
    public function previousInjury(): HasOne;

    public function isInjured(): bool;

    public function hasInjuries(): bool;
}
