<?php

declare(strict_types=1);

namespace App\Presenters;

use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class Presenter
{
    /**
     * Create a new Presenter instance.
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * Get the presenter for the assigned class.
     *
     * @throws \Exception
     */
    public function __get(string $property): mixed
    {
        $callable = [$this, $property];

        if (is_callable($callable)) {
            return call_user_func($callable);
        }

        $message = '%s does not respond to the "%s" property or method.';

        throw new Exception(sprintf($message, static::class, $property));
    }
}
