<?php

namespace Tests;

use App\Analytics\Security\CredentialVault;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('embedlayer.credential_encryption_key', Encrypter::generateKey('aes-256-gcm'));
        config()->set('embedlayer.previous_credential_encryption_keys', []);
        $this->app->forgetInstance(CredentialVault::class);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
