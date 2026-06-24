<?php

declare(strict_types=1);

namespace App\Analytics\Support\Enums;

enum JoinRelationship: string
{
    case OneToOne = 'one_to_one';
    case ManyToOne = 'many_to_one';
    case OneToMany = 'one_to_many';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
