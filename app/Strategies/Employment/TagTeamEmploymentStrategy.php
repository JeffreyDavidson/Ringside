<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;
use App\Repositories\TagTeamRepository;

class TagTeamEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Employable
     */
    private Employable $employable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TagTeamRepository
     */
    private TagTeamRepository $tagTeamRepository;

    /**
     * Create a new tag team employment strategy instance.
     *
     * @param \App\Models\Contracts\Employable $employable
     */
    public function __construct(Employable $employable)
    {
        $this->employable = $employable;
        $this->tagTeamRepository = new TagTeamRepository;
    }

    /**
     * Employ an employable model.
     *
     * @param  string|null $employmentDate
     * @return void
     */
    public function employ(string $employmentDate = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate = $employmentDate ?? now()->toDateTimeString();

        $this->tagTeamRepository->employ($this->employable, $employmentDate);
        $this->employable->updateStatusAndSave();
    }
}
