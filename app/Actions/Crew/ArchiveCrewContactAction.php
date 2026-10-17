<?php

declare(strict_types=1);

namespace App\Actions\Crew;

use App\Exceptions\RecordChangedException;
use App\Models\Crew\CrewContact;
use Illuminate\Support\Facades\DB;

class ArchiveCrewContactAction
{
    public function handle(CrewContact $contact, int $version): void
    {
        DB::transaction(function () use ($contact, $version): void {
            $contact = CrewContact::query()->whereKey($contact->id)->lockForUpdate()->firstOrFail();
            if ($contact->version !== $version || $contact->archived_at !== null) {
                throw RecordChangedException::changed();
            }
            $contact->archived_at = now();
            $contact->version++;
            $contact->save();
            activity('production')->withProperties(['contact_id' => $contact->id, 'version' => $contact->version])->log('contact-archive');
        }, attempts: 3);
    }
}
