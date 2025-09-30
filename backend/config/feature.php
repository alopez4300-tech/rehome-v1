<?php

return [
    'profile' => env('APP_PROFILE', 'light'),
    'cost_tracking' => env('FEATURE_COST_TRACKING', false),
    'realtime' => env('FEATURE_REALTIME', false),
    'multi_tenant' => env('FEATURE_MULTI_TENANT', true),
    'client_portal' => env('FEATURE_CLIENT_PORTAL', false),
];