<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Titles\NameTakenException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(
        private readonly DebutAction $debut,
        private readonly RecordNameLock $nameLock,
        private readonly GuardedNameRequirements $nameRequirements,
    ) {}

    /**
     * Create a title.
     *
     * This handles the complete title creation workflow:
     * - Rejects a name another title of the promotion already uses (deleted ones count, as they do for the form rule);
     *   nothing in the database keeps it unique, so it first takes the name lock, before anything else is written
     * - Creates the title record with name, description, and championship type
     * - Debuts the title through DebutAction if debut_date is provided, recording the Debuted transition
     * - Establishes the title as available for championship competition
     * - Sets up the foundation for future championship lineage
     *
     * @param  TitleData  $titleData  The data transfer object containing title information
     * @param  int|null  $promotionId  The promotion the title belongs to: the enforced promotion for a member, null for an administrator without one
     * @return Title The newly created title instance
     *
     * @throws NameTakenException When another title of the promotion already has the name
     */
    public function handle(TitleData $titleData, ?int $promotionId): Title
    {
        return DB::transaction(function () use ($titleData, $promotionId): Title {
            $this->nameLock->lock(GuardedName::TitleName, $promotionId, $titleData->name);

            $this->nameRequirements->ensureAvailable(
                Title::query()
                    ->withoutGlobalScope(PromotionContextScope::class)
                    ->withTrashed()
                    ->where('promotion_id', $promotionId),
                'name',
                $titleData->name,
                fn (): NameTakenException => NameTakenException::name($titleData->name),
            );

            // Create the base title record
            $title = Title::query()
                ->make([
                    'name' => $titleData->name,
                    'type' => $titleData->type,
                ])
                ->forceFill(['promotion_id' => $promotionId]);
            $title->save();

            // Create active status if debut_date is provided
            if (isset($titleData->debut_date)) {
                $this->debut->handle($title, $titleData->debut_date);
            }

            return $title;
        });
    }
}
