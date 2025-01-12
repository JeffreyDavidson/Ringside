<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\TagTeams\TagTeamForm;
use App\Models\TagTeam;

class FormModal extends BaseModal
{
    protected string $modelType = TagTeam::class;

    protected string $modalLanguagePath = 'tag-teams';

    protected string $modalFormPath = 'tag-teams.modals.form-modal';

    public TagTeamForm $modelForm;
}
