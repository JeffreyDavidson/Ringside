<?php

declare(strict_types=1);

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Height input component for feet and inches.
 *
 * Provides a standardized height input with separate feet and inches fields,
 * commonly used in wrestler and other physical attribute forms.
 */
class HeightInput extends Component
{
    public function __construct(
        public string $feetField = 'height_feet',
        public string $inchesField = 'height_inches',
        public ?int $feet = null,
        public ?int $inches = null,
        public string $label = 'Height',
        public bool $required = true
    ) {}

    public function render(): View
    {
        /** @var view-string $viewPath */
        $viewPath = 'components.forms.height-input';

        return view($viewPath);
    }
}
