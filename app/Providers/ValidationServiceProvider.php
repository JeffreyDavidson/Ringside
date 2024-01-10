<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter */
        Validator::replacer('ends_with', static function (string $message, string $attribute, string $rule, array $parameters) {
            $values = array_pop($parameters);

            if (count($parameters)) {
                $values = implode(', ', $parameters).' or '.$values;
            }

            return str_replace(':values', $values, $message);
        });
    }
}
