<?php

return [
    'daily_summary' => [
        'history_mode' => env('CHARITY_DAILY_SUMMARY_HISTORY_MODE', 'rolling_days'),
        'history_days' => (int) env('CHARITY_DAILY_SUMMARY_HISTORY_DAYS', 10),
    ],
];
