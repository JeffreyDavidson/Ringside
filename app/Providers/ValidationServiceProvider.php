<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter */
        Validator::replacer('ends_with', function ($message, $attribute, $rule, $parameters) {
            $values = array_pop($parameters);

            if (count($parameters)) {
                $values = implode(', ', $parameters).' or '.$values;
            }

            return str_replace(':values', $values, $message);
        });
    }
}
