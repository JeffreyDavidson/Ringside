<?php

function set_active($name, $active = 'kt-menu__item--active')
{
    return Route::currentRouteNamed($name) ? $active : '';
}
