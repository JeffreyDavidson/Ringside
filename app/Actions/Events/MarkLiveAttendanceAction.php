<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Enums\Events\LiveAttendanceMark;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Models\Events\Event;
use Illuminate\Support\Facades\DB;

class MarkLiveAttendanceAction
{
    public function __construct(private readonly LockLiveShowAction $lock, private readonly PersistLiveShowAction $persist, private readonly LiveShowDayRequirements $showDay) {}

    public function handle(Event $event, int $version, string $key, LiveAttendanceMark $mark, bool $value): void
    {
        DB::transaction(function () use ($event, $version, $key, $mark, $value): void {
            $run = $this->lock->handle($event, $version);
            $this->showDay->ensureShowDay($run);
            $content = $run->content;
            $index = array_search($key, array_column($content['roster'], 'key'), true);
            if (! is_int($index)) {
                throw LiveShowException::unknownPerson();
            }
            $before = $content['roster'][$index];
            $person = $before;
            if (($before[$mark->value] !== null) === $value || ($mark === LiveAttendanceMark::Briefed && $value && $before['arrived'] === null)) {
                throw LiveShowException::attendanceInvalid();
            }
            $person[$mark->value] = $value ? now()->utc()->toIso8601String() : null;
            if ($mark === LiveAttendanceMark::Arrived && ! $value) {
                $person['briefed'] = null;
            }
            $person['updated_by'] = (int) auth()->id();
            $content['roster'][$index] = $person;
            $run->content = $content;
            $auditBefore = $before;
            $auditAfter = $person;
            if (str_starts_with($key, 'crew:')) {
                $auditBefore = array_intersect_key($before, array_flip(['arrived', 'briefed', 'updated_by']));
                $auditAfter = array_intersect_key($person, array_flip(['arrived', 'briefed', 'updated_by']));
            }
            $this->persist->handle($run, new LiveShowChangeData('attendance', $key, $auditBefore, $auditAfter));
        }, attempts: 3);
    }
}
