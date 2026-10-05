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
        $this->ensureNoPivotRowIsRepeated('events_matches_referees', 'referee_id');
        $this->ensureNoPivotRowIsRepeated('events_matches_titles', 'title_id');

        Schema::table('events_matches_referees', function (Blueprint $table): void {
            $table->unique(['match_id', 'referee_id'], 'events_matches_referees_match_referee_unique');
        });

        Schema::table('events_matches_titles', function (Blueprint $table): void {
            $table->unique(['match_id', 'title_id'], 'events_matches_titles_match_title_unique');
        });
    }

    /**
     * Abort before any schema change when a match already lists the same row twice.
     *
     * Existing rows are never removed: the operator decides which duplicates to delete.
     */
    private function ensureNoPivotRowIsRepeated(string $table, string $column): void
    {
        $conflicts = DB::table($table)
            ->orderBy('id')
            ->get(['id', 'match_id', $column])
            ->groupBy(fn (object $row): string => "{$row->match_id}:{$row->{$column}}")
            ->filter(fn ($rows): bool => $rows->count() > 1)
            ->map(fn ($rows): string => sprintf(
                '%s: match %d lists %s %d in row ids %s',
                $table,
                $rows->first()->match_id,
                $column,
                $rows->first()->{$column},
                $rows->pluck('id')->implode(', '),
            ))
            ->implode('; ');

        if ($conflicts === '') {
            return;
        }

        throw new RuntimeException(
            "Cannot enforce unique {$column} per match: {$conflicts}. "
            .'No changes were made. Delete the duplicate rows so each is listed once per match, '
            .'then re-run "php artisan migrate".'
        );
    }
};
