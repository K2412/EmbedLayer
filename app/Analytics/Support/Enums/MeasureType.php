<?php

declare(strict_types=1);

namespace App\Analytics\Support\Enums;

enum MeasureType: string
{
    case Count = 'count';
    case CountDistinct = 'count_distinct';
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
    case Ratio = 'ratio';
    case Calculated = 'calculated';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    public function requiresColumn(): bool
    {
        return match ($this) {
            self::Count, self::CountDistinct, self::Sum, self::Avg, self::Min, self::Max => true,
            self::Ratio, self::Calculated => false,
        };
    }

    public function requiresExpression(): bool
    {
        return $this === self::Ratio || $this === self::Calculated;
    }
}
