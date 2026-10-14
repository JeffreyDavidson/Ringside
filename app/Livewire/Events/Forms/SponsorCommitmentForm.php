<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Data\Events\SponsorCommitmentData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Models\Events\EventSponsorCommitment;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;

class SponsorCommitmentForm extends Form
{
    #[Locked]
    public ?string $originalDeadline = null;

    #[Locked]
    public ?string $originalCompletion = null;

    public int $sponsorId = 0;

    public string $type = 'banner';

    public string $description = '';

    public string $owner = '';

    public string $deadline = '';

    public string $deadlineOffset = '';

    public string $timezone = 'UTC';

    public string $notes = '';

    public string $agreement = '';

    public string $status = 'pending';

    public string $evidence = '';

    public string $evidenceUrl = '';

    public string $completedAt = '';

    public string $completedOffset = '';

    public string $instructionLabel = '';

    public string $instruction = '';

    public string $instructionOwner = '';

    public string $reason = '';

    public bool $reviewed = false;

    public bool $refreshSponsorName = false;

    public function load(EventSponsorCommitment $commitment): void
    {
        $this->sponsorId = $commitment->sponsor_id;
        $this->originalDeadline = $commitment->deadline_at?->toIso8601String();
        $this->originalCompletion = $commitment->completed_at?->toIso8601String();
        $this->type = $commitment->type->value;
        $this->description = $commitment->description;
        $this->owner = $commitment->responsible ?? '';
        $this->timezone = $commitment->timezone;
        $deadline = $commitment->deadline_at?->copy()->setTimezone($this->timezone);
        $completed = $commitment->completed_at?->copy()->setTimezone($this->timezone);
        $this->deadline = $deadline?->format('Y-m-d\TH:i') ?? '';
        $this->deadlineOffset = $deadline?->format('P') ?? '';
        $this->completedAt = $completed?->format('Y-m-d\TH:i') ?? '';
        $this->completedOffset = $completed?->format('P') ?? '';
        $this->notes = $commitment->notes ?? '';
        $this->agreement = $commitment->agreement_reference ?? '';
        $this->status = $commitment->status->value;
        $this->evidence = $commitment->evidence_note ?? '';
        $this->evidenceUrl = $commitment->evidence_url ?? '';
        $this->instructionLabel = $commitment->instruction_label ?? '';
        $this->instruction = $commitment->instruction ?? '';
        $this->instructionOwner = $commitment->instruction_owner ?? '';
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return ['sponsorId' => ['required', 'integer', 'min:1'], 'type' => ['required', Rule::enum(SponsorCommitmentType::class)],
            'description' => ['required', 'string', 'max:500'], 'owner' => ['nullable', 'string', 'max:160'],
            'deadline' => ['nullable', 'date_format:Y-m-d\TH:i'], 'deadlineOffset' => ['nullable', 'regex:/^[+-]\d{2}:\d{2}$/'],
            'timezone' => ['required', 'timezone:all'], 'notes' => ['nullable', 'string', 'max:5000'],
            'agreement' => ['nullable', 'string', 'max:160'], 'status' => ['required', Rule::enum(SponsorCommitmentStatus::class)],
            'evidence' => ['required_if:status,completed', 'nullable', 'string', 'max:2000'],
            'evidenceUrl' => ['nullable', 'url:http,https', 'max:2000'], 'completedAt' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'completedOffset' => ['nullable', 'regex:/^[+-]\d{2}:\d{2}$/'], 'reason' => ['nullable', 'string', 'max:2000'],
            'instructionLabel' => ['nullable', 'string', 'max:160'], 'instruction' => ['nullable', 'string', 'max:2000'],
            'instructionOwner' => ['nullable', 'string', 'max:160'], 'reviewed' => ['boolean'], 'refreshSponsorName' => ['boolean']];
    }

    public function toData(): SponsorCommitmentData
    {
        $this->validate();

        return new SponsorCommitmentData($this->sponsorId, SponsorCommitmentType::from($this->type), $this->description,
            $this->owner ?: null, $this->instant('deadline', $this->deadline, $this->deadlineOffset, $this->originalDeadline), $this->timezone,
            $this->notes ?: null, $this->agreement ?: null, SponsorCommitmentStatus::from($this->status),
            $this->evidence ?: null, $this->evidenceUrl ?: null, $this->instant('completedAt', $this->completedAt, $this->completedOffset, $this->originalCompletion),
            $this->instructionLabel ?: null, $this->instruction ?: null, $this->instructionOwner ?: null);
    }

    private function instant(string $field, string $value, string $offset, ?string $original): ?Carbon
    {
        if ($value === '') {
            return null;
        }
        if ($original !== null) {
            $recorded = Carbon::parse($original)->setTimezone($this->timezone);
            if ($recorded->format('Y-m-d\TH:i') === $value && $recorded->format('P') === $offset) {
                return $recorded->utc();
            }
        }
        $instant = app(LiveShowTimingService::class)->wallTime($value, $this->timezone, $offset ?: null);
        if ($instant === null) {
            throw ValidationException::withMessages(['form.'.$field => __('sponsors.invalid_time')]);
        }

        return $instant;
    }
}
