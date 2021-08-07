<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;
use App\Repositories\TitleRepository;

class TitleUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
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
     * @var \App\Repositories\TitleRepository
     */
    private TitleRepository $titleRepository;

    /**
     * Create a new title unretire strategy instance.
     *
     * @param \App\Models\Contracts\Unretirable $unretirable
     */
    public function __construct(Unretirable $unretirable)
    {
        $this->unretirable = $unretirable;
        $this->titleRepository = new TitleRepository;
    }

    /**
     * Unretire an unretirable model.
     *
     * @param  string|null $unretireDate
     * @return void
     */
    public function unretire(string $unretireDate = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $unretireDate = $unretireDate ?: now();

        $this->titleRepository->unretire($this->unretirable, $unretireDate);
        $this->titleRepository->activate($this->unretirable, $unretireDate);
        $this->unretirable->updateStatusAndSave();
    }
}
