<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;

class WrestlerEditsController
{
    public function edit(Wrestler $wrestler): View
    {
        return view('wrestlers.form', [
            'wrestler' => $wrestler,
        ]);
    }
}
