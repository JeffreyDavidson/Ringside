<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\LiveAttendanceMark;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\Event;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Facades\DB;

class MarkLiveAttendanceAction
{
    public function __construct(private readonly LockLiveShowAction $lock, private readonly PersistLiveShowAction $persist, private readonly LiveShowTimingService $timing) {}

    public function handle(Event $event, int $version, string $key, LiveAttendanceMark $mark, bool $value): void
    {
        DB::transaction(function () use ($event, $version, $key, $mark, $value): void {
            $run = $this->lock->handle($event, $version);
            $this->timing->ensureShowDay($run);
            $content = $run->content;
            $index = array_search($key, array_column($content['roster'], 'key'), true);
            if (! is_int($index)) {
                throw LiveShowException::because('unknown_person');
            }
            $before = $content['roster'][$index];
            $person = $before;
            if (($before[$mark->value] !== null) === $value || ($mark === LiveAttendanceMark::Briefed && $value && $before['arrived'] === null)) {
                throw LiveShowException::because('attendance_invalid');
            }
            $person[$mark->value] = $value ? now()->utc()->toIso8601String() : null;
            if ($mark === LiveAttendanceMark::Arrived && ! $value) {
                $person['briefed'] = null;
            }
            $person['updated_by'] = (int) auth()->id();
            $content['roster'][$index] = $person;
            $run->content = $content;
            $this->persist->handle($run, 'attendance', $key, $before, $content['roster'][$index]);
        }, attempts: 3);
    }
}
