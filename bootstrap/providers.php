<?php

use App\Providers\AppServiceProvider;
use App\Providers\NavigationServiceProvider;
use Christophrumpel\MissingLivewireAssertions\MissingLivewireAssertionsServiceProvider;

return [
    AppServiceProvider::class,
    NavigationServiceProvider::class,
    MissingLivewireAssertionsServiceProvider::class,
];
