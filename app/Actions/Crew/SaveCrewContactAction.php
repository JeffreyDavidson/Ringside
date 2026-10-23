<?php

declare(strict_types=1);

namespace App\Actions\Crew;

use App\Data\Crew\CrewContactData;
use App\Exceptions\RecordChangedException;
use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

class SaveCrewContactAction
{
    public function __construct(private readonly PromotionContextService $context) {}

    public function handle(?CrewContact $contact, int $version, string $clientId, CrewContactData $data, User $actor): CrewContact
    {
        return DB::transaction(function () use ($contact, $version, $clientId, $data, $actor): CrewContact {
            if (! $contact instanceof CrewContact) {
                $promotion = Promotion::query()
                    ->whereKey($this->context->required()->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = CrewContact::query()
                    ->where('promotion_id', $promotion->id)
                    ->where('client_id', $clientId)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }

                if ($version !== 0) {
                    throw RecordChangedException::changed();
                }

                $saved = new CrewContact;
                $saved->promotion_id = $promotion->id;
                $saved->client_id = $clientId;
                $saved->version = 1;
            } else {
                $saved = $contact->refreshForUpdate();

                if ($saved->version !== $version || $saved->archived_at !== null) {
                    throw RecordChangedException::changed();
                }

                $saved->version++;
            }

            $saved->name = $data->name;
            $saved->phone = $data->phone;
            $saved->email = $data->email;
            $saved->notes = $data->notes;
            $saved->save();

            activity('production')
                ->causedBy($actor)
                ->withProperties([
                    'contact_id' => $saved->id,
                    'version' => $saved->version,
                ])
                ->log('contact-save');

            return $saved;
        }, attempts: 3);
    }
}
