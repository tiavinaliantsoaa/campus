<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Planifié',
            self::Cancelled => 'Annulé',
        };
    }
}
