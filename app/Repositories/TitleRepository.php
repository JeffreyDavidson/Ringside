<?php

namespace App\Repositories;

use App\Models\Title;

class TitleRepository
{
    public function create($data)
    {
        return Title::create(request()->validatedExcept('activated_at'));
    }
}
