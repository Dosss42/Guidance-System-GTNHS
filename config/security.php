<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Restricts the browser to scripts, styles, fonts and images served by this
    | application. It is off in local development because the Vite dev server
    | runs on another port; it is on everywhere else.
    |
    */

    'csp' => (bool) env('SECURITY_CSP', env('APP_ENV', 'production') !== 'local'),

    /*
    | Strict-Transport-Security. Only sent over HTTPS. Turn on once the
    | production site is permanently served over HTTPS.
    */

    'hsts' => (bool) env('SECURITY_HSTS', env('APP_ENV', 'production') === 'production'),

];
