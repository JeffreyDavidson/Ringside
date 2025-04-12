<?php

declare(strict_types=1);

namespace App\View\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HandlesDates;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HasConfig;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HasOptions;
use Rappasoft\LaravelLivewireTables\Views\Traits\Core\HasWireables;

final class FirstEmploymentFilter extends DateRangeFilter
{
    use HandlesDates,
        HasConfig,
        HasOptions,
        HasWireables;

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
                /**
                 * @var array{'minDate': string, 'maxDate': string} $dateRange
                 */
                $query->withWhereHas($this->filterRelationshipName, function ($query) use ($dateRange) {
                    $query
                        ->where(function (Builder $query) use ($dateRange) {
                            $query->whereBetween(
                                $this->filterStartField,
                                [
                                    Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])?->startOfDay() ?? today()->startOfDay(),
                                    Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])?->endOfDay() ?? today()->endOfDay(),
                                ]);
                        })
                        ->orWhere(function (Builder $query) use ($dateRange) {
                            $query->whereBetween(
                                $this->filterEndField,
                                [
                                    Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])?->startOfDay() ?? today()->startOfDay(),
                                    Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])?->endOfDay() ?? today()->endOfDay(),
                                ]);
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
