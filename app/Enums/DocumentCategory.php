<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Étudiant',
            self::Teacher => 'Enseignant',
            self::Administrative => 'Administratif',
        };
    }
}
