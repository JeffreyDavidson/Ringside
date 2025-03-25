<?php

function set_active($name, $active = 'kt-menu__item--active')
{
    return Route::currentRouteNamed($name) ? $active : '';
}

function set_open(array $routes, $open = 'kt-menu__item--open kt-menu__item--here')
{
    return in_array(Route::currentRouteName(), $routes) ? $open : '';
}
