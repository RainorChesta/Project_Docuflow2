<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ONLYOFFICE Document Server URL (Browser Accessible)
    |--------------------------------------------------------------------------
    |
    | The base URL where the client browser can access ONLYOFFICE Docs API.
    | e.g., http://localhost:8080
    |
    */
    'url' => env('ONLYOFFICE_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | ONLYOFFICE Internal URL (Container/Server Accessible)
    |--------------------------------------------------------------------------
    |
    | The URL that ONLYOFFICE uses to download documents and send callbacks to Laravel.
    | In Docker setups, this may be http://host.docker.internal:8000 or Laravel's LAN IP.
    |
    */
    'internal_url' => env('ONLYOFFICE_INTERNAL_URL', env('APP_URL', 'http://localhost:8000')),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication
    |--------------------------------------------------------------------------
    |
    | Enable/disable JSON Web Token authentication between ONLYOFFICE and Laravel.
    |
    */
    'jwt_enabled' => env('ONLYOFFICE_JWT_ENABLED', false),

    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Disk where document files are saved.
    |
    */
    'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Editor Customization Options
    |--------------------------------------------------------------------------
    |
    | Controls editor autosave and forcesave behavior.
    |
    */
    'autosave' => env('ONLYOFFICE_AUTOSAVE', true),
    'forcesave' => env('ONLYOFFICE_FORCESAVE', true),
];
