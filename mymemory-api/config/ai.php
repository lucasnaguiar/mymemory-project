<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    | Supported: "stub" (local dev), "openai" (production)
    | Set AI_PROVIDER=openai and OPENAI_API_KEY in .env to enable real calls.
    */
    'provider'   => env('AI_PROVIDER', 'stub'),
    'openai_key' => env('OPENAI_API_KEY', ''),
    'model'      => env('OPENAI_MODEL', 'gpt-4o-mini'),
];
