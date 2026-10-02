<?php

namespace App\Enums;

enum TeacherStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'En activité',
            self::Archived => 'Archivé',
        };
    }
}
