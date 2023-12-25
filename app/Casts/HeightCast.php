<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Height;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class HeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        $feet = (int) floor($value / 12);
        $inches = $value % 12;

        return new Height($feet, $inches);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }
}
