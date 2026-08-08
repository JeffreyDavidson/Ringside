<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Wrestler $wrestler): View
    {
        return view('wrestlers.show', [
            'wrestler' => $wrestler,
        ]);
    }
}
