<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Survey analytics mock mode
    |--------------------------------------------------------------------------
    |
    | When true, chain survey-analytics endpoints return canned data so the
    | chain portal can render without link_survey / third-party integration.
    | Set SURVEY_ANALYTICS_MOCK=false in production.
    |
    */
    'mock' => filter_var(env('SURVEY_ANALYTICS_MOCK', false), FILTER_VALIDATE_BOOL),
];
