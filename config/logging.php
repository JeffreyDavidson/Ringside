<?php

/*
|--------------------------------------------------------------------------
| Logging overrides
|--------------------------------------------------------------------------
|
| Everything else (channels, LOG_CHANNEL, LOG_LEVEL) comes from Laravel's
| own logging config. Deprecations keep Laravel's channel-and-trace shape
| so LOG_DEPRECATIONS_CHANNEL and LOG_DEPRECATIONS_TRACE both work.
|
*/

return [

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

];
