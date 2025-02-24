<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Navigation\Navigation;
use Spatie\Navigation\Section;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving(Navigation::class, function (Navigation $navigation): Navigation {
            return $navigation
                ->add('Home', route('dashboard'))
                ->add('Roster', '', fn (Section $section) => $section
                    ->add('Wrestlers Table', route('wrestlers.index'))
                    ->add('Tag Teams Table', route('tag-teams.index'))
                    ->add('Managers Table', route('managers.index'))
                    ->add('Referees Table', route('referees.index'))
                    ->add('Stables Table', route('stables.index'))
                )
                ->add('Titles Table', route('titles.index'))
                ->add('Events Table', route('events.index'))
                ->add('Venues Table', route('venues.index'));
        });
    }
}
