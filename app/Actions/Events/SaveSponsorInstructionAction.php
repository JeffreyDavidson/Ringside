<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventPackData;
use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\SponsorInstructionMode;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Lifecycle\Events\EventCardLock;
use App\Lifecycle\Events\SponsorInstructionRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Users\User;
use App\Services\Events\EventPackCardService;
use App\Services\Events\EventPackSourceService;
use App\Services\Events\SponsorInstructionService;
use App\ValueObjects\PackKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveSponsorInstructionAction
{
    public function __construct(
        private readonly SponsorInstructionService $instructions,
        private readonly EventPackCardService $card,
        private readonly EventPackSourceService $source,
        private readonly SaveEventPackAction $save,
        private readonly PersistSponsorCommitmentAction $persist,
        private readonly EventCardLock $lock,
        private readonly SponsorInstructionRequirements $requirements,
    ) {}

    public function handle(Event $event, int $id, int $version, int $packVersion, string $fingerprint, SponsorInstructionData $data, User $actor): void
    {
        DB::transaction(function () use ($event, $id, $version, $packVersion, $fingerprint, $data, $actor): void {
            ['event' => $event, 'matches' => $matches, 'pack' => $pack, 'run' => $run] = $this->lock->lock($event->id);
            $commitment = EventSponsorCommitment::query()
                ->forEventId($event->id)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $card = $this->card->get($event);
            $preview = $this->instructions->preview($event, $commitment, $data->mode, $data->targetKey?->__toString(), $data->afterMatchId);
            $this->requirements->ensureCanSave($commitment, $pack, $matches, $run, $preview, $card['fingerprint'], $version, $packVersion, $fingerprint, $data);
            $content = $pack->content ?? $this->source->get($event)['content'];

            if ($data->mode === SponsorInstructionMode::Remove) {
                $packKey = PackKey::fromString($commitment->pack_key ?? throw SponsorCommitmentException::reasonRequired());
                $uuid = $packKey->uuid;

                if ($packKey->kind === SponsorInstructionMode::Segment) {
                    $content['segments'] = array_values(array_filter($content['segments'], fn (array $entry): bool => $entry['id'] !== $uuid));
                } else {
                    $content['sponsor_tasks'] = array_values(array_filter($content['sponsor_tasks'] ?? [], fn (array $entry): bool => $entry['id'] !== $uuid));
                }
                $commitment->pack_key = null;
                $commitment->pack_instruction_hash = null;
            } else {
                $packKey = $data->targetKey ?? new PackKey($data->mode, (string) Str::uuid());
                $key = (string) $packKey;

                if (EventSponsorCommitment::query()->forEventId($event->id)->where('pack_key', $key)->whereKeyNot($commitment->id)->exists()) {
                    throw SponsorCommitmentException::usedTarget();
                }
                $uuid = $packKey->uuid;
                $wording = $preview['wording'];

                if ($data->mode === SponsorInstructionMode::Segment) {
                    $entry = ['id' => $uuid, 'label' => $wording['label'], 'notes' => $this->instructions->notes($commitment), 'after_match_id' => $data->afterMatchId,
                        'planned_at' => $preview['target']['planned_at'] ?? null, 'revised_at' => $preview['target']['revised_at'] ?? null, 'minutes' => $preview['target']['minutes'] ?? null];
                    $content['segments'] = array_values(collect($content['segments'])->keyBy('id')->put($uuid, $entry)->all());
                } else {
                    $entry = ['id' => $uuid, ...$wording];
                    $content['sponsor_tasks'] = array_values(collect($content['sponsor_tasks'] ?? [])->keyBy('id')->put($uuid, $entry)->all());
                }

                if ($data->mode === SponsorInstructionMode::Segment && count($content['segments']) > 50) {
                    throw SponsorCommitmentException::segmentLimit();
                }
                $commitment->pack_key = $key;
                $commitment->pack_instruction_hash = $this->instructions->hash($entry);
            }
            $this->save->handle($event, $packVersion, $fingerprint, EventPackData::fromValidated($content), false);
            $this->persist->handle($commitment, $data->mode === SponsorInstructionMode::Remove ? 'retract_instruction' : 'review_instruction', $data->reason, $actor);
        }, attempts: 3);
    }
}
