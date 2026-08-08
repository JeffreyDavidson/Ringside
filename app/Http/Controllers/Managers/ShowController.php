<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Models\Managers\Manager;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Manager $manager): View
    {
        return view('managers.show', [
            'manager' => $manager,
        ]);
    }
}
