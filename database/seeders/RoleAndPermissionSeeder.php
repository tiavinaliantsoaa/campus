<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'students.view' => ['Consulter les étudiants', 'Étudiants'],
            'students.manage' => ['Gérer les étudiants', 'Étudiants'],
            'teachers.view' => ['Consulter les enseignants', 'Enseignants'],
            'teachers.manage' => ['Gérer les enseignants', 'Enseignants'],
            'groups.view' => ['Consulter les groupes', 'Groupes'],
            'groups.manage' => ['Gérer les groupes', 'Groupes'],
            'timetable.view' => ['Consulter l\'emploi du temps', 'Emploi du temps'],
            'timetable.manage' => ['Gérer l\'emploi du temps', 'Emploi du temps'],
            'attendance.view' => ['Consulter l\'assiduité', 'Assiduité'],
            'attendance.record' => ['Faire l\'appel', 'Assiduité'],
            'grades.view' => ['Consulter les notes', 'Notes'],
            'grades.enter' => ['Saisir les notes', 'Notes'],
            'grades.validate' => ['Valider les notes', 'Notes'],
            'finance.view' => ['Consulter la scolarité', 'Scolarité'],
            'finance.manage' => ['Gérer la scolarité', 'Scolarité'],
            'admissions.view' => ['Consulter les admissions', 'Admissions'],
            'admissions.manage' => ['Gérer les admissions', 'Admissions'],
            'documents.view' => ['Consulter les documents', 'Documents'],
            'documents.manage' => ['Gérer les documents', 'Documents'],
            'announcements.view' => ['Consulter les annonces', 'Communication'],
            'announcements.manage' => ['Publier des annonces', 'Communication'],
            'communication.manage' => ['Répondre aux messages', 'Communication'],
            'reports.view' => ['Consulter les rapports', 'Rapports'],
            'academic-years.manage' => ['Gérer les années académiques', 'Administration'],
            'administration.manage' => ['Gérer les comptes', 'Administration'],
        ];

        foreach ($permissions as $slug => [$name, $group]) {
            Permission::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'group_name' => $group]);
        }

        $roles = [
            'administration' => ['Administration / Direction', array_keys($permissions)],
            'pedagogical' => ['Équipe pédagogique', [
                'students.view', 'teachers.view', 'groups.view', 'groups.manage',
                'timetable.view', 'timetable.manage', 'attendance.view', 'attendance.record',
                'grades.view', 'grades.validate', 'admissions.view', 'documents.view', 'documents.manage',
                'announcements.view', 'announcements.manage', 'communication.manage', 'reports.view',
            ]],
            'teacher' => ['Enseignant', [
                'students.view', 'teachers.view', 'groups.view', 'timetable.view',
                'attendance.view', 'attendance.record', 'grades.view', 'grades.enter',
                'documents.view', 'announcements.view',
            ]],
            'student' => ['Étudiant', [
                'timetable.view', 'attendance.view', 'grades.view', 'finance.view',
                'documents.view', 'announcements.view',
            ]],
            'parent' => ['Parent / Responsable', [
                'timetable.view', 'attendance.view', 'grades.view', 'finance.view',
                'documents.view', 'announcements.view',
            ]],
        ];

        foreach ($roles as $slug => [$name, $granted]) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $name],
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $granted)->pluck('id'),
            );
        }
    }
}
