<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\Visibility;

class Navigation
{
    /**
     * @return list<array{label: string, route: string, icon: string, active: string, permission: ?string, badge: ?string}>
     */
    public static function items(): array
    {
        return [
            ['label' => 'Vue d\'ensemble', 'route' => 'dashboard', 'icon' => 'home', 'active' => 'dashboard', 'permission' => null, 'badge' => null],
            ['label' => 'Emploi du temps', 'route' => 'courses.index', 'icon' => 'calendar', 'active' => 'courses.*', 'permission' => 'timetable.view', 'badge' => null],
            ['label' => 'Assiduité', 'route' => 'attendance.index', 'icon' => 'clipboard', 'active' => 'attendance.*', 'permission' => 'attendance.view', 'badge' => 'attendance'],
            ['label' => 'Étudiants', 'route' => 'students.index', 'icon' => 'users', 'active' => 'students.*', 'permission' => 'students.view', 'badge' => null],
            ['label' => 'Enseignants', 'route' => 'teachers.index', 'icon' => 'academic', 'active' => 'teachers.*', 'permission' => 'teachers.view', 'badge' => null],
            ['label' => 'Classes / Groupes', 'route' => 'groups.index', 'icon' => 'groups', 'active' => 'groups.*|levels.*|subjects.*|rooms.*', 'permission' => 'groups.view', 'badge' => null],
            ['label' => 'Admissions', 'route' => 'applicants.index', 'icon' => 'admissions', 'active' => 'applicants.*', 'permission' => 'admissions.view', 'badge' => null],
            ['label' => 'Notes & résultats', 'route' => 'assessments.index', 'icon' => 'grades', 'active' => 'assessments.*', 'permission' => 'grades.view', 'badge' => null],
            ['label' => 'Scolarité & paiements', 'route' => 'finance.index', 'icon' => 'wallet', 'active' => 'finance.*', 'permission' => 'finance.view', 'badge' => null],
            ['label' => 'Documents', 'route' => 'documents.index', 'icon' => 'folder', 'active' => 'documents.*', 'permission' => 'documents.view', 'badge' => null],
            ['label' => 'Communication', 'route' => 'announcements.index', 'icon' => 'megaphone', 'active' => 'announcements.*|inquiries.*', 'permission' => 'announcements.view', 'badge' => null],
            ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'chart', 'active' => 'reports.*', 'permission' => 'reports.view', 'badge' => null],
            ['label' => 'Administration', 'route' => 'academic-years.index', 'icon' => 'settings', 'active' => 'academic-years.*|users.*', 'permission' => 'academic-years.manage', 'badge' => null],
        ];
    }

    /**
     * @return list<array{label: string, route: string, icon: string, active: string, permission: ?string, badge: ?string}>
     */
    public static function for(User $user): array
    {
        return array_values(array_filter(
            self::items(),
            fn (array $item): bool => $item['permission'] === null || $user->hasPermission($item['permission']),
        ));
    }

    public static function attendanceBadge(User $user, ?AcademicYear $year): int
    {
        if (! $year || ! $user->hasPermission('attendance.record')) {
            return 0;
        }

        return app(Visibility::class)
            ->courses($user, $year)
            ->whereDate('starts_at', today())
            ->where('status', 'scheduled')
            ->whereDoesntHave('attendanceRecords')
            ->count();
    }
}
