<?php

declare(strict_types=1);

namespace App\Console\Commands\Analytics;

use App\Analytics\Security\CredentialVault;
use App\Models\DataSource;
use App\Models\SemanticProvider;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Re-encrypts all DataSource + SemanticProvider credential blobs under the
 * current primary key. Run after changing EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY
 * (and moving the old key into EMBEDLAYER_PREVIOUS_CREDENTIAL_ENCRYPTION_KEYS).
 */
#[Signature('embedlayer:rotate-credentials')]
#[Description('Re-encrypt analytics credentials under the current primary key.')]
class RotateAnalyticsCredentials extends Command
{
    public function handle(CredentialVault $vault): int
    {
        $rotated = 0;

        DataSource::query()->each(function (DataSource $ds) use ($vault, &$rotated): void {
            $current = (string) $ds->encrypted_config;

            if ($current === '' || ! $vault->needsRotation($current)) {
                return;
            }

            $ds->forceFill(['encrypted_config' => $vault->rotate($current)])->save();
            $rotated++;
            $this->line("rotated DataSource {$ds->id}");
        });

        SemanticProvider::query()
            ->whereNotNull('encrypted_config')
            ->each(function (SemanticProvider $sp) use ($vault, &$rotated): void {
                $current = (string) $sp->encrypted_config;

                if ($current === '' || ! $vault->needsRotation($current)) {
                    return;
                }

                $sp->forceFill(['encrypted_config' => $vault->rotate($current)])->save();
                $rotated++;
                $this->line("rotated SemanticProvider {$sp->id}");
            });

        $this->info("Rotated {$rotated} credential blob(s).");

        return self::SUCCESS;
    }
}
