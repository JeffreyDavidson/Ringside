<?php

declare(strict_types=1);

use App\Enums\MatchFinish;

it('identifies whether a finish allows a title change', function (MatchFinish $finish, bool $allowsTitleChange) {
    expect($finish->allowsTitleChange())->toBe($allowsTitleChange);
})->with([
    'pinfall' => [MatchFinish::Pinfall, true],
    'submission' => [MatchFinish::Submission, true],
    'knockout' => [MatchFinish::Knockout, true],
    'stipulation' => [MatchFinish::Stipulation, true],
    'forfeit' => [MatchFinish::Forfeit, true],
    'disqualification' => [MatchFinish::Disqualification, false],
    'countout' => [MatchFinish::Countout, false],
    'time-limit draw' => [MatchFinish::TimeLimitDraw, false],
    'no decision' => [MatchFinish::NoDecision, false],
]);
