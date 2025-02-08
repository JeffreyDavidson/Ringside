<?php

declare(strict_types=1);

namespace App\View\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Traits\Core\HasWireables;
use Rappasoft\LaravelLivewireTables\Views\Traits\Filters\HandlesDates;
use Rappasoft\LaravelLivewireTables\Views\Traits\Filters\HasConfig;
use Rappasoft\LaravelLivewireTables\Views\Traits\Filters\HasOptions;

class FirstEmploymentFilter extends DateRangeFilter
{
    use HandlesDates,
        HasConfig,
        HasOptions;
    use HasWireables;

    public string $filterRelationshipName = '';

    public string $filterStartField = '';

    public string $filterEndField = '';

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
            ->filter(function (Builder $query, array $dateRange) {
                $query->withWhereHas($this->filterRelationshipName, function (Builder $query) use ($dateRange) {
                    $query
                        ->where(function (Builder $query) use ($dateRange) {
                            $query->whereBetween($this->filterStartField, [Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])->startOfDay(), Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])->endOfDay()]);
                        })
                        ->orWhere(function (Builder $query) use ($dateRange) {
                            $query->whereBetween($this->filterEndField, [Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])->startOfDay(), Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])->endOfDay()]);
                        });
                });
            });
    }

    public function setFields(string $relationshipName, string $startField, string $endField): self
    {
        $this->filterRelationshipName = $relationshipName;
        $this->filterStartField = $startField;
        $this->filterEndField = $endField;

        return $this;
    }
}
