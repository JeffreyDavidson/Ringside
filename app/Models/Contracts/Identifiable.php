<?php

namespace App\Models\Contracts;

interface Identifiable
{
    public function getIdentifier(): string;
}
