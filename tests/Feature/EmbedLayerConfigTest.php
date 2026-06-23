<?php

it('exposes default ttl, row limit and query timeout', function () {
    expect(config('embedlayer.default_ttl_seconds'))->toBe(300)
        ->and(config('embedlayer.default_row_limit'))->toBe(10000)
        ->and(config('embedlayer.default_query_timeout_ms'))->toBe(30000);
});

it('exposes driver, provider and origin lists as arrays', function () {
    expect(config('embedlayer.enabled_drivers'))->toBeArray()
        ->and(config('embedlayer.enabled_providers'))->toBeArray()->toContain('internal')
        ->and(config('embedlayer.allowed_origins'))->toBeArray();
});

it('reads the embed signing key', function () {
    config()->set('embedlayer.embed_signing_key', 'test-key');

    expect(config('embedlayer.embed_signing_key'))->toBe('test-key');
});
