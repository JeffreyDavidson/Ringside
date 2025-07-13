<?php

declare(strict_types=1);

namespace App\Data\Titles;

use App\Enums\Titles\TitleType;
use Illuminate\Support\Carbon;

readonly class TitleData
{
    public function __construct(
        public string $name,
        public TitleType $type,
        public ?Carbon $debut_date
    ) {}
}
