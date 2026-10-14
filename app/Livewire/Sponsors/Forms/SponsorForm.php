<?php

declare(strict_types=1);

namespace App\Livewire\Sponsors\Forms;

use App\Data\Sponsors\SponsorData;
use App\Models\Sponsors\Sponsor;
use Livewire\Form;

class SponsorForm extends Form
{
    public string $name = '';

    public string $contactName = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public bool $archived = false;

    public function load(Sponsor $sponsor): void
    {
        $this->name = $sponsor->name;
        $this->contactName = $sponsor->contact_name ?? '';
        $this->email = $sponsor->contact_email ?? '';
        $this->phone = $sponsor->contact_phone ?? '';
        $this->notes = $sponsor->notes ?? '';
        $this->archived = $sponsor->archived_at !== null;
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'contactName' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:80'], 'notes' => ['nullable', 'string', 'max:5000'], 'archived' => ['boolean']];
    }

    public function toData(): SponsorData
    {
        $this->validate();

        return new SponsorData($this->name, $this->contactName ?: null, $this->email ?: null, $this->phone ?: null, $this->notes ?: null, $this->archived);
    }
}
