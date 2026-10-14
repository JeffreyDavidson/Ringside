<?php

declare(strict_types=1);

namespace App\Actions\Crew;

use App\Exceptions\Crew\CrewConflictException;
use App\Models\Crew\CrewContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArchiveCrewContactAction
{
    public function handle(CrewContact $contact, int $version): void
    {
        DB::transaction(function () use ($contact, $version): void {
            $contact = CrewContact::query()->whereKey($contact->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('archive', $contact);
            if ($contact->version !== $version || $contact->archived_at !== null) {
                throw CrewConflictException::stale();
            }
            $contact->archived_at = now();
            $contact->version++;
            $contact->save();
            activity('production')->withProperties(['contact_id' => $contact->id, 'version' => $contact->version])->log('contact-archive');
        }, attempts: 3);
    }
}
