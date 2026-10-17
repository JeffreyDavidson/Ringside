<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class LiveShowException extends BaseBusinessException
{
    public static function attendanceInvalid(): self
    {
        return new self(__('live-show.attendance_invalid'));
    }

    public static function completed(): self
    {
        return new self(__('live-show.completed'));
    }

    public static function invalidTimes(): self
    {
        return new self(__('live-show.invalid_times'));
    }

    public static function oldEvent(): self
    {
        return new self(__('live-show.old_event'));
    }

    public static function prepareRequired(): self
    {
        return new self(__('live-show.prepare_required'));
    }

    public static function reasonRequired(): self
    {
        return new self(__('live-show.reason_required'));
    }

    public static function reviewRequired(): self
    {
        return new self(__('live-show.review_required'));
    }

    public static function showDay(): self
    {
        return new self(__('live-show.show_day'));
    }

    public static function transitionInvalid(): self
    {
        return new self(__('live-show.transition_invalid'));
    }

    public static function unfinished(): self
    {
        return new self(__('live-show.unfinished'));
    }

    public static function unknownItem(): self
    {
        return new self(__('live-show.unknown_item'));
    }

    public static function unknownPerson(): self
    {
        return new self(__('live-show.unknown_person'));
    }
}
