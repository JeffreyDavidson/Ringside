<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('orderByNullsLast', function ($column, $direction = 'asc') {
            $column = $this->getGrammar()->wrap($column);
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $this->orderByRaw("$column IS NULL $direction, $column $direction");
        });

        Request::macro('validatedExcept', function ($keys) {
            $keys = is_array($keys) ? $keys : func_get_args();

            $results = $this->validated();

            Arr::forget($results, $keys);

            return $results;
        });

        BelongsToMany::macro('asSingleEntity', function () {
            return new class($this->related->newQuery(), $this->parent, $this->table, $this->foreignPivotKey, $this->relatedPivotKey, $this->parentKey, $this->relatedKey, $this->relationName) extends BelongsToMany {
                /**
                 * Match the eagerly loaded results to their parents.
                 *
                 * @param  array   $models
                 * @param  \Illuminate\Database\Eloquent\Collection  $results
                 * @param  string  $relation
                 * @return array
                 */
                public function match(array $models, Collection $results, $relation)
                {
                    $dictionary = $this->buildDictionary($results);

                    // Once we have an array dictionary of child objects we can easily match the
                    // children back to their parent using the dictionary and the keys on the
                    // the parent models. Then we will return the hydrated models back out.
                    foreach ($models as $model) {
                        if (isset($dictionary[$key = $model->{$this->parentKey}])) {
                            $model->setRelation(
                                // $relation, $this->related->newCollection($dictionary[$key])      // original code
                                $relation, array_first($dictionary[$key])
                            );
                        } else {
                            $model->setRelation($relation, null);
                        }
                    }

                    return $models;
                }
            };
        });
    }
}
