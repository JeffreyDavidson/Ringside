<?php

declare(strict_types=1);

namespace App\Livewire\Table\Columns;

use App\Livewire\Table\Column;
use Carbon\Carbon;
use DateTimeInterface;
use LogicException;

class DateColumn extends Column
{
    protected string $inputFormat = 'Y-m-d H:i:s';

    protected string $outputFormat = 'Y-m-d';

    protected string $emptyValue = '';

    public function inputFormat(string $format): static
    {
        $this->inputFormat = $format;

        return $this;
    }

    public function outputFormat(string $format): static
    {
        $this->outputFormat = $format;

        return $this;
    }

    public function emptyValue(string $value): static
    {
        $this->emptyValue = $value;

        return $this;
    }

    public function resolveValue(mixed $row): string
    {
        $value = data_get($row, $this->getField());

        if ($value === null || $value === '') {
            return $this->emptyValue;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format($this->outputFormat);
        }

        if (! is_string($value)) {
            throw new LogicException('Date column values must be date objects or formatted strings.');
        }

        $date = Carbon::createFromFormat($this->inputFormat, $value);

        return $date ? $date->format($this->outputFormat) : $this->emptyValue;
    }
}
