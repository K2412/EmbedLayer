<?php

declare(strict_types=1);

namespace App\Analytics\Support\Exceptions;

use RuntimeException;

final class SemanticModelValidationException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Semantic model validation failed: '.implode('; ', $errors));
    }
}
