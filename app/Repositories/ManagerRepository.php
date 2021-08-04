<?php

namespace App\Repositories;

use App\Models\Manager;

class ManagerRepository
{
    /**
     * Undocumented function
     *
     * @param  array $data
     * @return \App\Models\Manager
     */
    public function create(array $data)
    {
        return Manager::create([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move']
        ]);
    }
}
