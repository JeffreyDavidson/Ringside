<?php

namespace App\Services;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Carbon\Carbon;

class RefereeService
{
    protected $refereeRepository;

    public function __construct(RefereeRepository $refereeRepository)
    {
        $this->refereeRepository = $refereeRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function create(array $data): Referee
    {
        $referee = $this->refereeRepository->create($data);

        if ($data['started_at']) {
            $this->employ($referee, $data['started_at']);
        }

        return $referee;
    }

    /**
     * Updates a new referee.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data): Referee
    {
        $referee->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($referee, $data['started_at']);
        }

        return $referee;
    }

    public function employOrUpdateEmployment(Referee $referee, $startedAt)
    {
        if ($referee->isUnemployed()) {
            return $this->employ($referee, $startedAt);
        }

        if ($referee->hasFutureEmployment() && $referee->futureEmployment->started_at->ne($startedAt)) {
            return $referee->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }
}
