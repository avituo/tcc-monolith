<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Experiment Session Authentication
    |--------------------------------------------------------------------------
    |
    | The production application keeps its existing stateless API middleware.
    | The isolated thesis environment enables the web middleware so a Fortify
    | login session prepared before timing can authenticate API requests.
    |
    */
    'session_authentication' => (bool) env('TCC_EXPERIMENT_SESSION_AUTH', false),

    'allow_destructive_reset' => (bool) env('TCC_EXPERIMENT_ALLOW_RESET', false),

    'disable_rate_limiting' => (bool) env('TCC_EXPERIMENT_DISABLE_RATE_LIMITING', false),
];
