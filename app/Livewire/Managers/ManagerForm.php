<?php

declare(strict_types=1);

namespace App\Livewire\Referees;

use App\Livewire\Concerns\StandardForm;
use App\Models\Referee;
use Exception;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Validate;
use Livewire\Form;

class RefereeForm extends Form
{
    use StandardForm;

    public ?Referee $formModel;

    protected string $formModelType = Referee::class;

    #[Validate(as: 'referees.first_name')]
    public $firstName = '';

    #[Validate(as: 'referees.last_name')]
    public $lastName = '';

    #[Validate(as: 'employments.start_date')]
    public $start_date = '';

    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
            'start_date' => ['nullable', 'string', 'date'],
        ];
    }

    public function save()
    {
        $this->validate();

        $validated = $this->validate();

        try {
            if (isset($this->formModel)) {
                Gate::authorize('update', $this->formModel);
                $this->formModel->fill($validated);
            } else {
                Gate::authorize('create', Referee::class);
                $this->formModel = new Referee;
                $this->formModel->fill($validated);
            }

            return $this->formModel->save();
        } catch (Exception $exception) {
            dd($exception->getMessage());
        }

        return false;
    }
}
