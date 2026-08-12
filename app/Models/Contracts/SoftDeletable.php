<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface SoftDeletable
{
    /** @return bool|null */
    public function delete();

    /** @return bool */
    public function restore();
}
