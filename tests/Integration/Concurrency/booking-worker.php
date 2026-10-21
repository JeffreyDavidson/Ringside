<?php

declare(strict_types=1);

/*
 * Child process for BookingConcurrencyTest: books one match through AddMatchForEventAction, or, when the spec
 * carries sponsor_event_id it creates/completes/cancels a sponsor promise, archives its sponsor, publishes a reviewed instruction, or prepares/reviews its live source. A spec that
 * carries a reschedule_date, moves an event to that date through Events\UpdateAction. A spec with a
 * create_event_at_venue_id creates an event at that venue through Events\CreateAction, and one with a
 * demote_user_id makes that owner a plain member of the promotion through UpdatePromotionMemberRoleAction. A spec
 * with a split_stable_id splits that stable through SplitStableAction, moving the listed wrestlers (comma-separated ids) into a new
 * stable named new_name. A create_stable_name creates an unformed stable with that name through Stables\CreateAction, a
 * restore_stable_id restores that deleted stable through Stables\RestoreAction, and an update_stable_id renames that stable to
 * new_name through Stables\UpdateAction. A create_tag_team_name creates a tag team of the two wrestler_ids (comma-separated) with that
 * name and signature_move through TagTeams\CreateAction, an update_tag_team_id renames that tag team to new_name through
 * TagTeams\UpdateAction, and a create_title_name creates a singles title with that name through Titles\CreateAction. A
 * promotion_id makes the worker act inside that promotion, as a request with an enforced promotion context does. A
 * create_wrestler_name creates a wrestler with that name and signature_move through Wrestlers\CreateAction, an update_wrestler_id
 * renames that wrestler to new_name through Wrestlers\UpdateAction, a create_event_name creates an unscheduled event with that
 * name through Events\CreateAction, and a create_venue_name creates a venue with that name through Venues\CreateAction.
 *
 * Usage: php booking-worker.php '<json spec>'
 *
 * The worker boots the application, opens its database connection, announces READY and then blocks on
 * STDIN until the parent sends a common start time, so every worker is released at the same instant.
 * A start_delay_ms in the spec holds that one worker back after the common start, so it lands inside the
 * other worker's transaction. It prints a single RESULT line as JSON.
 */

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Actions\Events\CreateAction;
use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Actions\Events\SaveTalentBookingAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Actions\Events\UpdateAction;
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\RecordResultAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Roster\Availability\CreateAction as CreateUnavailabilityAction;
use App\Actions\Sponsors\SaveSponsorAction;
use App\Actions\Stables\CreateAction as CreateStableAction;
use App\Actions\Stables\RestoreAction as RestoreStableAction;
use App\Actions\Stables\SplitStableAction;
use App\Actions\Stables\UpdateAction as UpdateStableAction;
use App\Actions\TagTeams\CreateAction as CreateTagTeamAction;
use App\Actions\TagTeams\UpdateAction as UpdateTagTeamAction;
use App\Actions\Titles\CreateAction as CreateTitleAction;
use App\Actions\Venues\CreateAction as CreateVenueAction;
use App\Actions\Wrestlers\CreateAction as CreateWrestlerAction;
use App\Actions\Wrestlers\UpdateAction as UpdateWrestlerAction;
use App\Data\Events\EventData;
use App\Data\Events\EventPackData;
use App\Data\Events\EventProductionData;
use App\Data\Events\LiveShowReviewData;
use App\Data\Events\SponsorCommitmentData;
use App\Data\Events\SponsorInstructionData;
use App\Data\Events\TalentBookingData;
use App\Data\Events\VenueData;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Data\Roster\UnavailabilityData;
use App\Data\Sponsors\SponsorData;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Data\TagTeams\TagTeamData;
use App\Data\Titles\TitleData;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\PublicSection;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Enums\Events\SponsorInstructionMode;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Titles\TitleType;
use App\Exceptions\BaseBusinessException;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Sponsors\Sponsor;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use App\ValueObjects\MatchSides;
use App\ValueObjects\PackKey;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 3).'/vendor/autoload.php';
require_once __DIR__.'/worker-support.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;

if (! is_string($payload)) {
    fwrite(STDERR, "Missing booking spec.\n");
    exit(1);
}

/** @var array{sponsor_event_id: int, sponsor_id: int, promise_id: int, user_id: int, version: int, submission_key: string, sponsor_operation: string, fingerprint: string, pack_version: int, preview_hash: string, target_key: string, mode: string, clock: string}|array{talent_event_id: int, user_id: int, person_id: int, version: int, fingerprint: string, talent_status: string, clock: string}|array{production_save_event_id: int, user_id: int, version: int, fingerprint: string, production_json: string}|array{crew_archive_id: int, user_id: int, version: int}|array{live_event_id: int, user_id: int, live_operation: string, version: int, pack_version: int, fingerprint: string, key: string, clock: string}|array{create_wrestler_name: string, signature_move: string, promotion_id?: int}|array{update_wrestler_id: int, new_name: string}|array{create_event_name: string, promotion_id?: int}|array{create_venue_name: string}|array{create_tag_team_name: string, signature_move: string, wrestler_ids: string, promotion_id?: int}|array{update_tag_team_id: int, new_name: string}|array{create_title_name: string, promotion_id?: int}|array{split_stable_id: int, new_name: string, wrestler_ids: string}|array{create_stable_name: string}|array{restore_stable_id: int}|array{update_stable_id: int, new_name: string}|array{restore_event_id: int, start_delay_ms?: int}|array{event_id: int, reschedule_date: string}|array{create_event_at_venue_id: int, date: string}|array{promotion_id: int, demote_user_id: int}|array{create_owned_stable_name: string, owned_promotion_id: int}|array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int}|array{unavailability_person_id: int, unavailability_day: string, user_id: int}|array{team_id: int, opponent_team_id: int, event_id: int, referee_id: int}|array{public_event_id: int, public_actor_id: int, public_version: int, public_fingerprint: string, public_operation: string, public_match_id: int, public_clock: string} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

DB::select('select 1');
fwrite(STDOUT, "READY\n");
// The parent sends the same absolute start time to every worker; spin until then so the actions overlap.
$startAt = (float) fgets(STDIN) + (($spec['start_delay_ms'] ?? 0) / 1000);

while (microtime(true) < $startAt) {
    // Busy-wait for the release time.
}

$result = ['ok' => true, 'exception' => null, 'deadlock' => false];

if (isset($spec['promotion_id']) && ! isset($spec['demote_user_id'])) {
    $context = resolve(PromotionContextService::class);
    $context->set(Promotion::query()->findOrFail($spec['promotion_id']));
    $context->enforce();
}

try {
    if (isset($spec['production_save_event_id'])) {
        auth()->login(User::query()->findOrFail($spec['user_id']));
        $production = json_decode($spec['production_json'], true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($production)) {
            throw new InvalidArgumentException('Production worker input must be a JSON object.');
        }
        resolve(SaveEventProductionAction::class)->handle(Event::query()->findOrFail($spec['production_save_event_id']), $spec['version'], $spec['fingerprint'], EventProductionData::fromValidated([
            'notes' => $production['notes'] ?? null, 'assignments' => $production['assignments'] ?? [], 'cues' => $production['cues'] ?? [],
        ]), false);
    } elseif (isset($spec['crew_archive_id'])) {
        auth()->login(User::query()->findOrFail($spec['user_id']));
        resolve(ArchiveCrewContactAction::class)->handle(CrewContact::query()->findOrFail($spec['crew_archive_id']), $spec['version']);
    } elseif (isset($spec['public_event_id'])) {
        $actor = User::query()->findOrFail($spec['public_actor_id']);
        auth()->login($actor);
        Carbon::setTestNow($spec['public_clock']);
        $event = Event::query()->findOrFail($spec['public_event_id']);
        if ($spec['public_operation'] === 'result') {
            $match = EventMatch::query()->findOrFail($spec['public_match_id']);
            resolve(RecordResultAction::class)->handle($match, new MatchResultData(MatchFinish::Submission, $match->sides()->firstOrFail(), collect()));
        } elseif ($spec['public_operation'] === 'card-edit') {
            resolve(UpdateAction::class)->handle($event, new EventData($event->name.' revised', $event->date, null, $event->preview));
        } elseif ($spec['public_operation'] === 'unpublish') {
            resolve(UnpublishEventPublicationAction::class)->handle($event, $actor, $spec['public_version'], $spec['public_fingerprint'], PublicSection::Card);
        } else {
            resolve(ReleaseEventPublicationAction::class)->handle($event, $actor, $spec['public_version'], $spec['public_fingerprint'], PublicSection::from($spec['public_operation']));
        }
    } elseif (isset($spec['unavailability_person_id'])) {
        resolve(CreateUnavailabilityAction::class)->handle(Wrestler::query()->findOrFail($spec['unavailability_person_id']), new UnavailabilityData($spec['unavailability_day'], $spec['unavailability_day'], null), User::query()->findOrFail($spec['user_id']));
    } elseif (isset($spec['team_id'])) {
        resolve(AddMatchForEventAction::class)->handle(Event::query()->findOrFail($spec['event_id']), new EventMatchData(
            MatchType::TagTeam, Referee::query()->whereKey($spec['referee_id'])->get(), Title::query()->whereKey([])->get(),
            new MatchSides(collect([1 => ['tag_teams' => [TagTeam::query()->findOrFail($spec['team_id'])]], 2 => ['tag_teams' => [TagTeam::query()->findOrFail($spec['opponent_team_id'])]]])), null,
        ));
    } elseif (isset($spec['talent_event_id'])) {
        auth()->login(User::query()->findOrFail($spec['user_id']));
        Carbon::setTestNow($spec['clock']);
        resolve(SaveTalentBookingAction::class)->handle(Event::query()->findOrFail($spec['talent_event_id']), 'wrestler', $spec['person_id'],
            $spec['version'], $spec['fingerprint'], new TalentBookingData(TalentBookingStatus::from($spec['talent_status']), null, null, 'UTC', null, true, 'Concurrent change'));
    } elseif (isset($spec['sponsor_event_id'])) {
        auth()->login(User::query()->findOrFail($spec['user_id']));
        Carbon::setTestNow($spec['clock']);
        $event = Event::query()->withTrashed()->findOrFail($spec['sponsor_event_id']);
        if ($spec['sponsor_operation'] === 'archive') {
            $sponsor = Sponsor::query()->findOrFail($spec['sponsor_id']);
            resolve(SaveSponsorAction::class)->handle(Promotion::query()->findOrFail($sponsor->promotion_id), $sponsor->id, $spec['version'], $sponsor->submission_key, new SponsorData($sponsor->name, archived: true));
        } elseif ($spec['sponsor_operation'] === 'publish') {
            resolve(SaveSponsorInstructionAction::class)->handle($event, $spec['promise_id'], $spec['version'], $spec['pack_version'], $spec['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::from($spec['mode']), PackKey::fromString($spec['target_key']), null, $spec['preview_hash']));
        } elseif ($spec['sponsor_operation'] === 'pack') {
            $source = resolve(EventPackViewModel::class)->data($event);
            resolve(SaveEventPackAction::class)->handle($event, $spec['pack_version'], $spec['fingerprint'], EventPackData::fromValidated([...$source['content'], 'notes' => 'Reviewed ordinary pack note']), false);
        } elseif ($spec['sponsor_operation'] === 'prepare') {
            resolve(PrepareLiveShowAction::class)->handle($event, 0, $spec['pack_version'], $spec['fingerprint']);
        } elseif ($spec['sponsor_operation'] === 'review') {
            resolve(ReviewLiveShowAction::class)->handle($event, $spec['version'], $spec['pack_version'], $spec['fingerprint'], LiveShowReviewData::fromValidated([]));
        } else {
            $status = match ($spec['sponsor_operation']) {
                'complete' => SponsorCommitmentStatus::Completed,
                'cancel' => SponsorCommitmentStatus::Cancelled,
                default => SponsorCommitmentStatus::Pending,
            };
            resolve(SaveSponsorCommitmentAction::class)->handle($event, $spec['sponsor_operation'] === 'create' ? null : $spec['promise_id'], $spec['version'], $spec['submission_key'],
                new SponsorCommitmentData($spec['sponsor_id'], SponsorCommitmentType::Banner, 'Concurrent banner', status: $status, evidence: $status === SponsorCommitmentStatus::Completed ? 'Checked at doors' : null),
                'Concurrent correction');
        }
    } elseif (isset($spec['live_event_id'])) {
        auth()->login(User::query()->findOrFail($spec['user_id']));
        Carbon::setTestNow($spec['clock']);
        $event = Event::query()->findOrFail($spec['live_event_id']);
        match ($spec['live_operation']) {
            'prepare' => resolve(PrepareLiveShowAction::class)->handle($event, $spec['version'], $spec['pack_version'], $spec['fingerprint']),
            'review' => resolve(ReviewLiveShowAction::class)->handle($event, $spec['version'], $spec['pack_version'], $spec['fingerprint'], LiveShowReviewData::fromValidated([])),
            'attendance' => resolve(MarkLiveAttendanceAction::class)->handle($event, $spec['version'], $spec['key'], LiveAttendanceMark::Arrived, true),
            default => resolve(TransitionLiveItemAction::class)->handle($event, $spec['version'], $spec['key'], LiveItemOperation::from($spec['live_operation'])),
        };
    } elseif (isset($spec['split_stable_id'])) {
        resolve(SplitStableAction::class)->handle(
            Stable::query()->findOrFail($spec['split_stable_id']),
            $spec['new_name'],
            new StableMembershipData(wrestlers: Wrestler::query()->whereKey(explode(',', $spec['wrestler_ids']))->get()),
            Carbon::now(),
        );
    } elseif (isset($spec['create_stable_name'])) {
        resolve(CreateStableAction::class)->handle(
            new StableData($spec['create_stable_name'], null, new StableMembershipData),
        );
    } elseif (isset($spec['restore_stable_id'])) {
        resolve(RestoreStableAction::class)->handle(Stable::withTrashed()->findOrFail($spec['restore_stable_id']));
    } elseif (isset($spec['update_stable_id'])) {
        resolve(UpdateStableAction::class)->handle(
            Stable::query()->findOrFail($spec['update_stable_id']),
            new StableData($spec['new_name'], null, new StableMembershipData),
        );
    } elseif (isset($spec['create_tag_team_name'])) {
        $wrestlers = Wrestler::query()->whereKey(explode(',', $spec['wrestler_ids']))->orderBy('id')->get();

        resolve(CreateTagTeamAction::class)->handle(
            new TagTeamData($spec['create_tag_team_name'], $spec['signature_move'] ?: null, null, $wrestlers->firstOrFail(), $wrestlers->skip(1)->firstOrFail()),
        );
    } elseif (isset($spec['update_tag_team_id'])) {
        $tagTeam = TagTeam::query()->findOrFail($spec['update_tag_team_id']);

        $partners = $tagTeam->wrestlers;

        resolve(UpdateTagTeamAction::class)->handle(
            $tagTeam,
            new TagTeamData($spec['new_name'], $tagTeam->signature_move, null, $partners->firstOrFail(), $partners->skip(1)->firstOrFail()),
        );
    } elseif (isset($spec['create_title_name'])) {
        resolve(CreateTitleAction::class)->handle(new TitleData($spec['create_title_name'], TitleType::Singles, null));
    } elseif (isset($spec['create_wrestler_name'])) {
        resolve(CreateWrestlerAction::class)->handle(
            new WrestlerData($spec['create_wrestler_name'], 73, 220, 'Concurrent City, Ohio', $spec['signature_move'] ?: null, null),
        );
    } elseif (isset($spec['update_wrestler_id'])) {
        $wrestler = Wrestler::query()->findOrFail($spec['update_wrestler_id']);

        resolve(UpdateWrestlerAction::class)->handle(
            $wrestler,
            new WrestlerData($spec['new_name'], $wrestler->height, $wrestler->weight, $wrestler->hometown, $wrestler->signature_move, null),
        );
    } elseif (isset($spec['create_event_name'])) {
        resolve(CreateAction::class)->handle(new EventData($spec['create_event_name'], null, null, null));
    } elseif (isset($spec['create_venue_name'])) {
        resolve(CreateVenueAction::class)->handle(
            new VenueData($spec['create_venue_name'], '1 Concurrent Way', 'Columbus', 'Ohio', '43004'),
        );
    } elseif (isset($spec['create_event_at_venue_id'])) {
        // A name of its own per worker: two events of one name are refused by the name guard, and this race is about the venue day.
        resolve(CreateAction::class)->handle(
            new EventData('Concurrent Venue Event '.getmypid(), Carbon::parse($spec['date']), Venue::query()->findOrFail($spec['create_event_at_venue_id']), null),
        );
    } elseif (isset($spec['demote_user_id'])) {
        resolve(UpdatePromotionMemberRoleAction::class)->handle(
            Promotion::query()->findOrFail($spec['promotion_id']),
            User::query()->findOrFail($spec['demote_user_id']),
            MembershipRole::Member,
        );
    } elseif (isset($spec['restore_event_id'])) {
        resolve(RestoreAction::class)->handle(Event::withTrashed()->findOrFail($spec['restore_event_id']));
    } elseif (isset($spec['reschedule_date'])) {
        $event = Event::query()->findOrFail($spec['event_id']);

        resolve(UpdateAction::class)->handle(
            $event,
            new EventData($event->name, Carbon::parse($spec['reschedule_date']), null, $event->preview),
        );
    } elseif (isset($spec['create_owned_stable_name'])) {
        // Creates an unformed stable of the given promotion, which no name lock guards (StableNameConcurrencyTest).
        resolve(CreateStableAction::class)->handle(
            new StableData($spec['create_owned_stable_name'], null, new StableMembershipData),
            $spec['owned_promotion_id'],
        );
    } else {
        resolve(AddMatchForEventAction::class)->handle(
            Event::query()->findOrFail($spec['event_id']),
            new EventMatchData(
                MatchType::Singles,
                Referee::query()->whereKey($spec['referee_id'])->get(),
                Title::query()->whereKey([])->get(),
                new MatchSides(collect([
                    1 => ['wrestlers' => [Wrestler::query()->findOrFail($spec['first_wrestler_id'])]],
                    2 => ['wrestlers' => [Wrestler::query()->findOrFail($spec['second_wrestler_id'])]],
                ])),
                null,
            ),
        );
    }
} catch (BaseBusinessException|ValidationException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => false, 'message' => mb_substr($exception->getMessage(), 0, 400)];
} catch (DeadlockException|QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => isDeadlock($exception), 'message' => mb_substr($exception->getMessage(), 0, 400)];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result)."\n");
