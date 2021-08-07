<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;
use App\Repositories\WrestlerRepository;

class WrestlerUnretireStrategy extends BaseUnretireStrategy
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Unretirable
     */
    private Unretirable $unretirable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    private WrestlerRepository $wrestlerRepository;

    /**
     * Create a new wrestler unretire strategy instance.
     *
     * @param \App\Models\Contracts\Unretirable $unretirable
     */
    public function __construct(Unretirable $unretirable)
    {
        $this->unretirable = $unretirable;
        $this->wrestlerRepository = new WrestlerRepository;
    }

    /**
     * Unretire an unretirable model.
     *
     * @param  string|null $unretiredDate
     * @return void
     */
    public function unretire(string $unretiredDate = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredDate ?: now()->toDateTimeString();

        $this->wrestlerRepository->unretire($this->unretirable, $unretiredDate);
        $this->wrestlerRepository->employ($this->unretirable, $unretiredDate);
        $this->unretirable->updateStatusAndSave();

        if ($this->unretirable->currentTagTeam) {
            $this->unretirable->currentTagTeam->updateStatusAndSave();
        }
    }
}
