<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Models\Wrestler;
use App\ValueObjects\Height;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class WrestlerForm extends Form
{
    public ?Wrestler $wrestler;

    #[Validate('required|min:5')]
    public string $name = '';

    public string $hometown = '';

    public ?string $signature_move = '';

    public Carbon|string|null $start_date = '';

    public int $height_feet;

    public int $height_inches;

    public int $weight;

    public function setWrestler(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;

        $this->name = $wrestler->name;

        $this->hometown = $wrestler->hometown;
        $this->signature_move = $wrestler->signature_move;
        $this->start_date = $wrestler->currentEmployment?->started_at;
        $this->weight = $wrestler->weight;
        $height = $wrestler->height;

        $feet = (int) floor($height->toInches() / 12);
        $inches = $height->toInches() % 12;

        $this->height_feet = $feet;
        $this->height_inches = $inches;
    }

    public function update(): bool
    {
        $this->validate();
        $height = new Height($this->height_feet, $this->height_inches);
        if (! isset($this->wrestler)) {
            $this->wrestler = new Wrestler([
                'name' => $this->name,
                'hometown' => $this->hometown,
                'signature_move' => $this->signature_move,
                'start_date' => $this->start_date,
                'height' => $height->toInches(),
                'weight' => $this->weight,
            ]);
            $this->wrestler->save();
        } else {
            $this->wrestler->update([
                'name' => $this->name,
                'hometown' => $this->hometown,
                'signature_move' => $this->signature_move,
                'start_date' => $this->start_date,
                'height' => $height->toInches(),
                'weight' => $this->weight,
            ]);
        }

        return true;
    }
}
