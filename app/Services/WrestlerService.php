<?php

namespace App\Services;

use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class WrestlerService
{
    protected $wrestlerRepository;

    public function __construct(WrestlerRepository $wrestlerRepository)
    {
        $this->wrestlerRepository = $wrestlerRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function create(array $data): Wrestler
    {
        $wrestler = $this->wrestlerRepository->create($data);

        if ($data['started_at']) {
            $this->employ($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    /**
     * Updates a new wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data): Wrestler
    {
        $wrestler->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    public function employOrUpdateEmployment(Wrestler $wrestler, $startedAt)
    {
        if ($wrestler->isUnemployed()) {
            return $this->employ($wrestler, $startedAt);
        }

        if ($wrestler->hasFutureEmployment() && $wrestler->futureEmployment->started_at->ne($startedAt)) {
            return $wrestler->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }
}
