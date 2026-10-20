<?php

declare(strict_types=1);

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetOperation;
use App\Enums\Events\BudgetPaymentMethod;
use App\Enums\Events\EventStatus;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Stables\StableStatus;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Enums\Storylines\StorylineStatus;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentOperation;
use App\Enums\Tournaments\TournamentStatus;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;

test('enum labels render the text users see', function (Closure $resolveLabel, string $text): void {
    // Act
    $label = $resolveLabel();

    // Assert
    expect($label)->toBe($text);
})->with([
    'event past' => [EventStatus::Past->label(...), 'Past'],
    'event scheduled' => [EventStatus::Scheduled->label(...), 'Scheduled'],
    'event unscheduled' => [EventStatus::Unscheduled->label(...), 'Unscheduled'],

    'finish pinfall' => [MatchFinish::Pinfall->label(...), 'Pinfall'],
    'finish submission' => [MatchFinish::Submission->label(...), 'Submission'],
    'finish disqualification' => [MatchFinish::Disqualification->label(...), 'Disqualification'],
    'finish countout' => [MatchFinish::Countout->label(...), 'Countout'],
    'finish knockout' => [MatchFinish::Knockout->label(...), 'Knockout'],
    'finish stipulation' => [MatchFinish::Stipulation->label(...), 'Stipulation'],
    'finish forfeit' => [MatchFinish::Forfeit->label(...), 'Forfeit'],
    'finish time limit draw' => [MatchFinish::TimeLimitDraw->label(...), 'Time Limit Draw'],
    'finish no decision' => [MatchFinish::NoDecision->label(...), 'No Decision'],

    'match singles' => [MatchType::Singles->label(...), 'Singles'],
    'match tag team' => [MatchType::TagTeam->label(...), 'Tag Team'],
    'match triple threat' => [MatchType::TripleThreat->label(...), 'Triple Threat'],
    'match triangle' => [MatchType::Triangle->label(...), 'Triangle'],
    'match fatal four way' => [MatchType::Fatal4Way->label(...), 'Fatal 4 Way'],
    'match six man' => [MatchType::SixManTagTeam->label(...), '6 Man Tag Team'],
    'match eight man' => [MatchType::EightManTagTeam->label(...), '8 Man Tag Team'],
    'match ten man' => [MatchType::TenManTagTeam->label(...), '10 Man Tag Team'],
    'match two on one' => [MatchType::TwoOnOneHandicap->label(...), 'Two On One Handicap'],
    'match three on two' => [MatchType::ThreeOnTwoHandicap->label(...), 'Three On Two Handicap'],
    'match battle royal' => [MatchType::BattleRoyal->label(...), 'Battle Royal'],
    'match royal rumble' => [MatchType::RoyalRumble->label(...), 'Royal Rumble'],
    'match tornado' => [MatchType::TornadoTagTeam->label(...), 'Tornado Tag Team'],
    'match gauntlet' => [MatchType::Gauntlet->label(...), 'Gauntlet'],

    'title type singles' => [TitleType::Singles->label(...), 'Singles'],
    'title type tag team' => [TitleType::TagTeam->label(...), 'Tag Team'],
    'title undebuted' => [TitleStatus::Undebuted->label(...), 'Not Yet Debuted'],
    'title pending debut' => [TitleStatus::PendingDebut->label(...), 'Scheduled to Debut'],
    'title active' => [TitleStatus::Active->label(...), 'Active'],
    'title inactive' => [TitleStatus::Inactive->label(...), 'Inactive'],
    'title retired' => [TitleStatus::Retired->label(...), 'Retired'],

    'stable unformed' => [StableStatus::Unformed->label(...), 'Not Yet Formed'],
    'stable pending' => [StableStatus::PendingEstablishment->label(...), 'Not Yet Established'],
    'stable active' => [StableStatus::Active->label(...), 'Active'],
    'stable inactive' => [StableStatus::Inactive->label(...), 'Inactive'],
    'stable retired' => [StableStatus::Retired->label(...), 'Retired'],

    'employment employed' => [EmploymentStatus::Employed->label(...), 'Employed'],
    'employment future' => [EmploymentStatus::FutureEmployment->label(...), 'Awaiting Employment'],
    'employment released' => [EmploymentStatus::Released->label(...), 'Released'],
    'employment retired' => [EmploymentStatus::Retired->label(...), 'Retired'],
    'employment unemployed' => [EmploymentStatus::Unemployed->label(...), 'Unemployed'],

    'role administrator' => [Role::Administrator->label(...), 'Administrator'],
    'role basic' => [Role::Basic->label(...), 'Basic'],
    'user unverified' => [UserStatus::Unverified->label(...), 'Unverified'],
    'user active' => [UserStatus::Active->label(...), 'Active'],
    'user inactive' => [UserStatus::Inactive->label(...), 'Inactive'],

    'budget talent' => [BudgetCategory::Talent->label(...), 'Talent'],
    'budget travel' => [BudgetCategory::Travel->label(...), 'Travel'],
    'budget venue' => [BudgetCategory::Venue->label(...), 'Venue'],
    'budget ring' => [BudgetCategory::Ring->label(...), 'Ring'],
    'budget production' => [BudgetCategory::Production->label(...), 'Production'],
    'budget tickets' => [BudgetCategory::Tickets->label(...), 'Tickets'],
    'budget sponsorship' => [BudgetCategory::Sponsorship->label(...), 'Sponsorship'],
    'budget merchandise' => [BudgetCategory::Merchandise->label(...), 'Merchandise'],
    'budget other' => [BudgetCategory::Other->label(...), 'Other'],
    'direction income' => [BudgetDirection::Income->label(...), 'Income'],
    'direction expense' => [BudgetDirection::Expense->label(...), 'Expenses'],
    'movement payment' => [BudgetMovementKind::Payment->label(...), 'Payment / receipt'],
    'movement refund' => [BudgetMovementKind::Refund->label(...), 'Refund'],
    'operation save line' => [BudgetOperation::SaveLine->label(...), 'Save line'],
    'operation archive line' => [BudgetOperation::ArchiveLine->label(...), 'Archive line'],
    'operation record movement' => [BudgetOperation::RecordMovement->label(...), 'Record money'],
    'operation correct movement' => [BudgetOperation::CorrectMovement->label(...), 'Correct recording'],
    'method cash' => [BudgetPaymentMethod::Cash->label(...), 'Cash'],
    'method bank transfer' => [BudgetPaymentMethod::BankTransfer->label(...), 'Bank transfer'],
    'method other' => [BudgetPaymentMethod::Other->label(...), 'Other'],

    'live show prepared' => [LiveShowStatus::Prepared->label(...), 'Prepared'],
    'live show running' => [LiveShowStatus::Running->label(...), 'Running'],
    'live show completed' => [LiveShowStatus::Completed->label(...), 'Show completed'],
    'live item pending' => [LiveItemStatus::Pending->label(...), 'Pending'],
    'live item running' => [LiveItemStatus::Running->label(...), 'Running'],
    'live item finished' => [LiveItemStatus::Finished->label(...), 'Finished'],
    'live item skipped' => [LiveItemStatus::Skipped->label(...), 'Skipped'],

    'booking proposed' => [TalentBookingStatus::Proposed->label(...), 'Proposed'],
    'booking contacted' => [TalentBookingStatus::Contacted->label(...), 'Contacted'],
    'booking confirmed' => [TalentBookingStatus::Confirmed->label(...), 'Confirmed'],
    'booking cancelled' => [TalentBookingStatus::Cancelled->label(...), 'Cancelled'],

    'story active' => [StorylineStatus::Active->label(...), 'Active'],
    'story paused' => [StorylineStatus::Paused->label(...), 'Paused'],
    'story completed' => [StorylineStatus::Completed->label(...), 'Completed'],
    'story archived' => [StorylineStatus::Archived->label(...), 'Archived'],
    'step planned' => [StepStatus::Planned->label(...), 'Planned'],
    'step postponed' => [StepStatus::Postponed->label(...), 'Postponed'],
    'step cancelled' => [StepStatus::Cancelled->label(...), 'Cancelled'],
    'step happened' => [StepStatus::Happened->label(...), 'Happened'],
    'revision created' => [StorylineRevisionOperation::Created->label(...), 'Storyline created'],
    'revision story revised' => [StorylineRevisionOperation::StoryRevised->label(...), 'Premise revised'],
    'revision story status' => [StorylineRevisionOperation::StoryStatus->label(...), 'Story status changed'],
    'revision step planned' => [StorylineRevisionOperation::StepPlanned->label(...), 'Step planned'],
    'revision step revised' => [StorylineRevisionOperation::StepRevised->label(...), 'Step revised'],
    'revision step status' => [StorylineRevisionOperation::StepStatus->label(...), 'Step status changed'],
    'revision participant joined' => [StorylineRevisionOperation::ParticipantJoined->label(...), 'Participant joined'],
    'revision participant left' => [StorylineRevisionOperation::ParticipantLeft->label(...), 'Participant left'],
    'revision development recorded' => [StorylineRevisionOperation::DevelopmentRecorded->label(...), 'Development recorded'],
    'revision development corrected' => [StorylineRevisionOperation::DevelopmentCorrected->label(...), 'Account corrected'],
    'revision development retracted' => [StorylineRevisionOperation::DevelopmentRetracted->label(...), 'Account retracted'],
    'revision development retracted reopen' => [StorylineRevisionOperation::DevelopmentRetractedReopen->label(...), 'Account retracted; step reopened'],

    'tournament draft' => [TournamentStatus::Draft->label(...), 'Draft'],
    'tournament active' => [TournamentStatus::Active->label(...), 'In progress'],
    'tournament completed' => [TournamentStatus::Completed->label(...), 'Completed'],
    'tournament cancelled' => [TournamentStatus::Cancelled->label(...), 'Cancelled'],
    'entrant wrestler' => [EntrantKind::Wrestler->label(...), 'Wrestlers'],
    'entrant tag team' => [EntrantKind::TagTeam->label(...), 'Tag teams'],
    'operation book' => [TournamentOperation::Book->label(...), 'Book this bout'],
    'operation plan' => [TournamentOperation::Plan->label(...), 'Plan an event'],
    'operation reset' => [TournamentOperation::Reset->label(...), 'Reset unplayed future rounds'],
    'operation unbook' => [TournamentOperation::Unbook->label(...), 'Remove this unplayed booking'],
    'operation withdraw' => [TournamentOperation::Withdraw->label(...), 'Withdraw entrant'],
    'operation replace' => [TournamentOperation::Replace->label(...), 'Replace an unplayed entrant'],
    'operation walkover' => [TournamentOperation::Walkover->label(...), 'Advance the opponent by walkover'],
    'live item start' => [LiveItemOperation::Start->label(...), 'Start item'],
    'live item finish' => [LiveItemOperation::Finish->label(...), 'Finish item'],
    'live item skip' => [LiveItemOperation::Skip->label(...), 'Skip item'],
    'live item correct' => [LiveItemOperation::Correct->label(...), 'Correct actual times'],
    'live item reopen' => [LiveItemOperation::Reopen->label(...), 'Reopen / restore item'],
    'operation cancel' => [TournamentOperation::Cancel->label(...), 'Cancel this tournament'],
]);

test('title types translate the competitor label used in messages', function (TitleType $type, string $text): void {
    // Act
    $label = $type->competitorLabel();

    // Assert
    expect($label)->toBe($text);
})->with([
    'singles' => [TitleType::Singles, 'wrestlers'],
    'tag team' => [TitleType::TagTeam, 'tag teams'],
]);

test('match finishes, match types and title types expose select options without an all row', function (): void {
    // Act
    $finishes = MatchFinish::options();
    $types = MatchType::options();
    $titleTypes = TitleType::options();

    // Assert
    expect($finishes)->toHaveCount(9)
        ->and($finishes['time-limit-draw'])->toBe('Time Limit Draw')
        ->and($types)->toHaveCount(14)
        ->and($types['6-man-tag-team'])->toBe('6 Man Tag Team')
        ->and($titleTypes)->toBe(['singles' => 'Singles', 'tag-team' => 'Tag Team']);
});

test('filter options prepend an all row to the select options', function (): void {
    // Act
    $options = TitleType::filterOptions();

    // Assert
    expect($options)->toBe(['' => __('core.all'), 'singles' => 'Singles', 'tag-team' => 'Tag Team']);
});
