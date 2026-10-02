<?php

namespace Database\Seeders;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\CourseStatus;
use App\Enums\PaymentMethod;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Models\AcademicYear;
use App\Models\AdmissionStatus;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use App\Models\FeeTariff;
use App\Models\Grade;
use App\Models\GroupAssignment;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de démonstration locales. Ne pas exécuter sur une base réelle déjà peuplée.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Student::query()->exists()) {
            return;
        }

        $year = AcademicYear::query()->where('name', '2026-2027')->firstOrFail();
        $levels = Level::query()->pluck('id', 'code');

        DB::transaction(function () use ($year, $levels): void {
            $subjects = collect([
                ['name' => 'Management des équipes', 'code' => 'MGT'],
                ['name' => 'Communication digitale', 'code' => 'COM'],
                ['name' => 'Analyse financière', 'code' => 'FIN'],
                ['name' => 'Marketing', 'code' => 'MKT'],
                ['name' => 'Comptabilité', 'code' => 'CPT'],
            ])->mapWithKeys(fn (array $subject) => [$subject['code'] => Subject::query()->create($subject)]);

            $rooms = collect([
                ['name' => 'Amphi A101', 'code' => 'A101', 'building' => 'Bâtiment A', 'capacity' => 40],
                ['name' => 'Salle A102', 'code' => 'A102', 'building' => 'Bâtiment A', 'capacity' => 30],
                ['name' => 'Salle R201', 'code' => 'R201', 'building' => 'Bâtiment R', 'capacity' => 25],
            ])->mapWithKeys(fn (array $room) => [$room['code'] => Room::query()->create($room)]);

            $groups = collect([
                ['name' => 'Bachelor 1 — G1', 'code' => 'B1-G1', 'level' => 'B1', 'color' => '#2563eb'],
                ['name' => 'Bachelor 2 — G1', 'code' => 'B2-G1', 'level' => 'B2', 'color' => '#059669'],
                ['name' => 'Bachelor 3', 'code' => 'B3', 'level' => 'B3', 'color' => '#d0123c'],
                ['name' => 'MBA 1', 'code' => 'MBA-1', 'level' => 'MBA', 'color' => '#7c3aed'],
                ['name' => 'Master 1 — A', 'code' => 'M1-A', 'level' => 'M1', 'color' => '#d97706'],
            ])->mapWithKeys(fn (array $group) => [$group['code'] => StudentGroup::query()->create([
                'academic_year_id' => $year->id,
                'level_id' => $levels[$group['level']],
                'name' => $group['name'],
                'code' => $group['code'],
                'capacity' => 30,
                'color' => $group['color'],
            ])]);

            $teachers = collect([
                ['Nadia', 'Rakoto', 'nadia.rakoto@escm.mg', ['MGT'], ['B3' => 'MGT', 'MBA-1' => 'MGT']],
                ['Sarah', 'Raveloson', 'sarah.raveloson@escm.mg', ['COM'], ['B1-G1' => 'COM', 'B3' => 'COM']],
                ['Thomas', 'Bernard', 'thomas.bernard@escm.mg', ['FIN'], ['MBA-1' => 'FIN', 'B2-G1' => 'FIN', 'B3' => 'FIN']],
                ['Jean', 'Rasoanaivo', 'jean.rasoanaivo@escm.mg', ['MKT', 'CPT'], ['M1-A' => 'MKT', 'B1-G1' => 'CPT', 'B2-G1' => 'MKT']],
            ])->map(function (array $row) use ($subjects, $groups) {
                $user = $this->account($row[0].' '.$row[1], $row[2], 'teacher');
                $teacher = Teacher::query()->create([
                    'user_id' => $user->id,
                    'employee_number' => 'ENS-'.str_pad((string) (Teacher::query()->count() + 1), 4, '0', STR_PAD_LEFT),
                    'first_name' => $row[0],
                    'last_name' => $row[1],
                    'email' => $row[2],
                    'status' => TeacherStatus::Active,
                ]);
                $teacher->subjects()->sync(collect($row[3])->map(fn (string $code) => $subjects[$code]->id));

                foreach ($row[4] as $groupCode => $subjectCode) {
                    GroupAssignment::query()->create([
                        'teacher_id' => $teacher->id,
                        'student_group_id' => $groups[$groupCode]->id,
                        'subject_id' => $subjects[$subjectCode]->id,
                    ]);
                }

                return $teacher;
            })->keyBy(fn (Teacher $teacher) => $teacher->email);

            $names = [
                'B1-G1' => [['Aina', 'Rabe'], ['Hery', 'Andria'], ['Mialy', 'Rakoto']],
                'B2-G1' => [['Tiana', 'Razafy'], ['Lova', 'Ranaivo'], ['Fara', 'Rahari']],
                'B3' => [['Nomena', 'Rasoanaivo'], ['Kolo', 'Randria'], ['Soa', 'Rakotobe']],
                'MBA-1' => [['Eric', 'Martin'], ['Lalaina', 'Ravelo'], ['Patricia', 'Duval']],
                'M1-A' => [['Yann', 'Morel'], ['Hasina', 'Rajaona'], ['Clara', 'Bernard']],
            ];

            $students = collect();
            $sequence = 1;

            foreach ($names as $groupCode => $people) {
                foreach ($people as [$first, $last]) {
                    $student = Student::query()->create([
                        'matricule' => 'ESCM-2026-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                        'first_name' => $first,
                        'last_name' => $last,
                        'email' => strtolower($first.'.'.$last).'@etudiant.escm.mg',
                        'phone' => '034 00 00 '.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                        'city' => 'Antananarivo',
                        'country' => 'Madagascar',
                        'status' => StudentStatus::Active,
                    ]);
                    Enrollment::query()->create([
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                        'student_group_id' => $groups[$groupCode]->id,
                        'level_id' => $groups[$groupCode]->level_id,
                        'status' => 'active',
                        'enrolled_on' => '2026-09-01',
                    ]);
                    $students->push($student);
                    $sequence++;
                }
            }

            $portalStudent = $students->first();
            $portalUser = $this->account($portalStudent->full_name, 'etudiant@escm.mg', 'student');
            $portalStudent->update(['user_id' => $portalUser->id, 'email' => 'etudiant@escm.mg']);

            $guardianUser = $this->account('Rakoto Parent', 'parent@escm.mg', 'parent');
            $guardian = Guardian::query()->create([
                'user_id' => $guardianUser->id,
                'first_name' => 'Rakoto',
                'last_name' => 'Parent',
                'email' => 'parent@escm.mg',
                'phone' => '034 12 00 00',
            ]);
            $guardian->students()->attach($portalStudent->id, ['relationship' => 'Parent', 'is_primary' => true]);

            $this->account('Équipe pédagogique', 'pedagogie@escm.mg', 'pedagogical');

            $monday = now()->startOfWeek();
            $slots = [
                [$monday->copy()->setTime(8, 0), $monday->copy()->setTime(10, 0), 'MGT', 'nadia.rakoto@escm.mg', 'B3', 'A101'],
                [$monday->copy()->setTime(10, 30), $monday->copy()->setTime(12, 30), 'COM', 'sarah.raveloson@escm.mg', 'B1-G1', 'R201'],
                [$monday->copy()->addDay()->setTime(8, 0), $monday->copy()->addDay()->setTime(10, 0), 'MGT', 'nadia.rakoto@escm.mg', 'B3', 'A101'],
                [$monday->copy()->addDay()->setTime(10, 30), $monday->copy()->addDay()->setTime(12, 30), 'COM', 'sarah.raveloson@escm.mg', 'B1-G1', 'R201'],
                [$monday->copy()->addDay()->setTime(14, 0), $monday->copy()->addDay()->setTime(16, 0), 'FIN', 'thomas.bernard@escm.mg', 'MBA-1', 'A102'],
                [$monday->copy()->addDays(2)->setTime(8, 0), $monday->copy()->addDays(2)->setTime(10, 0), 'FIN', 'thomas.bernard@escm.mg', 'B2-G1', 'A102'],
                [$monday->copy()->addDays(2)->setTime(10, 30), $monday->copy()->addDays(2)->setTime(12, 30), 'MKT', 'jean.rasoanaivo@escm.mg', 'M1-A', 'R201'],
                [$monday->copy()->addDays(2)->setTime(14, 0), $monday->copy()->addDays(2)->setTime(16, 0), 'COM', 'sarah.raveloson@escm.mg', 'B3', 'A101'],
                [$monday->copy()->addDays(3)->setTime(8, 0), $monday->copy()->addDays(3)->setTime(10, 0), 'CPT', 'jean.rasoanaivo@escm.mg', 'B1-G1', 'A102'],
                [$monday->copy()->addDays(3)->setTime(14, 0), $monday->copy()->addDays(3)->setTime(16, 0), 'MGT', 'nadia.rakoto@escm.mg', 'MBA-1', 'A101'],
                [$monday->copy()->addDays(4)->setTime(8, 0), $monday->copy()->addDays(4)->setTime(10, 0), 'FIN', 'thomas.bernard@escm.mg', 'B3', 'A102'],
                [$monday->copy()->addDays(4)->setTime(10, 30), $monday->copy()->addDays(4)->setTime(12, 30), 'MKT', 'jean.rasoanaivo@escm.mg', 'B2-G1', 'R201'],
            ];

            $courses = collect($slots)->map(fn (array $slot) => Course::query()->create([
                'academic_year_id' => $year->id,
                'subject_id' => $subjects[$slot[2]]->id,
                'teacher_id' => $teachers[$slot[3]]->id,
                'student_group_id' => $groups[$slot[4]]->id,
                'room_id' => $rooms[$slot[5]]->id,
                'starts_at' => $slot[0],
                'ends_at' => $slot[1],
                'status' => CourseStatus::Scheduled,
            ]));

            $mondayB3 = $courses[0];
            $mondayB1 = $courses[1];
            $b3Students = Enrollment::query()->where('student_group_id', $groups['B3']->id)->pluck('student_id')->values();
            $b1Students = Enrollment::query()->where('student_group_id', $groups['B1-G1']->id)->pluck('student_id')->values();
            $statuses = [AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Late];

            foreach ($b3Students->values() as $index => $studentId) {
                AttendanceRecord::query()->create([
                    'course_id' => $mondayB3->id,
                    'student_id' => $studentId,
                    'status' => $statuses[$index],
                    'recorded_by' => $teachers['nadia.rakoto@escm.mg']->user_id,
                ]);
            }

            AttendanceRecord::query()->create([
                'course_id' => $mondayB1->id,
                'student_id' => $b1Students[0],
                'status' => AttendanceStatus::Present,
                'recorded_by' => $teachers['sarah.raveloson@escm.mg']->user_id,
            ]);
            AttendanceRecord::query()->create([
                'course_id' => $mondayB1->id,
                'student_id' => $b1Students[1],
                'status' => AttendanceStatus::Absent,
                'recorded_by' => $teachers['sarah.raveloson@escm.mg']->user_id,
            ]);

            $assessment = Assessment::query()->create([
                'academic_year_id' => $year->id,
                'semester_id' => $year->semesters()->where('name', 'Semestre 1')->value('id'),
                'student_group_id' => $groups['B1-G1']->id,
                'subject_id' => $subjects['COM']->id,
                'teacher_id' => $teachers['sarah.raveloson@escm.mg']->id,
                'title' => 'Contrôle continu',
                'type' => AssessmentType::Quiz,
                'coefficient' => 1,
                'max_score' => 20,
                'assessed_on' => $monday->toDateString(),
                'status' => AssessmentStatus::Validated,
                'validated_at' => now(),
            ]);

            foreach ($b1Students->values() as $index => $studentId) {
                Grade::query()->create([
                    'assessment_id' => $assessment->id,
                    'student_id' => $studentId,
                    'score' => [15, 12, 17][$index],
                ]);
            }

            $tariff = FeeTariff::query()->create([
                'academic_year_id' => $year->id,
                'name' => 'Tranche 1 — scolarité',
                'amount' => 1500000,
                'due_on' => '2026-10-15',
            ]);

            foreach ($students as $index => $student) {
                $installment = FeeInstallment::query()->create([
                    'academic_year_id' => $year->id,
                    'student_id' => $student->id,
                    'fee_tariff_id' => $tariff->id,
                    'label' => $tariff->name,
                    'amount_due' => 1500000,
                    'discount' => $index === 0 ? 100000 : 0,
                    'due_on' => '2026-10-15',
                ]);

                if ($index < 6) {
                    Payment::query()->create([
                        'academic_year_id' => $year->id,
                        'student_id' => $student->id,
                        'fee_installment_id' => $installment->id,
                        'amount' => $index === 0 ? 700000 : 1500000,
                        'paid_on' => '2026-09-20',
                        'method' => PaymentMethod::Transfer,
                        'receipt_number' => 'REC-2026-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                        'received_by' => User::query()->where('email', config('campus.admin_email'))->value('id'),
                    ]);
                }
            }

            $submitted = AdmissionStatus::query()->where('slug', 'submitted')->first();
            $review = AdmissionStatus::query()->where('slug', 'review')->first();
            $admitted = AdmissionStatus::query()->where('slug', 'admitted')->first();

            foreach ([
                ['Lina', 'Rasoanaivo', 'submitted'],
                ['Marc', 'Andriamihaja', 'review'],
                ['Elisa', 'Rakotomalala', 'submitted'],
                ['Paul', 'Randria', 'admitted'],
            ] as $index => [$first, $last, $status]) {
                Applicant::query()->create([
                    'academic_year_id' => $year->id,
                    'admission_status_id' => match ($status) {
                        'review' => $review->id,
                        'admitted' => $admitted->id,
                        default => $submitted->id,
                    },
                    'level_id' => $levels['B1'],
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => strtolower($first).'@candidat.escm.mg',
                    'phone' => '033 00 00 0'.$index,
                ]);
            }

            Announcement::query()->create([
                'academic_year_id' => $year->id,
                'author_id' => User::query()->where('email', config('campus.admin_email'))->value('id'),
                'title' => 'Rentrée 2026-2027',
                'body' => 'Les cours du semestre 1 commencent cette semaine. Consultez votre emploi du temps et préparez vos documents d\'inscription.',
                'audience' => 'everyone',
                'published_at' => now()->subDay(),
            ]);
        });
    }

    private function account(string $name, string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
