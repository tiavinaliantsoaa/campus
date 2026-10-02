<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouvert',
            self::Answered => 'Répondu',
            self::Closed => 'Clôturé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Answered => 'info',
            self::Closed => 'neutral',
        };
    }
}
