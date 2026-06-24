<?php

declare(strict_types=1);

namespace App\Analytics\Support\Exceptions;

use RuntimeException;

final class UnsupportedTimeGrainException extends RuntimeException
{
    public function __construct(public readonly string $grain, public readonly string $dialect = '')
    {
        $suffix = $dialect !== '' ? " for dialect `{$dialect}`" : '';
        parent::__construct("Unsupported time grain `{$grain}`{$suffix}.");
    }
}
