<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Titles\TitleForm;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    protected string $modelType = Title::class;

    protected string $modalLanguagePath = 'titles';

    protected string $modalFormPath = 'titles.modals.form-modal';

    public TitleForm $modelForm;

    public function fillDummyFields()
    {
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->name = Str::of(fake()->words(2, true))->title()->append(' Title')->value();
        $this->modelForm->start_date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
    }
}
