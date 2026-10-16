<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\Queries\Events\EventPackCardQuery;
use App\Queries\Events\SponsorInstructionQuery;
use App\ViewModels\EventPackViewModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveSponsorInstructionAction
{
    public function __construct(private readonly SponsorInstructionQuery $instructions, private readonly EventPackCardQuery $card,
        private readonly EventPackViewModel $pack, private readonly SaveEventPackAction $save,
        private readonly PersistSponsorCommitmentAction $persist) {}

    public function handle(Event $event, int $id, int $version, int $packVersion, string $fingerprint, string $mode,
        ?string $targetKey, ?int $afterMatchId, string $previewHash, ?string $reason = null): void
    {
        DB::transaction(function () use ($event, $id, $version, $packVersion, $fingerprint, $mode, $targetKey, $afterMatchId, $previewHash, $reason): void {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('manageSponsors', $event);
            $matches = EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            $commitment = EventSponsorCommitment::query()->where('event_id', $event->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            $pack = EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $run = EventLiveRun::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $card = $this->card->get($event);
            $preview = $this->instructions->preview($event, $commitment, $mode, $targetKey, $afterMatchId);
            if ($commitment->version !== $version || ($pack->version ?? 0) !== $packVersion || ! hash_equals($card['fingerprint'], $fingerprint)
                || ! hash_equals($preview['hash'], $previewHash)) {
                throw RecordChangedException::changed();
            }
            if ($pack !== null && $pack->card_fingerprint !== $fingerprint) {
                throw SponsorCommitmentException::packReviewRequired();
            }
            if (! in_array($mode, ['segment', 'setup', 'remove'], true)
                || ($targetKey !== null && (! str_starts_with($targetKey, $mode.':') || $preview['target'] === null))
                || ($mode !== 'remove' && $commitment->pack_key !== null && $commitment->pack_key !== $targetKey)) {
                throw SponsorCommitmentException::invalidTarget();
            }
            if ($mode === 'remove' && ($commitment->pack_key === null || mb_trim($reason ?? '') === '')) {
                throw SponsorCommitmentException::reasonRequired();
            }
            if ($mode !== 'remove' && (mb_trim($commitment->instruction_label ?? '') === '' || mb_trim($commitment->instruction ?? '') === '')) {
                throw SponsorCommitmentException::instructionRequired();
            }
            if ($afterMatchId !== null && ! $matches->contains(fn (EventMatch $match): bool => $match->id === $afterMatchId && ! $match->trashed())) {
                throw SponsorCommitmentException::invalidTarget();
            }
            $protectedKeys = array_filter([$commitment->pack_key, $targetKey]);
            foreach ($run->content['items'] ?? [] as $item) {
                if (in_array($item['key'], $protectedKeys, true) && $item['state'] !== 'pending') {
                    throw SponsorCommitmentException::startedInstruction();
                }
            }
            $content = $pack->content ?? $this->pack->data($event)['content'];
            if ($mode === 'remove') {
                [$kind, $uuid] = explode(':', $commitment->pack_key);
                if ($kind === 'segment') {
                    $content['segments'] = array_values(array_filter($content['segments'], fn (array $entry): bool => $entry['id'] !== $uuid));
                } else {
                    $content['sponsor_tasks'] = array_values(array_filter($content['sponsor_tasks'] ?? [], fn (array $entry): bool => $entry['id'] !== $uuid));
                }
                $commitment->pack_key = null;
                $commitment->pack_instruction_hash = null;
            } else {
                $key = $targetKey ?? $mode.':'.Str::uuid();
                if (EventSponsorCommitment::query()->where('event_id', $event->id)->where('pack_key', $key)->whereKeyNot($commitment->id)->exists()) {
                    throw SponsorCommitmentException::usedTarget();
                }
                $uuid = explode(':', $key, 2)[1];
                $wording = $preview['wording'];
                if ($mode === 'segment') {
                    $entry = ['id' => $uuid, 'label' => $wording['label'], 'notes' => $this->instructions->notes($commitment), 'after_match_id' => $afterMatchId,
                        'planned_at' => $preview['target']['planned_at'] ?? null, 'revised_at' => $preview['target']['revised_at'] ?? null, 'minutes' => $preview['target']['minutes'] ?? null];
                    $content['segments'] = array_values(collect($content['segments'])->keyBy('id')->put($uuid, $entry)->all());
                } else {
                    $entry = ['id' => $uuid, ...$wording];
                    $content['sponsor_tasks'] = array_values(collect($content['sponsor_tasks'] ?? [])->keyBy('id')->put($uuid, $entry)->all());
                }
                if ($mode === 'segment' && count($content['segments']) > 50) {
                    throw SponsorCommitmentException::segmentLimit();
                }
                $commitment->pack_key = $key;
                $commitment->pack_instruction_hash = $this->instructions->hash($entry);
            }
            $this->save->handle($event, $packVersion, $fingerprint, $content, false);
            $this->persist->handle($commitment, $mode === 'remove' ? 'retract_instruction' : 'review_instruction', $reason);
        }, attempts: 3);
    }
}
