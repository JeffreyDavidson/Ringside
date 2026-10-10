<?php

return [

    /*
     * The framework's mail configuration, plus an optional reply-to for every outgoing message. Production sends
     * from a notifications address nobody reads, so MAIL_REPLY_TO_ADDRESS points replies (to an invitation or a
     * password reset, say) at a monitored inbox. Leave it unset and messages carry no Reply-To header.
     */
    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS'),
        'name' => env('MAIL_REPLY_TO_NAME', env('MAIL_FROM_NAME', env('APP_NAME', 'Ringside'))),
    ],

];
