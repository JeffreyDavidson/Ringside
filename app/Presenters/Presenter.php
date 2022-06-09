<?php

declare(strict_types=1);

namespace App\Presenters;

use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class Presenter
{
    /**
     * The presentable model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Create a new Presenter instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Undocumented function.
     *
     * @param  string  $property
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $property)
    {
        $callable = [$this, $property];

        if (is_callable($callable)) {
            return call_user_func($callable);
        }

        $message = '%s does not respond to the "%s" property or method.';

        throw new Exception(sprintf($message, static::class, $property));
    }
}
