<?php

declare(strict_types=1);

namespace App\Analytics\Security;

use App\Analytics\Support\Exceptions\CredentialVaultException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use SensitiveParameter;
use Throwable;

/**
 * Encrypts and decrypts customer credential blobs for data sources and
 * semantic providers, using a key dedicated to embedlayer (separate from
 * Laravel's APP_KEY). Supports key rotation through `previous_keys`.
 *
 * Plan §17.4: credentials must be encrypted at rest, never sent to the
 * frontend, and rotatable.
 */
final class CredentialVault
{
    private const CIPHER = 'aes-256-gcm';

    private readonly Encrypter $primary;

    /** @var list<Encrypter> */
    private readonly array $previous;

    /**
     * @param  string  $key  Raw 32-byte key (NOT base64); see fromConfig().
     * @param  list<string>  $previousKeys  Older raw keys for rotation.
     */
    public function __construct(
        #[SensitiveParameter] string $key,
        #[SensitiveParameter] array $previousKeys = [],
    ) {
        $this->primary = new Encrypter($key, self::CIPHER);
        $this->previous = array_map(
            fn (string $previous): Encrypter => new Encrypter($previous, self::CIPHER),
            array_values($previousKeys),
        );
    }

    public static function fromConfig(): self
    {
        return new self(
            key: self::resolveKey((string) config('embedlayer.credential_encryption_key', '')),
            previousKeys: array_map(
                self::resolveKey(...),
                (array) config('embedlayer.previous_credential_encryption_keys', []),
            ),
        );
    }

    /**
     * Encrypt a credential blob. Returns a portable ciphertext string suitable
     * for storage in a `json` column.
     *
     * @param  array<string, mixed>  $config
     */
    public function encryptConfig(#[SensitiveParameter] array $config): string
    {
        return $this->primary->encryptString(self::serialize($config));
    }

    /**
     * Decrypt a credential blob. Tries the primary key first, then any
     * configured previous keys (for rotation).
     *
     * @return array<string, mixed>
     */
    public function decryptConfig(string $ciphertext): array
    {
        return self::deserialize($this->decryptWithRotation($ciphertext));
    }

    /**
     * Returns true if `$ciphertext` was encrypted with a non-primary key and
     * should be re-encrypted next time the owning record is saved.
     */
    public function needsRotation(string $ciphertext): bool
    {
        try {
            $this->primary->decryptString($ciphertext);

            return false;
        } catch (DecryptException) {
            // Fall through — try previous keys below.
        }

        foreach ($this->previous as $encrypter) {
            try {
                $encrypter->decryptString($ciphertext);

                return true;
            } catch (DecryptException) {
                continue;
            }
        }

        throw new CredentialVaultException('Ciphertext does not decrypt with any configured key.');
    }

    /**
     * Re-encrypt a ciphertext under the current primary key. Safe to call
     * whether or not rotation is needed.
     */
    public function rotate(string $ciphertext): string
    {
        return $this->primary->encryptString($this->decryptWithRotation($ciphertext));
    }

    private function decryptWithRotation(string $ciphertext): string
    {
        try {
            return $this->primary->decryptString($ciphertext);
        } catch (DecryptException) {
            // Fall through — try previous keys below.
        }

        foreach ($this->previous as $encrypter) {
            try {
                return $encrypter->decryptString($ciphertext);
            } catch (DecryptException) {
                continue;
            }
        }

        throw new CredentialVaultException('Ciphertext does not decrypt with any configured key.');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function serialize(array $config): string
    {
        try {
            return json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            throw new CredentialVaultException('Failed to serialize credential config: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function deserialize(string $plain): array
    {
        try {
            $decoded = json_decode($plain, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new CredentialVaultException('Failed to deserialize credential config: '.$e->getMessage(), previous: $e);
        }

        if (! is_array($decoded)) {
            throw new CredentialVaultException('Decrypted credential payload is not an array.');
        }

        return $decoded;
    }

    /**
     * Accept keys either as raw 32 bytes or as `base64:<...>` (the Laravel
     * convention). Strips the prefix and base64-decodes if present.
     */
    private static function resolveKey(#[SensitiveParameter] string $key): string
    {
        if ($key === '') {
            throw new CredentialVaultException(
                'embedlayer credential encryption key is not configured. Set EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY.'
            );
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), strict: true);

            if ($decoded === false) {
                throw new CredentialVaultException('Invalid base64 encoding on embedlayer credential encryption key.');
            }

            return $decoded;
        }

        return $key;
    }
}
