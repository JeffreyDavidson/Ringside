<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface SoftDeletable
{
    /** @return bool|null */
    public function delete(); // @pest-ignore-type

    /** @return bool */
    public function restore(); // @pest-ignore-type
}
