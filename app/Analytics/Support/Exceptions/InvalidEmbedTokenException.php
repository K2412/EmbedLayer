<?php

declare(strict_types=1);

namespace App\Analytics\Support\Exceptions;

use RuntimeException;

/**
 * Raised when an embed JWT cannot be trusted — bad signature, expired, malformed
 * claims, or missing required identifiers. See Plan §11 (Embed Token Design).
 */
final class InvalidEmbedTokenException extends RuntimeException {}
