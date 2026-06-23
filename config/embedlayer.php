<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EmbedLayer
    |--------------------------------------------------------------------------
    |
    | Configuration for the EmbedLayer analytics product. See the technical
    | plan at embedlayer_v1_v3_technical_plan.md (§11 for the signing key and
    | embed-token design, §9 for data-source drivers, §7 for semantic
    | providers, §14 for V1 defaults).
    |
    */

    'embed_signing_key' => env('EMBEDLAYER_EMBED_SIGNING_KEY'),

    'credential_encryption_key' => env('EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY'),

    'previous_credential_encryption_keys' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EMBEDLAYER_PREVIOUS_CREDENTIAL_ENCRYPTION_KEYS', '')),
    ))),

    'default_ttl_seconds' => (int) env('EMBEDLAYER_DEFAULT_TTL_SECONDS', 300),

    'enabled_drivers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EMBEDLAYER_ENABLED_DRIVERS', 'postgres,mysql')),
    ))),

    'enabled_providers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EMBEDLAYER_ENABLED_PROVIDERS', 'internal')),
    ))),

    'default_row_limit' => (int) env('EMBEDLAYER_DEFAULT_ROW_LIMIT', 10000),

    'default_query_timeout_ms' => (int) env('EMBEDLAYER_DEFAULT_QUERY_TIMEOUT_MS', 30000),

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EMBEDLAYER_ALLOWED_ORIGINS', '')),
    ))),

];
