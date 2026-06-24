<?php

declare(strict_types=1);

namespace App\Analytics\Support\Exceptions;

use RuntimeException;

final class UnsupportedAggregateException extends RuntimeException
{
    public function __construct(public readonly string $aggregate, public readonly string $dialect = '')
    {
        $suffix = $dialect !== '' ? " for dialect `{$dialect}`" : '';
        parent::__construct("Unsupported aggregate `{$aggregate}`{$suffix}.");
    }
}
