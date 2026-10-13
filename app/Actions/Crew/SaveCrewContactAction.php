<?php

declare(strict_types=1);

namespace App\Actions\Crew;

use App\Exceptions\Crew\CrewConflictException;
use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SaveCrewContactAction
{
    public function __construct(private readonly PromotionContextService $context) {}

    /** @param array{name: string, phone: ?string, email: ?string, notes: ?string} $data */
    public function handle(?CrewContact $contact, int $version, string $clientId, array $data): CrewContact
    {
        Gate::authorize($contact instanceof CrewContact ? 'update' : 'create', $contact ?? CrewContact::class);
        Validator::make([...$data, 'client_id' => $clientId], [
            'name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
            'client_id' => ['required', 'uuid'],
        ])->validate();

        return DB::transaction(function () use ($contact, $version, $clientId, $data): CrewContact {
            if (! $contact instanceof CrewContact) {
                $promotion = Promotion::query()->whereKey($this->context->required()->id)->lockForUpdate()->firstOrFail();
                $existing = CrewContact::query()->where('promotion_id', $promotion->id)->where('client_id', $clientId)->first();
                if ($existing !== null) {
                    return $existing;
                }
                if ($version !== 0) {
                    throw CrewConflictException::stale();
                }
                $saved = new CrewContact;
                $saved->promotion_id = $promotion->id;
                $saved->client_id = $clientId;
                $saved->version = 1;
            } else {
                $saved = CrewContact::query()->whereKey($contact->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $saved);
                if ($saved->version !== $version || $saved->archived_at !== null) {
                    throw CrewConflictException::stale();
                }
                $saved->version++;
            }
            $saved->fill($data);
            $saved->save();
            activity('production')->withProperties(['contact_id' => $saved->id, 'version' => $saved->version])->log('contact-save');

            return $saved;
        }, attempts: 3);
    }
}
