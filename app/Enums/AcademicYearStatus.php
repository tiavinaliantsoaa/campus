<?php

namespace App\Enums;

enum AcademicYearStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Active',
            self::Closed => 'Clôturée',
            self::Archived => 'Archivée',
        };
    }

    public function allowsChanges(): bool
    {
        return $this === self::Draft || $this === self::Active;
    }
}
