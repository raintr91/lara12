<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store third-party integration mock
    |--------------------------------------------------------------------------
    |
    | When true, store API endpoints that normally depend on external systems
    | (OTA bookings, Toreta sync, tracker/analytics, link tracking, surveys,
    | message delivery logs, etc.) return canned payloads so the store portal
    | can be exercised without those integrations. Set STORE_THIRD_PARTY_MOCK=false
    | in production.
    |
    */
    'mock' => filter_var(env('STORE_THIRD_PARTY_MOCK', false), FILTER_VALIDATE_BOOL),
];
