<?php

declare(strict_types=1);
use App\Data\Events\BudgetLineData;
use App\Data\Events\BudgetMovementData;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/** @param array<string, mixed> $lineAttributes
 * @return array{event: Event, budget: EventBudget, line: EventBudgetLine}
 */
function budgetFixture(array $lineAttributes = []): array
{
    actingAs(administrator());
    $event = Event::factory()->create();
    $budget = EventBudget::factory()->for($event)->create();
    $line = EventBudgetLine::factory()->for($budget, 'budget')->create($lineAttributes);

    return ['event' => $event, 'budget' => $budget, 'line' => $line];
}

/** @param array{kind?: string, amount?: string, effective_date?: string, method?: string, reference?: ?string, notes?: ?string, source_id?: ?int, acknowledge?: bool} $overrides
 * @return array{kind: string, amount: string, effective_date: string, method: string, reference?: ?string, notes?: ?string, source_id?: ?int, acknowledge?: bool}
 */
function budgetPayment(array $overrides = []): array
{
    return [...['kind' => 'payment', 'amount' => '200.00', 'effective_date' => today()->toDateString(), 'method' => 'cash'], ...$overrides];
}

/** @param array{direction?: string, category?: string, description?: string, counterparty?: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, acknowledge?: bool} $overrides
 * @return array{direction: string, category: string, description: string, counterparty: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, acknowledge?: bool}
 */
function budgetLineInput(array $overrides = []): array
{
    return [...['direction' => 'expense', 'category' => 'talent', 'description' => 'Main event fee', 'counterparty' => 'Casey', 'planned' => '500.00', 'confirmed' => '500.00', 'due_date' => '', 'notes' => '', 'reason' => '', 'acknowledge' => false], ...$overrides];
}

/** @param array{kind?: string, amount?: string, effective_date?: string, method?: string, reference?: ?string, notes?: ?string, source_id?: ?int, acknowledge?: bool} $overrides */
function budgetMovementData(array $overrides = []): BudgetMovementData
{
    return BudgetMovementData::fromValidated(budgetPayment($overrides));
}

/** @param array{direction?: string, category?: string, description?: string, counterparty?: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, acknowledge?: bool} $overrides */
function budgetLineData(array $overrides = []): BudgetLineData
{
    return BudgetLineData::fromValidated(budgetLineInput($overrides));
}

function budgetKey(): string
{
    return (string) Str::uuid();
}

/** @return array<string, string> */
function budgetBusinessFingerprint(): array
{
    $fingerprints = [];
    // Cache, sessions and worker queues are operational state rather than business records.
    $skip = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'sqlite_sequence'];

    foreach (Schema::getTableListing(schemaQualified: false) as $table) {
        if (in_array($table, $skip, true)) {
            continue;
        }

        $rows = DB::table($table)->get()->map(fn (object $row): string => json_encode($row, JSON_THROW_ON_ERROR))->all();
        sort($rows);
        $fingerprints[$table] = hash('sha256', implode("\n", $rows));
    }

    ksort($fingerprints);

    return $fingerprints;
}

/** Ordinary event outputs and the pack people may share never carry financial fields.
 * @return array<string, string>
 */
function budgetSharedSurfaces(Event $event): array
{
    $surfaces = [];

    foreach (['event' => 'events.show', 'pack' => 'events.pack.show', 'live' => 'events.live-show.show'] as $name => $routeName) {
        $surfaces[$name] = (string) \Pest\Laravel\get(route($routeName, $event))->assertOk()->getContent();
    }

    $surfaces['print'] = (string) \Pest\Laravel\get(route('events.pack.show', ['event' => $event, 'print' => 1]))->assertOk()->getContent();
    auth()->logout();
    app(PromotionContextService::class)->clear();
    $surfaces['public home'] = (string) \Pest\Laravel\get(route('home'))->assertOk()->getContent();

    // These random integrity/CSRF values carry no financial information; retain snapshot data itself.
    return array_map(function (string $html): string {
        $html = html_entity_decode($html, ENT_QUOTES);
        $html = preg_replace('/("(?:checksum|csrf|_token)"\\s*:\\s*")[^"]*"/', '$1[technical]"', $html) ?? $html;

        return preg_replace('/((?:name="_token"[^>]*value|name="csrf-token"[^>]*content)=")[^"]*"/', '$1[technical]"', $html) ?? $html;
    }, $surfaces);
}
