<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Exam = 'exam';
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Project = 'project';
    case Participation = 'participation';

    public function label(): string
    {
        return match ($this) {
            self::Exam => 'Examen',
            self::Quiz => 'Contrôle',
            self::Assignment => 'Devoir',
            self::Project => 'Projet',
            self::Participation => 'Participation',
        };
    }
}
