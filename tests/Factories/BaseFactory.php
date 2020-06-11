<?php

namespace Tests\Factories;

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseFactory
{
    public $attributes = [];
    protected $propertiesToClone = [];
    protected $count = 1;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public static function new(array $attributes = [])
    {
        return new static($attributes);
    }

    public function count(int $count)
    {
        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    public function make(callable $callback, $attributes = [])
    {
        if ($this->count > 1) {
            $created = new Collection();
            for ($i = 0; $i < $this->count; $i++) {
                $clone = clone $this;
                $clone->count = 1;
                $created->push($clone->create($attributes));
            }

            return $created;
        }

        return call_user_func($callback, $attributes);
    }

    protected function withClone(callable $callback)
    {
        $clone = clone $this;
        call_user_func($callback, $clone);

        return $clone;
    }

    public function softDeleted($delete = true)
    {
        return $this->withClone(fn ($factory) => $factory->softDeleted = $delete);
    }

    protected function resolveAttributes($overrides = [])
    {
        /* @var \Faker\Generator $faker */
        $faker = resolve(Generator::class);

        return array_replace($this->defaultAttributes($faker), $this->attributes, $overrides);
    }

    public function getDefaults(Faker $faker)
    {
        return [];
    }

    abstract public function create($attributes = []);

    protected function __clone()
    {
        foreach ($this->propertiesToClone as $propertyName) {
            if (isset($this->{$propertyName})) {
                $this->{$propertyName} = clone $this->{$propertyName};
            }
        }
    }
}
