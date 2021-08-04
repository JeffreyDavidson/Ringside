<?php

namespace App\Repositories;

use App\Models\Wrestler;

class WrestlerRepository
{
    public function create($data)
    {
        return Wrestler::create([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move']
        ]);
    }
}
