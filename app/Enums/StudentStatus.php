<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Inscrit',
            self::Archived => 'Archivé',
            self::Withdrawn => 'Radié',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Archived => 'neutral',
            self::Withdrawn => 'danger',
        };
    }
}
