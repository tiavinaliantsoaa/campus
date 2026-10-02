<?php

namespace App\Enums;

enum Audience: string
{
    case Everyone = 'everyone';
    case Role = 'role';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Tout l\'établissement',
            self::Role => 'Un rôle',
            self::Group => 'Un groupe',
        };
    }
}
