<?php

declare(strict_types=1);

use App\Analytics\Security\CredentialVault;
use App\Analytics\Support\Exceptions\CredentialVaultException;
use Illuminate\Encryption\Encrypter;
use Tests\TestCase;

uses(TestCase::class);

function vaultKey(): string
{
    return Encrypter::generateKey('aes-256-gcm');
}

it('encrypts and decrypts a credential config round-trip', function () {
    $vault = new CredentialVault(vaultKey());
    $config = ['host' => 'db.example.com', 'port' => 5432, 'password' => 'hunter2'];

    $ciphertext = $vault->encryptConfig($config);

    expect($ciphertext)->toBeString()->not->toContain('hunter2')
        ->and($vault->decryptConfig($ciphertext))->toBe($config);
});

it('produces different ciphertexts for the same plaintext (nonce randomness)', function () {
    $vault = new CredentialVault(vaultKey());
    $config = ['secret' => 's'];

    expect($vault->encryptConfig($config))->not->toBe($vault->encryptConfig($config));
});

it('rejects ciphertext encrypted with an unrelated key', function () {
    $a = new CredentialVault(vaultKey());
    $b = new CredentialVault(vaultKey());

    $ciphertext = $a->encryptConfig(['x' => 1]);

    expect(fn () => $b->decryptConfig($ciphertext))->toThrow(CredentialVaultException::class);
});

it('decrypts ciphertexts produced under a previous key during rotation', function () {
    $oldKey = vaultKey();
    $newKey = vaultKey();

    $oldVault = new CredentialVault($oldKey);
    $rotatedVault = new CredentialVault($newKey, [$oldKey]);

    $ciphertext = $oldVault->encryptConfig(['token' => 'abc']);

    expect($rotatedVault->decryptConfig($ciphertext))->toBe(['token' => 'abc'])
        ->and($rotatedVault->needsRotation($ciphertext))->toBeTrue();
});

it('reports no rotation needed when ciphertext uses the primary key', function () {
    $vault = new CredentialVault(vaultKey());
    $ciphertext = $vault->encryptConfig(['k' => 'v']);

    expect($vault->needsRotation($ciphertext))->toBeFalse();
});

it('rotates ciphertext onto the primary key', function () {
    $oldKey = vaultKey();
    $newKey = vaultKey();

    $rotatedVault = new CredentialVault($newKey, [$oldKey]);
    $oldCiphertext = (new CredentialVault($oldKey))->encryptConfig(['k' => 'v']);

    $newCiphertext = $rotatedVault->rotate($oldCiphertext);

    expect($newCiphertext)->not->toBe($oldCiphertext)
        ->and($rotatedVault->needsRotation($newCiphertext))->toBeFalse()
        ->and($rotatedVault->decryptConfig($newCiphertext))->toBe(['k' => 'v']);
});

it('accepts base64-prefixed keys via fromConfig', function () {
    $rawKey = vaultKey();
    config()->set('embedlayer.credential_encryption_key', 'base64:'.base64_encode($rawKey));
    config()->set('embedlayer.previous_credential_encryption_keys', []);

    $vault = CredentialVault::fromConfig();
    $ciphertext = $vault->encryptConfig(['ok' => true]);

    expect($vault->decryptConfig($ciphertext))->toBe(['ok' => true]);
});

it('throws when no encryption key is configured', function () {
    config()->set('embedlayer.credential_encryption_key', '');

    expect(fn () => CredentialVault::fromConfig())->toThrow(CredentialVaultException::class);
});

it('rejects invalid base64 in a configured key', function () {
    config()->set('embedlayer.credential_encryption_key', 'base64:@@@not-base64');

    expect(fn () => CredentialVault::fromConfig())->toThrow(CredentialVaultException::class);
});
