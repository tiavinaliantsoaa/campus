<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\StudentRequest;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Services\FinanceService;
use App\Services\Gradebook;
use App\Services\StudentService;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Student::class);
        $year = $this->academicYear();

        $students = $visibility->students($request->user())
            ->with(['enrollments' => fn ($query) => $query->where('academic_year_id', $year->id)->with(['group', 'level'])])
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('group'), function ($query) use ($request, $year) {
                $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                    ->where('academic_year_id', $year->id)
                    ->where('student_group_id', $request->integer('group')));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'year' => $year,
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('students.form', $this->formData(new Student));
    }

    public function store(StudentRequest $request, StudentService $students): RedirectResponse
    {
        $result = $students->create($request->validated(), $this->academicYear(), $request->user());

        return redirect()
            ->route('students.show', $result['student'])
            ->with('status', 'Étudiant inscrit.')
            ->with('temporary_passwords', $result['passwords']);
    }

    public function show(Request $request, Student $student, Gradebook $gradebook, FinanceService $finance): View
    {
        Gate::authorize('view', $student);
        $year = $this->academicYear();
        $tab = $request->string('tab')->toString() ?: 'overview';
        abort_unless(in_array($tab, ['overview', 'information', 'schooling', 'timetable', 'attendance', 'grades', 'payments', 'documents', 'history'], true), 404);

        $student->load([
            'emergencyContacts',
            'guardians',
            'user',
            'enrollments' => fn ($query) => $query->with(['group', 'level', 'academicYear'])->orderByDesc('academic_year_id'),
        ]);

        $enrollment = $student->enrollmentFor($year);

        return view('students.show', [
            'student' => $student,
            'year' => $year,
            'tab' => $tab,
            'enrollment' => $enrollment,
            'courses' => $enrollment?->group
                ? $enrollment->group->courses()->with(['subject', 'teacher', 'room'])->orderBy('starts_at')->orderBy('id')->get()
                : collect(),
            'attendance' => $student->attendanceRecords()->with(['course.subject'])->whereHas('course', fn ($query) => $query->where('academic_year_id', $year->id))->latest('id')->limit(30)->get(),
            'grades' => $student->grades()->with(['assessment.subject', 'assessment.semester'])->whereHas('assessment', fn ($query) => $query->where('academic_year_id', $year->id)->where('status', 'validated'))->get(),
            'average' => $gradebook->average($student, $year),
            'installments' => $student->installments()->where('academic_year_id', $year->id)->withSum('payments', 'amount')->orderBy('due_on')->get(),
            'payments' => $student->payments()->where('academic_year_id', $year->id)->latest('paid_on')->get(),
            'balance' => $finance->studentBalance($student->id, $year),
            'finance' => $finance,
            'documents' => $student->documents()->latest()->get(),
        ]);
    }

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);
        $student->load(['emergencyContacts', 'guardians', 'enrollments']);

        return view('students.form', $this->formData($student));
    }

    public function update(StudentRequest $request, Student $student, StudentService $students): RedirectResponse
    {
        $passwords = $students->update($student, $request->validated(), $this->academicYear());

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Dossier étudiant mis à jour.')
            ->with('temporary_passwords', $passwords);
    }

    public function archive(Student $student, StudentService $students): RedirectResponse
    {
        Gate::authorize('update', $student);
        $students->archive($student, $this->academicYear());

        return back()->with('status', 'Étudiant archivé.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);
        $student->delete();

        return redirect()->route('students.index')->with('status', 'Étudiant supprimé.');
    }

    public function photo(Student $student): StreamedResponse
    {
        Gate::authorize('view', $student);
        abort_unless($student->photo_path && Storage::disk('local')->exists($student->photo_path), 404);

        return Storage::disk('local')->response($student->photo_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Student $student): array
    {
        $year = $this->academicYear();
        $enrollment = $student->exists ? $student->enrollmentFor($year) : null;

        return [
            'student' => $student,
            'year' => $year,
            'enrollment' => $enrollment,
            'levels' => Level::query()->orderBy('sort_order')->orderBy('name')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'statuses' => StudentStatus::cases(),
        ];
    }
}
