<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * @mixin Eloquent
 */
trait HasCachedAttributes
{
    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute') ||
            method_exists($this, 'get'.Str::studly($key).'CachedAttribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        $studlyKey = Str::studly($key);
        if (method_exists($this, "get{$studlyKey}CachedAttribute")) {
            if (!isset($this->attributes[$key])) {
                $this->attributes[$key] = $this->{"get{$studlyKey}CachedAttribute"}($value);
            }

            return $this->attributes[$key];
        }

        return $this->{"get{$studlyKey}Attribute"}($value);
    }
}
