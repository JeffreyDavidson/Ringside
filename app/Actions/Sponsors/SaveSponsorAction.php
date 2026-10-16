<?php

declare(strict_types=1);

namespace App\Actions\Sponsors;

use App\Data\Sponsors\SponsorData;
use App\Exceptions\RecordChangedException;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveSponsorAction
{
    /** Creation serializes submission keys on the promotion. Metadata updates lock only the sponsor to avoid a promotion-FK/sponsor lock cycle with promise creation. */
    public function handle(Promotion $promotion, ?int $id, int $version, string $submissionKey, SponsorData $data): Sponsor
    {
        return DB::transaction(function () use ($promotion, $id, $version, $submissionKey, $data): Sponsor {
            $promotionQuery = Promotion::query()->whereKey($promotion->id);
            if ($id === null) {
                $promotionQuery->lockForUpdate();
            }
            $promotion = $promotionQuery->firstOrFail();
            Gate::authorize('manageSponsors', $promotion);
            $sponsor = $id === null ? null : Sponsor::query()->where('promotion_id', $promotion->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if (! Str::isUuid($submissionKey)) {
                throw RecordChangedException::changed();
            }
            if (($sponsor->version ?? 0) !== $version || ($sponsor === null && Sponsor::query()->where('promotion_id', $promotion->id)->where('submission_key', $submissionKey)->exists())) {
                throw RecordChangedException::changed();
            }
            $sponsor ??= new Sponsor;
            $sponsor->promotion_id = $promotion->id;
            Gate::authorize('manageSponsors', $sponsor);
            $sponsor->submission_key = $sponsor->exists ? $sponsor->submission_key : $submissionKey;
            $sponsor->name = mb_trim($data->name);
            $sponsor->contact_name = $data->contactName;
            $sponsor->contact_email = $data->email;
            $sponsor->contact_phone = $data->phone;
            $sponsor->notes = $data->notes;
            $sponsor->archived_at = $data->archived ? ($sponsor->archived_at ?? now()) : null;
            $sponsor->version = $version + 1;
            $sponsor->save();

            return $sponsor;
        }, attempts: 3);
    }
}
