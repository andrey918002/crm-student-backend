<?php

return [

    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    'delete_records_older_than_days' => 365,

    'default_log_name' => 'default',

    /*
     * Явно 'web': Sanctum SPA та session API використовують guard web;
     * пакет підставляє causer з auth()->guard(...)->user().
     */
    'default_auth_driver' => env('ACTIVITY_LOG_AUTH_DRIVER', 'web'),

    'subject_returns_soft_deleted_models' => false,

    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
