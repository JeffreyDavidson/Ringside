<?php

declare(strict_types=1);

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Address input component for US addresses.
 *
 * Provides a standardized address input with street, city, state, and zipcode
 * fields, commonly used in venue and contact forms.
 */
class AddressInput extends Component
{
    public function __construct(
        public string $streetField = 'street_address',
        public string $cityField = 'city',
        public string $stateField = 'state',
        public string $zipcodeField = 'zipcode',
        public ?string $street = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipcode = null,
        public string $label = 'Address'
    ) {}

    public function render(): View
    {
        /** @var view-string $viewPath */
        $viewPath = 'components.forms.address-input';

        return view($viewPath);
    }
}
