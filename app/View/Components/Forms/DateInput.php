<?php

declare(strict_types=1);

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Date input component with optional future date validation.
 *
 * Provides a standardized date input commonly used for start dates,
 * activation dates, and employment dates.
 */
class DateInput extends Component
{
    public function __construct(
        public string $field,
        public ?string $value = null,
        public string $label = 'Date',
        public bool $required = false,
        public bool $futureOnly = false,
        public ?string $placeholder = null
    ) {}

    public function render(): View
    {
        /** @var view-string $viewPath */
        $viewPath = 'components.forms.date-input';

        return view($viewPath);
    }
}
