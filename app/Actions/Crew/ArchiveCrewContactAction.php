<?php

declare(strict_types=1);

namespace App\Actions\Crew;

use App\Exceptions\RecordChangedException;
use App\Models\Crew\CrewContact;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class ArchiveCrewContactAction
{
    public function handle(CrewContact $contact, int $version, User $actor): void
    {
        DB::transaction(function () use ($contact, $version, $actor): void {
            $contact->refreshForUpdate();

            if ($contact->version !== $version || $contact->archived_at !== null) {
                throw RecordChangedException::changed();
            }

            $contact->increment('version', extra: ['archived_at' => now()]);

            activity('production')
                ->causedBy($actor)
                ->withProperties([
                    'contact_id' => $contact->id,
                    'version' => $contact->version,
                ])
                ->log('contact-archive');
        }, attempts: 3);
    }
}
