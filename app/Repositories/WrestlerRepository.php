<?php

namespace App\Repositories;

use App\Models\Wrestler;

class WrestlerRepository
{
    /**
     * Create a new wrestler with the given data.
     *
     * @param  array $data
     * @return \App\Models\Wrestler
     */
    public function create($data)
    {
        return Wrestler::create([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data)
    {
        return $wrestler->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);
    }

    /**
     * Clear the current injury a given wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $recoveryDate
     * @return void
     */
    public function clearInjury(Wrestler $wrestler, string $recoveryDate)
    {
        $wrestler->currentInjury()->update(['ended_at' => $recoveryDate]);
    }
}
