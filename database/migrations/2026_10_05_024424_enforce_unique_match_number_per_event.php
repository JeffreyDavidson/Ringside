<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureNoMatchNumberIsRepeated();

        // Soft-deleted matches are included on purpose: match numbers are never reused within an event.
        Schema::table('events_matches', function (Blueprint $table): void {
            $table->unique(['event_id', 'match_number'], 'events_matches_event_id_match_number_unique');
        });
    }

    /**
     * Abort before any schema change when an event already repeats a match number.
     *
     * Existing matches are never renumbered: the operator decides which numbers to change.
     */
    private function ensureNoMatchNumberIsRepeated(): void
    {
        $conflicts = DB::table('events_matches')
            ->orderBy('id')
            ->get(['id', 'event_id', 'match_number'])
            ->groupBy(fn (object $match): string => "{$match->event_id}:{$match->match_number}")
            ->filter(fn ($matches): bool => $matches->count() > 1)
            ->map(fn ($matches): string => sprintf(
                'match number %d of event %d is used by match ids %s',
                $matches->first()->match_number,
                $matches->first()->event_id,
                $matches->pluck('id')->implode(', '),
            ))
            ->implode('; ');

        if ($conflicts === '') {
            return;
        }

        throw new RuntimeException(
            "Cannot enforce unique match numbers per event: {$conflicts}. "
            .'No changes were made. Renumber the repeated matches (deleted matches count too), '
            .'then re-run "php artisan migrate".'
        );
    }
};
