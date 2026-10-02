<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case Unspecified = 'unspecified';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Femme',
            self::Male => 'Homme',
            self::Unspecified => 'Non précisé',
        };
    }
}
