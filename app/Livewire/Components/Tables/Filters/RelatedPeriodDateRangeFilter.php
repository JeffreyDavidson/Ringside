<?php

declare(strict_types=1);

namespace App\Livewire\Components\Tables\Filters;

use App\Livewire\Table\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

abstract class RelatedPeriodDateRangeFilter extends DateRangeFilter
{
    protected string $filterEndField = '';

    protected string $filterRelationshipName = '';

    protected string $filterStartField = '';

    public function __construct(string $name, ?string $key = null)
    {
        parent::__construct($name, $key);

        $this->config([
            'allowInput' => true,
            'altFormat' => 'F j, Y',
            'ariaDateFormat' => 'F j, Y',
            'dateFormat' => 'Y-m-d',
            'placeholder' => 'Enter Date Range',
            'locale' => 'en',
        ])
            ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate'])
            ->filter(function (Builder $query, array $dateRange): void {
                /** @var array{minDate: string, maxDate: string} $dateRange */
                $startDate = Date::parse($this->validDate($dateRange['minDate']) ?? 'today')->startOfDay();
                $endDate = Date::parse($this->validDate($dateRange['maxDate']) ?? 'today')->endOfDay();

                $query->whereHas($this->filterRelationshipName, function (Builder|Relation $query) use ($endDate, $startDate): void {
                    $query
                        ->where($this->filterStartField, '<=', $endDate)
                        ->where(function (Builder $query) use ($startDate): void {
                            $query
                                ->whereNull($this->filterEndField)
                                ->orWhere($this->filterEndField, '>=', $startDate);
                        });
                });
            });
    }

    public function setFields(string $relationshipName, string $startField, string $endField): static
    {
        $this->filterRelationshipName = $relationshipName;
        $this->filterStartField = $startField;
        $this->filterEndField = $endField;

        return $this;
    }

    private function validDate(string $value): ?string
    {
        return Date::hasFormat($value, 'Y-m-d') ? $value : null;
    }
}
